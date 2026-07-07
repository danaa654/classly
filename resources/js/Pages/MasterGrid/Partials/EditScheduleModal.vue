<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useTimetableGrid } from '@/Composables/useTimetableGrid'

const props = defineProps({
    show: { type: Boolean, default: false },
    block: { type: Object, default: null }, // representative block (first meeting) — used for read-only info panel + eligibility rules
    // Every meeting instance for the subject being edited (1 entry for
    // a 1x/week subject, 2 for 2x, 3 for 3x — see GreedyScheduleService's
    // "Multi-meeting subjects" docblock). Faculty/Room/Start/End are
    // edited ONCE here and applied to every entry; Day is the one field
    // that's genuinely per-meeting, so it gets one dropdown per entry.
    blocks: { type: Array, default: () => [] },
    academicTerm: { type: Object, default: null },
    faculties: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    conflicts: { type: Array, default: () => [] },
    warnings: { type: Array, default: () => [] },
    validating: { type: Boolean, default: false },
    // { faculty: [], rooms: [], times: [] } — same shape ConflictModal
    // used to render. Shown inline now, right under the conflict list,
    // instead of in a separate popup that ended up stacking BEHIND this
    // modal (z-50 vs this modal's z-60) and hiding every suggestion.
    recommendations: { type: Object, default: null },
    // 'grid' — editing an already-saved block; this writes straight to
    // the database, so an unresolved conflict still hard-blocks Apply.
    // 'preview' — editing a row inside the not-yet-saved Schedule
    // Preview; nothing here is written anywhere yet, so a conflict is
    // allowed through (the row shows red in the Preview table) rather
    // than trapping the person in this modal until it's perfect.
    context: { type: String, default: 'grid' },
    // True for Dean/Assistant Dean/OIC — they can open a block to see
    // its Faculty/Room/Day/Time, but every field is disabled and
    // there's no Apply button, since the backend rejects
    // validate-block/save for them anyway (see
    // MasterGridController::middleware()). This just gives them a
    // clean read-only view instead of a form that would only ever
    // error out on submit.
    readOnly: { type: Boolean, default: false },
    // True while a Remove Schedule request is in flight — disables
    // the Remove/Cancel/Apply buttons so a double-click can't fire two
    // deletes or a delete racing an Apply.
    removing: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'field-changed', 'apply', 'apply-faculty', 'apply-room', 'apply-time', 'remove'])

// Remove Schedule only makes sense for an already-committed grid
// block being managed by someone who can actually write — never for
// a 'preview' row (nothing's saved yet; Discard on the Schedule
// Preview modal already covers that) and never for a read-only
// viewer (the backend would 403 it anyway).
const canRemove = computed(() => props.context === 'grid' && !props.readOnly)

const { workingDays, timeRows } = useTimetableGrid(computed(() => props.academicTerm))

// Only real (non-lunch) rows give valid start/end options.
const slotOptions = computed(() => timeRows.value.filter((r) => r.type === 'slot'))

const draft = reactive({
    faculty_id: null,
    room_id: null,
    days: [],            // one entry per meeting instance, index-aligned with props.blocks
    start_minutes: null,
    end_minutes: null,   // auto-computed from start + duration whenever duration is known
})

/**
 * How many minutes one meeting actually runs — total weekly hours
 * divided by how many times a week it meets (see SubjectOffering::
 * getHoursPerMeetingAttribute(), mirrored here client-side). Null if
 * either figure is missing (legacy data from before meetings_per_week
 * existed), in which case the End field falls back to a manual picker
 * instead of guessing.
 */
const durationMinutes = computed(() => {
    const hours = props.block?.hours
    const meetings = props.block?.meetings_per_week || props.blocks.length || null

    if (!hours || !meetings) return null

    return Math.round((hours / meetings) * 60)
})

/**
 * The latest a class can end, in minutes — derived from the term's own
 * slot grid rather than a separate lookup, since slotOptions is already
 * bounded by the Academic Term's School Hours (see useTimetableGrid).
 * The last slot's endMinutes IS the school day's closing time.
 */
const schoolEndMinutes = computed(() => {
    if (!slotOptions.value.length) return null

    return Math.max(...slotOptions.value.map((s) => s.endMinutes))
})

/**
 * The earliest a class can start, in minutes — the mirror of
 * schoolEndMinutes above, used to know how far back Start can be
 * snapped when avoiding a Lunch overlap.
 */
const schoolStartMinutes = computed(() => {
    if (!slotOptions.value.length) return null

    return Math.min(...slotOptions.value.map((s) => s.startMinutes))
})

/**
 * The Academic Term's Lunch Break window, derived from the same
 * timeRows grid slotOptions is filtered from — the rows useTimetableGrid
 * marks as type 'lunch' rather than 'slot'. Null if the term has no
 * lunch break configured at all.
 */
const lunchWindow = computed(() => {
    const lunchRows = timeRows.value.filter((r) => r.type === 'lunch')

    if (!lunchRows.length) return null

    return {
        start: Math.min(...lunchRows.map((r) => r.startMinutes)),
        end: Math.max(...lunchRows.map((r) => r.endMinutes)),
    }
})

// True right after recomputeEnd() has snapped the chosen Start to a
// different slot because the original pick would have overlapped
// Lunch Break and/or run past school hours — drives the small inline
// note next to the Start/End fields so this doesn't happen silently.
const startAutoAdjusted = ref(false)

function recomputeEnd() {
    if (draft.start_minutes === null || draft.start_minutes === undefined) {
        draft.end_minutes = null
        startAutoAdjusted.value = false
        return
    }

    if (durationMinutes.value !== null) {
        let start = draft.start_minutes
        let end = start + durationMinutes.value
        let adjusted = false

        // Overlaps Lunch Break (e.g. a 9:00 AM start running 4 hrs
        // would eat straight through 12:00–1:00 PM) — snap Start back
        // to the EARLIEST start that still fits entirely before Lunch
        // begins (bounded by the School Day's own opening time), since
        // that's the smallest, least surprising change for the person.
        // Only push forward past Lunch instead if there simply isn't
        // room before it (the meeting is too long to fit in the
        // morning at all).
        if (lunchWindow.value && start < lunchWindow.value.end && end > lunchWindow.value.start) {
            const beforeLunchStart = lunchWindow.value.start - durationMinutes.value
            const fitsBeforeLunch = schoolStartMinutes.value !== null
                && beforeLunchStart >= schoolStartMinutes.value
                && slotOptions.value.some((s) => s.startMinutes === beforeLunchStart)

            if (fitsBeforeLunch) {
                start = beforeLunchStart
                end = lunchWindow.value.start
            } else {
                start = lunchWindow.value.end
                end = start + durationMinutes.value
            }

            adjusted = true
        }

        // This (possibly already-shifted) start time would run past
        // the end of school hours — snap Start back to the latest
        // start that still fits entirely within school hours AND
        // doesn't reopen a Lunch overlap.
        if (schoolEndMinutes.value !== null && end > schoolEndMinutes.value) {
            const latestStart = schoolEndMinutes.value - durationMinutes.value
            const latestEnd = schoolEndMinutes.value
            const overlapsLunch = lunchWindow.value
                && latestStart < lunchWindow.value.end
                && latestEnd > lunchWindow.value.start
            const stillValid = !overlapsLunch && slotOptions.value.some((s) => s.startMinutes === latestStart)

            if (stillValid) {
                start = latestStart
                end = latestEnd
                adjusted = true
            } else {
                // No valid start exists that fits this duration within
                // school hours (and clear of Lunch) at all — leave it
                // as picked and let the normal conflict flow surface
                // it instead of forcing something invalid.
                start = draft.start_minutes
                end = start + durationMinutes.value
                adjusted = false
            }
        }

        draft.start_minutes = start
        draft.end_minutes = end
        startAutoAdjusted.value = adjusted
    }
    // else: leave draft.end_minutes as whatever it already is — the
    // manual End picker (rendered only in this fallback case) owns it.
}

watch(() => props.blocks, (blocks) => {
    if (!blocks.length) return

    draft.faculty_id = blocks[0].faculty_id
    draft.room_id = blocks[0].room_id
    draft.days = blocks.map((b) => b.day)
    draft.start_minutes = blocks[0].start_minutes
    draft.end_minutes = blocks[0].end_minutes

    recomputeEnd()
}, { immediate: true })

function emitChange() {
    emit('field-changed', { ...draft, days: [...draft.days] })
}

function onStartChange() {
    recomputeEnd()
    emitChange()
}

function onDayChange() {
    emitChange()
}

function onManualEndChange() {
    emitChange()
}

const hasConflicts = computed(() => props.conflicts.length > 0)

// True when at least one conflict is against a block that's already
// committed to the `schedules` table (is_saved), rather than just
// another row in this same not-yet-saved preview batch. Unlike a
// preview-vs-preview conflict — which either side could still be
// edited to fix, so it's allowed through as "Apply With Conflict" —
// there is nothing left to adjust on the other side of a saved
// conflict short of a separate Remove/Edit Schedule action against
// that real row, so this must hard-block Apply even in 'preview'
// context.
const hasSavedConflict = computed(() => props.conflicts.some((c) => c.conflicting?.is_saved))

// Whether Apply should be disabled outright: either the normal 'grid'
// rule (any unresolved conflict blocks a live database write), or —
// regardless of context — a conflict against an already-saved block.
const blocksApply = computed(() => (props.context === 'grid' && hasConflicts.value) || hasSavedConflict.value)

function apply() {
    if (props.validating) return
    if (blocksApply.value) return
    emit('apply', { ...draft, days: [...draft.days] })
}

function close() {
    emit('close')
}

// Two-step confirm, inline in the footer, rather than a native
// confirm() popup or a whole separate modal — Remove Schedule is
// destructive (it deletes the committed row(s)), so it shouldn't fire
// on a single click, but this is common enough during schedule review
// that a heavier "are you sure" modal-on-a-modal would be overkill.
const confirmingRemove = ref(false)

function requestRemove() {
    confirmingRemove.value = true
}

function cancelRemove() {
    confirmingRemove.value = false
}

function confirmRemove() {
    emit('remove', props.block?.subject_offering_id)
}

// Reset the confirm step every time the modal opens for a (possibly
// different) block — otherwise re-opening Edit Schedule on another
// subject right after cancelling a remove would show the confirm
// prompt already armed.
watch(() => props.show, (show) => {
    if (!show) confirmingRemove.value = false
})

function formatMinutes(minutes) {
    if (minutes === null || minutes === undefined) return '—'
    const h = Math.floor(minutes / 60) % 24
    const m = minutes % 60
    const period = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 === 0 ? 12 : h % 12
    return `${h12}:${String(m).padStart(2, '0')} ${period}`
}

function facultyLabel(f) {
    return [f.first_name, f.last_name].filter(Boolean).join(' ')
}

function formatDay(day) {
    return day ? day.charAt(0).toUpperCase() + day.slice(1) : ''
}

/**
 * "Mon 8:00 AM–10:00 AM" for the OTHER block a conflict is against
 * (c.conflicting, from ScheduleValidationService::conflict()) — null
 * if that block has no day/time to show (shouldn't normally happen,
 * but conflicts of type outside_school_hours/lunch_break_violation/
 * non_working_day never carry a `conflicting` block at all, only
 * `current`).
 */
function conflictTimeLabel(c) {
    if (!c.conflicting || c.conflicting.start_minutes == null || c.conflicting.end_minutes == null) return null

    return `${formatDay(c.conflicting.day)} ${formatMinutes(c.conflicting.start_minutes)}–${formatMinutes(c.conflicting.end_minutes)}`
}

/*
|--------------------------------------------------------------------------
| Eligible Rooms / Faculty — mirrors GreedyScheduleService exactly
|--------------------------------------------------------------------------
|
| The dropdowns here must never offer a choice the scheduler itself
| would reject. Room eligibility mirrors candidateRooms(): same room
| type (Lecture/Laboratory) as the offering, and allowed for either
| "General" or this offering's own program. Faculty eligibility
| mirrors resolveAutoFacultyCandidates()'s scope table:
|
|   | Scope             | Major (own dept) | Minor (own dept) | Minor (other dept) |
|   |-------------------|:-----------------:|:-----------------:|:--------------------:|
|   | Departmental      |        YES        |         NO         |          NO          |
|   | Cross-Department  |        YES        |        YES         |         YES          |
|   | General / GenEd   |        NO         |        YES         |         YES          |
|
| A Major is therefore always Departmental/Cross-Department AND that
| exact department; a Minor is Cross-Department (any department) or
| General/GenEd (department_id null). If the schedule's CURRENT
| faculty/room somehow falls outside these rules (e.g. legacy data),
| it's still included so the field never renders empty/blank — but it
| will no longer be offered as a NEW choice for anyone else.
*/

const isMajor = computed(() => props.block?.classification === 'Major')

const eligibleRooms = computed(() => {
    if (!props.block) return []

    const list = props.rooms.filter((room) => {
        if (room.active === false) return false

        if (props.block.room_type && room.room_type !== props.block.room_type) return false

        const codes = room.room_group_codes ?? []

        return codes.includes('General') || (props.block.program_code && codes.includes(props.block.program_code))
    })

    if (draft.room_id && !list.some((room) => room.id === draft.room_id)) {
        const current = props.rooms.find((room) => room.id === draft.room_id)
        if (current) list.push(current)
    }

    return list
})

const eligibleFaculties = computed(() => {
    if (!props.block) return []

    const list = props.faculties.filter((f) => {
        if (f.status === false) return false

        if (isMajor.value) {
            return ['departmental', 'cross_department'].includes(f.faculty_scope)
                && f.department_id === props.block.department_id
        }

        return ['general', 'cross_department'].includes(f.faculty_scope)
    })

    if (draft.faculty_id && !list.some((f) => f.id === draft.faculty_id)) {
        const current = props.faculties.find((f) => f.id === draft.faculty_id)
        if (current) list.push(current)
    }

    return list
})
</script>

<template>
    <div v-if="show && block" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/40 px-4">
        <div
            class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full p-5 max-h-[90vh] overflow-y-auto transition-all duration-150"
            :class="hasConflicts ? 'max-w-3xl' : 'max-w-lg'"
        >
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-black text-slate-800 dark:text-slate-100">
                    {{ readOnly ? 'Schedule Details' : 'Edit Schedule' }}
                </h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="close">✕</button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                {{ readOnly
                    ? 'View-only — Faculty, Room, Day, and Time can only be changed by an Admin or Registrar.'
                    : 'Changes are checked instantly. Nothing is saved until you click Save Schedule on the Master Grid.' }}
            </p>

            <!--
                Split layout, form left / suggestions right
                --------------------------------------------------------------
                Only splits into two columns while there's actually
                something to suggest (hasConflicts) — with no conflict
                the modal stays a single centered column exactly like
                before, since there's nothing to show on a "right side."
                Both columns sit inside the SAME scroll container (the
                outer overflow-y-auto above), so the person never has to
                choose between seeing the form or seeing the suggestions.
            -->
            <div :class="hasConflicts ? 'grid grid-cols-1 md:grid-cols-2 gap-x-6' : ''">

                <div>
                    <!-- Read-only identity -->
                    <div class="grid grid-cols-2 gap-x-3 gap-y-2 mb-4 rounded-lg bg-slate-50 dark:bg-slate-900/40 p-3 text-xs">
                        <div><span class="text-slate-400 font-bold uppercase text-[10px]">Subject</span><p class="font-semibold text-slate-700 dark:text-slate-200">{{ block.subject_code }} — {{ block.descriptive_title }}</p></div>
                        <div><span class="text-slate-400 font-bold uppercase text-[10px]">Program</span><p class="font-semibold text-slate-700 dark:text-slate-200">{{ block.program_code }}</p></div>
                        <div><span class="text-slate-400 font-bold uppercase text-[10px]">Year / Section</span><p class="font-semibold text-slate-700 dark:text-slate-200">Y{{ block.year_level }} — {{ block.section_code }}</p></div>
                        <div><span class="text-slate-400 font-bold uppercase text-[10px]">Units</span><p class="font-semibold text-slate-700 dark:text-slate-200">{{ block.units }}</p></div>
                        <div class="col-span-2"><span class="text-slate-400 font-bold uppercase text-[10px]">Academic Term</span><p class="font-semibold text-slate-700 dark:text-slate-200">{{ academicTerm?.display_name }}</p></div>
                    </div>

                    <!-- Editable fields -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Faculty</label>
                            <select v-model.number="draft.faculty_id" :disabled="readOnly" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30 disabled:opacity-60 disabled:cursor-not-allowed" @change="emitChange">
                                <option :value="null">Unassigned</option>
                                <option v-for="f in eligibleFaculties" :key="f.id" :value="f.id">{{ facultyLabel(f) }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Room</label>
                            <select v-model.number="draft.room_id" :disabled="readOnly" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30 disabled:opacity-60 disabled:cursor-not-allowed" @change="emitChange">
                                <option v-for="r in eligibleRooms" :key="r.id" :value="r.id">{{ r.room_code }} ({{ r.room_type }})</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">
                                {{ draft.days.length > 1 ? `Meeting Days (${draft.days.length}x/week)` : 'Day' }}
                            </label>
                            <div class="grid gap-2" :class="draft.days.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                                <select
                                    v-for="(day, i) in draft.days"
                                    :key="i"
                                    v-model="draft.days[i]"
                                    :disabled="readOnly"
                                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30 disabled:opacity-60 disabled:cursor-not-allowed"
                                    @change="onDayChange"
                                >
                                    <option v-for="d in workingDays" :key="d.field" :value="d.field">{{ d.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Start</label>
                                <select v-model.number="draft.start_minutes" :disabled="readOnly" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30 disabled:opacity-60 disabled:cursor-not-allowed" @change="onStartChange">
                                    <option v-for="s in slotOptions" :key="'s' + s.startMinutes" :value="s.startMinutes">
                                        {{ s.label.split(' - ')[0] }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">
                                    End{{ durationMinutes !== null ? ' (auto)' : '' }}
                                </label>
                                <!-- Duration known (hours ÷ meetings/week) -> End is derived,
                                     not chosen: picking Start already fixes it, same as a
                                     4hrs/2x subject running exactly 2hrs per meeting. -->
                                <div
                                    v-if="durationMinutes !== null"
                                    class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-slate-600 dark:text-slate-300 text-sm px-2 py-1.5"
                                >
                                    {{ formatMinutes(draft.end_minutes) }}
                                </div>
                                <!-- Fallback for legacy offerings with no hours/meetings_per_week
                                     on record yet — don't guess a duration, let it be picked. -->
                                <select
                                    v-else
                                    v-model.number="draft.end_minutes"
                                    :disabled="readOnly"
                                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30 disabled:opacity-60 disabled:cursor-not-allowed"
                                    @change="onManualEndChange"
                                >
                                    <option v-for="s in slotOptions" :key="'e' + s.endMinutes" :value="s.endMinutes">
                                        {{ s.label.split(' - ')[1] }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <p v-if="startAutoAdjusted" class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">
                            Start time adjusted to {{ formatMinutes(draft.start_minutes) }} so this class avoids
                            Lunch Break and stays within school hours (ends by {{ formatMinutes(draft.end_minutes) }}).
                        </p>
                    </div>

                    <!-- Inline validation state — never populated in
                         read-only mode, since disabled inputs never
                         emit field-changed and validate-block is
                         never called (that endpoint is Admin/
                         Registrar-only anyway). Still explicitly
                         gated here so this can never flash briefly
                         for a read-only viewer. -->
                    <div v-if="!readOnly && validating" class="mt-4 text-xs text-slate-400 font-semibold">
                        Checking for conflicts…
                    </div>

                    <div v-else-if="!readOnly && hasConflicts" class="mt-4 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 p-3">
                        <p class="text-xs font-black text-red-700 dark:text-red-300 mb-1">⚠ Conflicts found — see suggestions {{ hasConflicts ? '\u2192' : 'below' }}</p>
                        <ul class="text-[11px] text-red-700 dark:text-red-300 list-disc list-inside space-y-1.5">
                            <li v-for="(c, i) in conflicts" :key="i">
                                {{ c.reason }}
                                <!-- What/why card — which OTHER subject is actually
                                     holding this faculty/room/section at this time,
                                     so the person doesn't have to go hunting for it
                                     on the grid themselves. Only conflicts checked
                                     against another block (faculty/room/section)
                                     carry a `conflicting` payload — school hours,
                                     lunch, non-working-day, and room-type conflicts
                                     never have one, since there's no "other block"
                                     to point at. -->
                                <div v-if="c.conflicting" class="mt-1 rounded-md border border-red-200 dark:border-red-800/60 bg-white/70 dark:bg-red-950/30 px-2 py-1.5 list-none">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="font-bold text-red-800 dark:text-red-200">
                                            {{ c.conflicting.subject_code || 'Unknown subject' }}
                                            <span v-if="c.conflicting.section_code" class="font-semibold"> — {{ c.conflicting.section_code }}</span>
                                        </p>
                                        <!-- Already committed to the Master Grid vs still
                                             just sitting in this not-yet-saved preview batch
                                             — the whole reason Apply gets hard-blocked for
                                             the former (see blocksApply) but not the latter. -->
                                        <span
                                            v-if="c.conflicting.is_saved"
                                            class="shrink-0 rounded-full bg-red-600 text-white text-[9px] font-black uppercase tracking-wide px-1.5 py-0.5"
                                        >Already Saved</span>
                                        <span
                                            v-else
                                            class="shrink-0 rounded-full bg-slate-400 text-white text-[9px] font-black uppercase tracking-wide px-1.5 py-0.5"
                                        >Preview Only</span>
                                    </div>
                                    <p v-if="conflictTimeLabel(c)" class="text-red-700 dark:text-red-300">{{ conflictTimeLabel(c) }}</p>
                                    <p v-if="c.conflicting.faculty_name" class="text-red-700 dark:text-red-300">Faculty: {{ c.conflicting.faculty_name }}</p>
                                    <p v-if="c.conflicting.room_code" class="text-red-700 dark:text-red-300">Room: {{ c.conflicting.room_code }}</p>
                                </div>
                            </li>
                        </ul>

                        <p v-if="hasSavedConflict" class="text-[11px] text-red-700 dark:text-red-300 mt-2 font-bold">
                            This conflicts with a schedule already saved to the Master Grid — Apply is disabled
                            until you change Faculty, Room, Day, or Time here, or go fix/remove the other
                            schedule first.
                        </p>
                        <p v-if="context === 'preview' && !hasSavedConflict" class="text-[11px] text-red-600 dark:text-red-400 mt-2 italic">
                            This subject hasn't been saved to the Master Grid yet — you can still Apply
                            this change now and it'll show as a conflict in the Schedule Preview table.
                            Fix it there (or come back and re-edit) before clicking Save Changes.
                        </p>
                    </div>

                    <div v-else-if="!readOnly && warnings.length" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-3">
                        <ul class="text-[11px] text-amber-700 dark:text-amber-300 list-disc list-inside space-y-0.5">
                            <li v-for="(w, i) in warnings" :key="i">{{ w.message }}</li>
                        </ul>
                    </div>
                </div>

                <!--
                    Suggestions — right column, only while a conflict
                    exists. This used to be ConflictModal, opened on top
                    of this same modal as a separate popup. Since that
                    popup's z-50 sat BELOW this modal's z-60, every
                    suggestion rendered fully hidden behind this form —
                    the person saw "Conflicts found" but never any
                    buttons to fix it. Living here instead means
                    there's only ever one modal open per edit, and the
                    form stays visible right alongside its own fix.
                -->
                <div v-if="!readOnly && hasConflicts && recommendations" class="mt-4 md:mt-0 space-y-3 md:border-l md:border-slate-200 md:dark:border-slate-700 md:pl-6">
                    <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Suggestions</p>

                    <div v-if="recommendations.faculty?.length">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 mb-1.5">Suggested Faculty</p>
                        <button
                            v-for="f in recommendations.faculty"
                            :key="f.faculty_id"
                            type="button"
                            class="w-full text-left rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-2 mb-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition"
                            @click="emit('apply-faculty', f.faculty_id)"
                        >
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ f.full_name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Current Load: <strong class="font-bold text-slate-700 dark:text-slate-200">{{ f.current_load }}/{{ f.max_units }}</strong> units</p>
                        </button>
                    </div>

                    <div v-if="recommendations.rooms?.length">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 mb-1.5">Suggested Room</p>
                        <button
                            v-for="r in recommendations.rooms"
                            :key="r.room_id"
                            type="button"
                            class="w-full text-left rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-2 mb-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition"
                            @click="emit('apply-room', r.room_id)"
                        >
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ r.room_code }} <span v-if="r.is_preferred" class="text-[10px] text-blue-500 font-black">PREFERRED</span></p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ r.room_type }} · Available</p>
                        </button>
                    </div>

                    <div v-if="recommendations.times?.length">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-500 mb-1.5">Suggested Time</p>
                        <button
                            v-for="(t, i) in recommendations.times"
                            :key="i"
                            type="button"
                            class="w-full text-left rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-2 mb-1.5 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition"
                            @click="emit('apply-time', { days: t.days ?? [t.day], start_minutes: t.start_minutes, end_minutes: t.end_minutes })"
                        >
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ t.label }}</p>
                        </button>
                    </div>

                    <p v-if="!recommendations.faculty?.length && !recommendations.rooms?.length && !recommendations.times?.length" class="text-xs text-slate-400 italic">
                        No automatic alternatives found — try a different manual change.
                    </p>
                </div>

            </div>

            <div class="flex items-center justify-between gap-2 mt-5">
                <!-- Remove Schedule — left-aligned, separated from
                     Cancel/Apply so it doesn't sit next to them and
                     invite a mis-click. Only ever shown for an
                     Admin/Registrar editing an already-saved grid
                     block (see canRemove above). -->
                <div v-if="canRemove">
                    <div v-if="!confirmingRemove">
                        <button
                            type="button"
                            :disabled="removing"
                            class="text-xs font-bold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 border border-red-300 hover:border-red-400 dark:border-red-700 dark:hover:border-red-600 rounded-lg px-3 py-1.5 disabled:opacity-50"
                            @click="requestRemove"
                        >
                            Remove Schedule
                        </button>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <span class="text-xs font-bold text-red-700 dark:text-red-300">Remove this schedule?</span>
                        <button type="button" class="btn-neutral !py-1 !px-2 !text-xs" :disabled="removing" @click="cancelRemove">
                            No
                        </button>
                        <button
                            type="button"
                            class="!py-1 !px-2 !text-xs rounded-lg font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60"
                            :disabled="removing"
                            @click="confirmRemove"
                        >
                            {{ removing ? 'Removing…' : 'Yes, Remove' }}
                        </button>
                    </div>
                </div>
                <div v-else></div>

                <div class="flex justify-end gap-2">
                    <button v-if="readOnly" type="button" class="btn-neutral" @click="close">
                        Close
                    </button>

                    <template v-else>
                        <button type="button" class="btn-neutral" :disabled="removing" @click="close">
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="removing || validating || blocksApply"
                            class="btn-save"
                            :class="context === 'preview' && hasConflicts && !hasSavedConflict ? '!bg-amber-500 hover:!bg-amber-600' : ''"
                            @click="apply"
                        >
                            {{ hasSavedConflict
                                ? 'Resolve Conflict First'
                                : (context === 'preview' && hasConflicts ? 'Apply With Conflict' : 'Apply Changes') }}
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>