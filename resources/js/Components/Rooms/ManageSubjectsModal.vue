<script setup>
import { computed, reactive, ref } from 'vue'
import axios from 'axios'

/*
|--------------------------------------------------------------------------
| Modal, not a Page
|--------------------------------------------------------------------------
|
| This used to be resources/js/Pages/Rooms/ManageSubjects.vue — a full
| Inertia page you navigated to. It's now a plain component rendered as
| an overlay on top of Rooms/Index.vue. Rooms/Index.vue fetches the data
| below via axios and passes it in as `initialData`; this component
| never triggers an Inertia visit itself, so the page underneath it
| never re-renders, navigates, or loses its filters/scroll position.
|
| On save, this emits 'saved' with the fresh preferred_hours/
| preferred_count for THIS room only — Rooms/Index.vue is responsible
| for merging that into its local rooms list and closing this modal.
*/

const props = defineProps({
    // Shape: { room, active_academic_term, offerings, weekly_capacity_hours }
    // — exactly what RoomController::manageSubjects() now returns as JSON.
    initialData: Object,
})

const emit = defineEmits(['close', 'saved'])

const room = props.initialData.room
const activeAcademicTerm = props.initialData.active_academic_term
const offerings = props.initialData.offerings
const weeklyCapacityHours = props.initialData.weekly_capacity_hours

/*
|--------------------------------------------------------------------------
| Selection State
|--------------------------------------------------------------------------
|
| Seeded from is_preferred so already-preferred offerings show checked on
| load. Nothing is written to the server until "Save Preferences" is
| clicked — this is a plain local Set, not a schedule of any kind.
*/

const selected = reactive(
    new Set(offerings.filter(o => o.is_preferred).map(o => o.id))
)

function toggle(offering) {
    if (selected.has(offering.id)) {
        selected.delete(offering.id)
    } else {
        selected.add(offering.id)
    }
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
|
| "Recommended only" defaults ON — it applies the department smart-filter
| (General/Shared rooms see everything; program-specific rooms see their
| own Major subjects + General Education Minor subjects). It's a display
| filter only: switching it off never unchecks anything already selected.
*/

const search = ref('')
const recommendedOnly = ref(true)

const filteredOfferings = computed(() => {
    const term = search.value.trim().toLowerCase()

    return offerings.filter(offering => {
        if (recommendedOnly.value && !offering.is_recommended && !selected.has(offering.id)) {
            return false
        }

        if (!term) {
            return true
        }

        return [
            offering.edp_code,
            offering.subject_code,
            offering.subject_title,
            offering.program_code,
            offering.section_code,
        ].filter(Boolean).some(field => field.toLowerCase().includes(term))
    })
})

/*
|--------------------------------------------------------------------------
| Utilization (derived — no schedule involved)
|--------------------------------------------------------------------------
|
| A live preview of Preferred Hours vs. the room's weekly capacity
| constant, recalculated purely from what's currently checked.
*/

const selectedOfferings = computed(() =>
    offerings.filter(o => selected.has(o.id))
)

// Offerings that are checked here AND currently claimed by a different
// room — saving will move them to this room. Surfaced so nothing shifts
// silently.
const pendingTransfers = computed(() =>
    selectedOfferings.value.filter(o => o.claimed_by_room_code)
)

const preferredHours = computed(() =>
    selectedOfferings.value.reduce((sum, o) => sum + Number(o.hours || 0), 0)
)

const utilizationPercent = computed(() => {
    if (!weeklyCapacityHours) return 0
    return Math.min(100, Math.round((preferredHours.value / weeklyCapacityHours) * 100))
})

const isOverCapacity = computed(() => preferredHours.value > weeklyCapacityHours)

/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
|
| Plain axios PUT — not router.put() — since this must never trigger an
| Inertia page visit. On success, emit 'saved' with the server's fresh
| aggregate numbers for this room and close; the parent (Rooms/Index.vue)
| does the actual in-place row update.
*/

const saving = ref(false)
const saveError = ref(null)

function save() {
    if (pendingTransfers.value.length > 0) {
        const list = pendingTransfers.value
            .map(o => `${o.edp_code} (from ${o.claimed_by_room_code})`)
            .join(', ')

        if (!confirm(`This will move the following to ${room.room_code}: ${list}. Continue?`)) {
            return
        }
    }

    saving.value = true
    saveError.value = null

    axios.put(route('rooms.manage-subjects.update', room.id), {
        subject_offering_ids: Array.from(selected),
    })
        .then(response => {
            emit('saved', response.data)
        })
        .catch(error => {
            saveError.value = error.response?.data?.message ?? 'Something went wrong while saving. Please try again.'
        })
        .finally(() => {
            saving.value = false
        })
}

function close() {
    if (saving.value) return
    emit('close')
}
</script>

<template>

<!-- Overlay -->
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
    @click.self="close"
>
    <!-- Panel -->
    <div class="flex max-h-[90vh] w-full max-w-5xl flex-col rounded-2xl bg-[var(--card-bg)] shadow-xl">

        <!-- Header -->
        <div class="flex items-start justify-between gap-3 border-b border-[var(--card-border)] p-5">

            <div>
                <h2 class="text-xl font-bold text-[var(--text-primary)]">
                    {{ room.room_code }}
                </h2>
                <p class="mt-1 text-sm text-[var(--text-muted)]">
                    Manage Preferred Subject Offerings for this room.
                </p>

                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-1 text-xs font-medium text-[var(--text-secondary)]">
                        {{ room.room_type }}
                    </span>
                    <span
                        v-for="group in room.room_group_codes"
                        :key="group"
                        class="inline-flex rounded-full bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-600 dark:text-blue-400"
                    >
                        {{ group }}
                    </span>
                </div>
            </div>

            <button
                @click="close"
                type="button"
                class="rounded-lg p-1 text-[var(--text-muted)] hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)]"
                aria-label="Close"
            >
                ✕
            </button>

        </div>

        <!-- Body (scrolls; header/footer stay put) -->
        <div class="flex-1 overflow-y-auto p-5">

            <!-- No Active Term -->
            <div
                v-if="!activeAcademicTerm"
                class="rounded-2xl border border-[var(--card-border)] bg-[var(--page-bg)] p-8 text-center text-[var(--text-muted)]"
            >
                There is no active Academic Term right now, so there are no Subject Offerings to prefer. Activate an Academic Term first.
            </div>

            <template v-else>

                <!-- Utilization Card -->
                <div class="mb-5 rounded-2xl border border-[var(--card-border)] p-5">

                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-[var(--text-primary)]">
                                Preferred Hours
                            </h3>
                            <p class="mt-0.5 text-xs text-[var(--text-muted)]">
                                {{ activeAcademicTerm.display_name }} &middot; derived from checked subjects below, no schedule created
                            </p>
                        </div>

                        <div class="text-right">
                            <span
                                class="text-2xl font-bold"
                                :class="isOverCapacity ? 'text-red-500' : 'text-[var(--text-primary)]'"
                            >
                                {{ preferredHours }}
                            </span>
                            <span class="text-sm text-[var(--text-muted)]"> / {{ weeklyCapacityHours }} hrs</span>
                        </div>
                    </div>

                    <div class="h-2 w-full overflow-hidden rounded-full bg-[var(--page-bg)]">
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="isOverCapacity ? 'bg-red-500' : 'bg-[#D4A62A]'"
                            :style="{ width: utilizationPercent + '%' }"
                        />
                    </div>

                    <p v-if="isOverCapacity" class="mt-2 text-xs text-red-500">
                        Preferred hours exceed this room's weekly capacity. You can still save — this is only a preference, not a conflict.
                    </p>
                </div>

                <!-- Filters -->
                <div class="mb-5 rounded-2xl border border-[var(--card-border)] p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by EDP code, subject code, title, program, or section..."
                            class="w-full rounded-lg border-[var(--card-border)] bg-[var(--page-bg)] text-sm text-[var(--text-primary)] focus:border-[#D4A62A] focus:ring-[#D4A62A]/30 lg:flex-1"
                        />

                        <label class="flex items-center gap-2 whitespace-nowrap text-sm text-[var(--text-secondary)]">
                            <input
                                v-model="recommendedOnly"
                                type="checkbox"
                                class="rounded border-[var(--card-border)] text-[#D4A62A] focus:ring-[#D4A62A]/30"
                            />
                            Recommended for this room only
                        </label>
                    </div>
                </div>

                <!-- Offerings Table -->
                <div class="overflow-hidden rounded-2xl border border-[var(--card-border)]">
                    <table class="min-w-full">
                        <thead class="border-b border-[var(--card-border)] bg-[var(--page-bg)]">
                            <tr>
                                <th class="w-10 px-4 py-3 text-center text-[var(--text-secondary)]"></th>
                                <th class="px-4 py-3 text-left text-[var(--text-secondary)]">EDP Code</th>
                                <th class="px-4 py-3 text-left text-[var(--text-secondary)]">Subject</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Program</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Year</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Section</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Units</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Hours</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Classification</th>
                                <th class="px-4 py-3 text-center text-[var(--text-secondary)]">Room Type</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="offering in filteredOfferings"
                                :key="offering.id"
                                class="cursor-pointer border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                                @click="toggle(offering)"
                            >
                                <td class="px-4 py-3 text-center" @click.stop="toggle(offering)">
                                    <input
                                        type="checkbox"
                                        :checked="selected.has(offering.id)"
                                        @change="toggle(offering)"
                                        class="rounded border-[var(--card-border)] text-[#D4A62A] focus:ring-[#D4A62A]/30"
                                    />
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-[var(--text-primary)]">
                                    {{ offering.edp_code }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-medium text-[var(--text-primary)]">{{ offering.subject_code }}</div>
                                    <div class="text-xs text-[var(--text-muted)]">{{ offering.subject_title }}</div>
                                    <div
                                        v-if="offering.claimed_by_room_code"
                                        class="mt-1 inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-xs font-medium text-red-600 dark:text-red-400"
                                        :title="`Currently preferred by ${offering.claimed_by_room_code}. Checking this will move it to ${room.room_code} instead.`"
                                    >
                                        Currently in {{ offering.claimed_by_room_code }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                                    {{ offering.program_code ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                                    Year {{ offering.year_level }}
                                </td>

                                <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                                    {{ offering.section_code ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                                    {{ offering.units }}
                                </td>

                                <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                                    {{ offering.hours }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="offering.classification === 'Major'
                                            ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                            : 'bg-slate-500/10 text-slate-600 dark:text-slate-400'"
                                    >
                                        {{ offering.classification }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full bg-purple-500/10 px-2 py-0.5 text-xs font-medium text-purple-600 dark:text-purple-400">
                                        {{ offering.room_type }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="filteredOfferings.length === 0">
                                <td colspan="10" class="py-8 text-center text-[var(--text-muted)]">
                                    No matching Subject Offerings found for the active term.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </template>
        </div>

        <!-- Footer / Save Bar -->
        <div class="flex flex-col gap-2 border-t border-[var(--card-border)] p-4">
            <p v-if="saveError" class="text-sm text-red-500">
                {{ saveError }}
            </p>

            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-[var(--text-muted)]">
                    {{ selected.size }} subject(s) preferred &middot; {{ preferredHours }} hour(s) total
                </p>

                <div class="flex items-center gap-2">
                    <button
                        @click="close"
                        type="button"
                        :disabled="saving"
                        class="rounded-lg border border-[var(--card-border)] px-4 py-2 text-sm text-[var(--text-secondary)] transition-colors duration-150 hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)] disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        @click="save"
                        :disabled="saving || !activeAcademicTerm"
                        class="btn-save disabled:opacity-50"
                    >
                        {{ saving ? 'Saving…' : 'Save Preferences' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

</template>