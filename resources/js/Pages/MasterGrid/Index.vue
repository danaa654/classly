<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MasterGridHeader from './Partials/MasterGridHeader.vue'
import SubjectSidebar from './Partials/SubjectSidebar.vue'
import RoomSidebar from './Partials/RoomSidebar.vue'
import Timetable from './Partials/Timetable.vue'
import GenerateScheduleModal from './Partials/GenerateScheduleModal.vue'
import GeneratePreviewModal from './Partials/GeneratePreviewModal.vue'
import EditScheduleModal from './Partials/EditScheduleModal.vue'
import ConflictModal from './Partials/ConflictModal.vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    activeTerm: { type: Object, default: null },
    subjectOfferings: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    programs: { type: Array, default: () => [] },
    specializations: { type: Array, default: () => [] },
    faculties: { type: Array, default: () => [] },
    savedSchedules: { type: Array, default: () => [] },
    collegeColors: { type: Object, default: () => ({}) },
})

/* ── Sidebar collapse state ─────────────────────────────────────── */
const subjectsCollapsed = ref(false)
const roomsCollapsed = ref(false)

/* ── Room View selection ────────────────────────────────────────────
   Clicking a Room card switches the timetable into "Room View" —
   only schedules assigned to that room are shown. */
const selectedRoom = ref(null)

function selectRoom(room) {
    selectedRoom.value = selectedRoom.value?.id === room.id ? null : room
}

function clearSelectedRoom() {
    selectedRoom.value = null
}

/* ── Generate Schedule modal ──────────────────────────────────────── */
const showGenerateModal = ref(false)
const generating = ref(false)
const generateError = ref(null)

/* ── Scheduled events (live Master Grid state) ──────────────────────
   Populated in-memory, but always kept in sync with what's actually
   committed in `schedules` — see applyEdit()/applyGeneratedPreview()
   below, both of which persist immediately rather than sitting in an
   "unsaved" local-only state. Seeded from already-committed Schedule
   rows so a returning Registrar sees the real, current schedule. */
const scheduledEvents = ref(
    props.savedSchedules.map((s) => ({ ...s, status: 'saved' }))
)
const lastGenerateSummary = ref(null)
const hasPreview = computed(() => scheduledEvents.value.length > 0)

/* ── Generate Schedule preview (review-first, save-on-commit) ───────
   Generate never writes anything anywhere. The raw response sits in
   generatePreview purely for review until the user explicitly clicks
   Save Changes — see applyGeneratedPreview() below, which posts
   straight to master-grid.save. Discarding never touches anything,
   since nothing was ever written to produce the preview. */
const showPreviewModal = ref(false)
const generatePreview = ref(null)
const generatePreviewSectionId = ref(null)
const applyingPreview = ref(false)
const applyError = ref(null)

async function handleGenerate(payload) {
    generating.value = true
    generateError.value = null

    try {
        const { data } = await axios.post(route('master-grid.generate'), payload)

        generatePreview.value = data
        generatePreviewSectionId.value = payload.section_id
        applyError.value = null
        showGenerateModal.value = false
        showPreviewModal.value = true
    } catch (err) {
        generateError.value = err.response?.data?.message ?? 'Failed to generate schedule. Please try again.'
    } finally {
        generating.value = false
    }
}

/**
 * The user reviewed the preview and clicked "Save Changes" — this now
 * commits straight to the database. Reviewing the table IS the
 * checking step; there is no separate "applied but unsaved" state
 * anymore. Additive per section, same rule as before: only the blocks
 * belonging to the section just generated are replaced, everything
 * else (other sections' already-saved schedules) is untouched.
 */
async function applyGeneratedPreview() {
    if (!generatePreview.value) return

    applyingPreview.value = true
    applyError.value = null

    const data = generatePreview.value
    const newBlocks = data.blocks.filter((block) => block.status === 'preview')

    const mergedBlocks = scheduledEvents.value
        .filter((event) => event.section_id !== generatePreviewSectionId.value)
        .concat(newBlocks)

    try {
        await axios.post(route('master-grid.save'), {
            blocks: mergedBlocks,
        })

        lastGenerateSummary.value = {
            scheduled: data.scheduled_count,
            unscheduled: data.unscheduled_count,
            unplaced: data.blocks.filter((block) => block.status !== 'preview'),
        }

        // Reflect the merge immediately so the grid doesn't sit blank
        // while the background reload below catches up.
        scheduledEvents.value = mergedBlocks.map((event) => ({ ...event, status: 'saved' }))

        showPreviewModal.value = false
        generatePreview.value = null
        generatePreviewSectionId.value = null

        // Resync from the database once the reload actually lands —
        // this is the real source of truth from here on.
        router.reload({
            only: ['subjectOfferings', 'rooms', 'savedSchedules'],
            onSuccess: (page) => {
                scheduledEvents.value = page.props.savedSchedules.map((s) => ({ ...s, status: 'saved' }))
            },
        })
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.conflicts) {
            conflictingIds.value = Object.keys(err.response.data.conflicts).map(Number)
            applyError.value = err.response.data.message
        } else {
            applyError.value = 'Failed to save the schedule. Please try again.'
        }
    } finally {
        applyingPreview.value = false
    }
}

/**
 * Throws the whole preview away — nothing was ever written anywhere,
 * so there's nothing to undo.
 */
function discardGeneratedPreview() {
    showPreviewModal.value = false
    generatePreview.value = null
    generatePreviewSectionId.value = null
    applyError.value = null
}

/* ── Phase 2: Interactive Schedule Review ─────────────────────────── */

const showEditModal = ref(false)
const editingBlock = ref(null)   // original block, untouched, for Cancel
const draftBlock = ref(null)     // block + latest edited fields
const validating = ref(false)
const currentConflicts = ref([])
const currentWarnings = ref([])

// 'grid'    — editing an already-saved block straight on the Master Grid.
// 'preview' — editing a row inside the (not-yet-saved) Schedule Preview
//             modal. Same modal, same validation call, just a different
//             sibling list to check against and a different place the
//             accepted edit gets written back to.
const editContext = ref('grid')

const showConflictModal = ref(false)
const conflictRecommendations = ref(null)

const saving = ref(false)
const saveError = ref(null)

// subject_offering_ids currently failing validation — populated right
// before/after a failed save attempt (bulk apply or Save Schedule), so
// blocks can be highlighted on the grid per spec ("Highlight all
// conflicting schedule blocks").
const conflictingIds = ref([])

function openEditModal(block, context = 'grid') {
    editContext.value = context
    editingBlock.value = block
    draftBlock.value = { ...block }
    currentConflicts.value = []
    currentWarnings.value = []
    showEditModal.value = true
}

function closeEditModal() {
    showEditModal.value = false
    editingBlock.value = null
    draftBlock.value = null
    currentConflicts.value = []
    currentWarnings.value = []
    showConflictModal.value = false
}

let validateToken = 0

async function validateDraft(fields) {
    if (!editingBlock.value) return

    draftBlock.value = { ...editingBlock.value, ...fields }

    const token = ++validateToken
    validating.value = true

    // Every other block, with the one being edited swapped for its
    // latest draft — the validator checks the draft against its
    // siblings, never against its own stale state. Which set of
    // siblings depends on where the edit is happening: a block being
    // edited from inside the still-unsaved Schedule Preview only needs
    // to be checked against the OTHER rows of that same preview run
    // (the saved grid is untouched until Save Changes), while a block
    // edited straight on the Master Grid is checked against the full
    // saved schedule.
    const siblingSource = editContext.value === 'preview'
        ? (generatePreview.value?.blocks ?? []).filter((b) => b.status === 'preview')
        : scheduledEvents.value

    const allBlocks = siblingSource.map((event) =>
        event.subject_offering_id === draftBlock.value.subject_offering_id ? draftBlock.value : event
    )

    try {
        const { data } = await axios.post(route('master-grid.validate-block'), {
            block: draftBlock.value,
            blocks: allBlocks,
        })

        if (token !== validateToken) return // a newer edit superseded this check

        currentConflicts.value = data.conflicts
        currentWarnings.value = data.warnings

        if (data.conflicts.length > 0) {
            conflictRecommendations.value = data.recommendations
            showConflictModal.value = true
        } else {
            showConflictModal.value = false
        }
    } catch (err) {
        currentConflicts.value = [{ type: 'error', reason: 'Could not check for conflicts. Please try again.' }]
    } finally {
        if (token === validateToken) validating.value = false
    }
}

function dismissConflictModal() {
    showConflictModal.value = false
}

function applySuggestedFaculty(facultyId) {
    validateDraft({ ...draftBlock.value, faculty_id: facultyId })
}

function applySuggestedRoom(roomId) {
    validateDraft({ ...draftBlock.value, room_id: roomId })
}

function applySuggestedTime({ day, start_minutes, end_minutes }) {
    validateDraft({ ...draftBlock.value, day, start_minutes, end_minutes })
}

/**
 * Commits the current draft. A 'preview' edit just patches the
 * row inside the still-unsaved Schedule Preview result — see the
 * 'preview' branch below. A 'grid' edit is a change to an ALREADY
 * SAVED block, so clicking Apply Changes here saves it straight to
 * the database immediately — there is no separate "unsaved" state to
 * confirm or warn about afterwards.
 */
async function applyEdit(fields) {
    if (currentConflicts.value.length > 0) return

    const facultyName = props.faculties.find((f) => f.id === fields.faculty_id)
    const roomCode = props.rooms.find((r) => r.id === fields.room_id)?.room_code ?? null
    const patch = {
        ...fields,
        faculty_name: facultyName ? [facultyName.first_name, facultyName.last_name].filter(Boolean).join(' ') : null,
        room_code: roomCode,
    }

    if (editContext.value === 'preview') {
        // Patch the row in place inside the still-unsaved preview result
        // — nothing touches the real Master Grid or the database until
        // the preview modal's own Save Changes is clicked.
        generatePreview.value = {
            ...generatePreview.value,
            blocks: generatePreview.value.blocks.map((block) =>
                block.subject_offering_id === editingBlock.value.subject_offering_id
                    ? { ...block, ...patch, status: 'preview' }
                    : block
            ),
        }

        closeEditModal()
        return
    }

    const editedSubjectOfferingId = editingBlock.value.subject_offering_id
    const mergedBlocks = scheduledEvents.value.map((event) =>
        event.subject_offering_id === editedSubjectOfferingId ? { ...event, ...patch } : event
    )

    saving.value = true
    saveError.value = null

    try {
        await axios.post(route('master-grid.save'), { blocks: mergedBlocks })

        scheduledEvents.value = mergedBlocks.map((event) => ({ ...event, status: 'saved' }))
        closeEditModal()

        router.reload({
            only: ['subjectOfferings', 'rooms', 'savedSchedules'],
            onSuccess: (page) => {
                scheduledEvents.value = page.props.savedSchedules.map((s) => ({ ...s, status: 'saved' }))
            },
        })
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.conflicts) {
            conflictingIds.value = Object.keys(err.response.data.conflicts).map(Number)
            saveError.value = err.response.data.message
        } else {
            saveError.value = 'Failed to save this change. Please try again.'
        }
    } finally {
        saving.value = false
    }
}

const hasActiveTerm = computed(() => !!props.activeTerm)
</script>

<template>

<Head title="Master Grid" />

<div class="flex flex-col h-full w-full min-w-0 overflow-hidden">

    <!-- Page header — same treatment as Subjects/Faculty/etc. -->
    <div class="mb-4 shrink-0">
        <h1 class="text-3xl font-bold text-[var(--text-primary)]">
            Master Grid
        </h1>
        <p class="text-[var(--text-muted)] mt-1">
            Scheduling workspace for the active Academic Term — Subjects, Rooms, and the Timetable grid.
        </p>
    </div>

    <!-- Preview summary banner -->
    <div
        v-if="lastGenerateSummary && lastGenerateSummary.unplaced.length > 0"
        class="mb-4 shrink-0 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 px-4 py-2 text-xs text-amber-800 dark:text-amber-300"
    >
        <span class="font-bold">{{ lastGenerateSummary.scheduled }} scheduled</span>
        · {{ lastGenerateSummary.unplaced.length }} could not be placed:
        <span v-for="(item, i) in lastGenerateSummary.unplaced" :key="item.subject_offering_id">
            {{ item.subject_code }}{{ i < lastGenerateSummary.unplaced.length - 1 ? ',' : '' }}
        </span>
    </div>

    <!-- Save error banner -->
    <div
        v-if="saveError"
        class="mb-4 shrink-0 rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-4 py-2 text-xs text-red-800 dark:text-red-300"
    >
        {{ saveError }} — conflicting blocks are highlighted in red on the grid.
    </div>

    <!-- Workspace card — sized to fit the viewport exactly, no page-level scroll -->
    <div class="master-grid-shell flex-1 flex flex-col min-h-0 min-w-0 w-full bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden">

        <MasterGridHeader
            :active-term="activeTerm"
            :selected-room="selectedRoom"
            :saving="saving"
            @clear-room="clearSelectedRoom"
            @generate="showGenerateModal = true"
        />

        <div v-if="!hasActiveTerm" class="flex-1 flex items-center justify-center p-10">
            <div class="text-center max-w-md">
                <p class="text-4xl mb-3">🗓️</p>
                <p class="font-bold text-[var(--text-primary)]">No Active Academic Term</p>
                <p class="text-sm text-[var(--text-muted)] mt-1">
                    Activate an Academic Term to open the Master Grid workspace.
                </p>
            </div>
        </div>

        <div v-else class="flex-1 flex min-h-0 min-w-0 overflow-hidden">
            <!-- LEFT/CENTER: Timetable -->
            <div
                class="flex-1 min-w-0 overflow-auto custom-scrollbar-theme"
                style="background: var(--page-bg)"
            >
                <Timetable
                    :academic-term="activeTerm"
                    :selected-room="selectedRoom"
                    :scheduled-events="scheduledEvents"
                    :college-colors="collegeColors"
                    :editable="hasPreview"
                    :conflicting-ids="conflictingIds"
                    @edit-block="openEditModal"
                />
            </div>

            <!-- RIGHT: Subjects + Rooms, side by side with each other -->
            <div class="shrink-0 flex min-h-0 border-l border-[var(--card-border)]">
                <SubjectSidebar
                    v-model:collapsed="subjectsCollapsed"
                    :offerings="subjectOfferings"
                    :college-colors="collegeColors"
                />

                <RoomSidebar
                    v-model:collapsed="roomsCollapsed"
                    :rooms="rooms"
                    :selected-room="selectedRoom"
                    :college-colors="collegeColors"
                    @select="selectRoom"
                />
            </div>
        </div>
    </div>
</div>

<GenerateScheduleModal
    :show="showGenerateModal"
    :departments="departments"
    :programs="programs"
    :specializations="specializations"
    :subject-offerings="subjectOfferings"
    :generating="generating"
    :error="generateError"
    @close="showGenerateModal = false"
    @generate="handleGenerate"
/>

<GeneratePreviewModal
    :show="showPreviewModal"
    :result="generatePreview"
    :saving="applyingPreview"
    :error="applyError"
    @save="applyGeneratedPreview"
    @discard="discardGeneratedPreview"
    @edit-block="(block) => openEditModal(block, 'preview')"
/>

<EditScheduleModal
    :show="showEditModal"
    :block="draftBlock"
    :academic-term="activeTerm"
    :faculties="faculties"
    :rooms="rooms"
    :conflicts="currentConflicts"
    :warnings="currentWarnings"
    :validating="validating"
    @close="closeEditModal"
    @field-changed="validateDraft"
    @apply="applyEdit"
/>

<ConflictModal
    :show="showConflictModal"
    :conflicts="currentConflicts"
    :recommendations="conflictRecommendations"
    @dismiss="dismissConflictModal"
    @apply-faculty="applySuggestedFaculty"
    @apply-room="applySuggestedRoom"
    @apply-time="applySuggestedTime"
/>

</template>

<style scoped>
.master-grid-shell {
    height: calc(100vh - 210px);
    min-height: 420px;
}
</style>