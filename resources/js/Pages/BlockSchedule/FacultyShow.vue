<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useTimetableGrid } from '@/Composables/useTimetableGrid'

const props = defineProps({
    department: Object,
    faculty: Object,
    assignments: Array,
    academicTerm: Object,
})

const viewMode = ref('list') // 'list' | 'overview'

function formatTime(minutes) {
    if (minutes === null || minutes === undefined) return null
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    const suffix = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 === 0 ? 12 : h % 12
    return `${h12}:${String(m).padStart(2, '0')} ${suffix}`
}

function timeRange(row) {
    if (!row.day) return 'Unscheduled'
    return `${row.day} · ${formatTime(row.start_minutes)} – ${formatTime(row.end_minutes)}`
}

/*
|--------------------------------------------------------------------------
| Schedule Overview — weekly timetable grid
|--------------------------------------------------------------------------
|
| Reuses the same useTimetableGrid() composable Master Grid's Timetable.vue
| is built on, purely for `workingDays` (which days the Academic Term has
| enabled, in Mon->Sun order) — everything below is positioned with plain
| minutes-since-midnight math rather than useTimetableGrid's row-per-
| interval model, since a read-only single-faculty overview only needs to
| draw a handful of blocks, not a full editable slot grid.
*/
const { workingDays } = useTimetableGrid(computed(() => props.academicTerm))

const PX_PER_MINUTE = 1.1

function toMinutes(hhmm) {
    if (!hhmm) return null
    const [h, m] = hhmm.split(':').map(Number)
    return (h * 60) + m
}

const schoolStart = computed(() => toMinutes(props.academicTerm?.school_start_time) ?? 480)
const schoolEnd = computed(() => toMinutes(props.academicTerm?.school_end_time) ?? 1170)
const lunchStart = computed(() => toMinutes(props.academicTerm?.lunch_start_time))
const lunchEnd = computed(() => toMinutes(props.academicTerm?.lunch_end_time))

const gridHeight = computed(() => Math.max((schoolEnd.value - schoolStart.value) * PX_PER_MINUTE, 0))

const hourMarks = computed(() => {
    const marks = []
    let cursor = schoolStart.value

    while (cursor <= schoolEnd.value) {
        marks.push({ minutes: cursor, label: formatTime(cursor) })
        cursor += 60
    }

    return marks
})

const lunchStyle = computed(() => {
    if (lunchStart.value == null || lunchEnd.value == null) return null

    return {
        top: `${(lunchStart.value - schoolStart.value) * PX_PER_MINUTE}px`,
        height: `${(lunchEnd.value - lunchStart.value) * PX_PER_MINUTE}px`,
    }
})

// Scheduled rows only — the overview grid has no way to place a subject
// with no day/time yet (see the note rendered below the grid for those).
const scheduledAssignments = computed(() => props.assignments.filter((a) => a.day))
const unscheduledCount = computed(() => props.assignments.length - scheduledAssignments.value.length)

function assignmentsForDay(dayField) {
    return scheduledAssignments.value.filter((a) => a.day?.toLowerCase() === dayField)
}

function blockStyle(assignment) {
    const top = (assignment.start_minutes - schoolStart.value) * PX_PER_MINUTE
    const height = Math.max((assignment.end_minutes - assignment.start_minutes) * PX_PER_MINUTE, 26)

    return { top: `${top}px`, height: `${height}px` }
}
</script>

<template>
    <AppLayout>
        <div class="p-8">
            <Link
                :href="department.is_general ? route('block-schedule.faculty.general') : route('block-schedule.faculty.list', department.id)"
                class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-slate-500 hover:text-slate-800"
            >
                &lsaquo; Back to {{ department.code }} Faculty
            </Link>

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ faculty.full_name }}</h1>
                    <p class="text-sm font-medium text-slate-400">
                        {{ department.name }}
                        <span v-if="academicTerm"> — {{ academicTerm.display_name }}</span>
                    </p>
                </div>

                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                    <button
                        type="button"
                        @click="viewMode = 'list'"
                        class="rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition"
                        :class="viewMode === 'list' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'"
                    >
                        List
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'overview'"
                        class="rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition"
                        :class="viewMode === 'overview' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'"
                    >
                        Overview
                    </button>
                </div>
            </div>

            <!-- LIST VIEW -->
            <div v-if="viewMode === 'list'" class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide">EDP Code</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide">Subject</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide">Block</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide">Units</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide">Day &amp; Time</th>
                            <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide">Room</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="row in assignments" :key="row.id" class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-sm font-semibold text-slate-700">
                                {{ row.edp_code ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-900">{{ row.subject_code }}</p>
                                <p class="text-xs text-slate-400">{{ row.descriptive_title }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm font-medium text-slate-700">
                                {{ row.section_code ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-sm font-medium text-slate-700">
                                {{ row.units ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    :class="row.day ? 'text-slate-700' : 'italic text-slate-400'"
                                    class="text-sm font-medium"
                                >
                                    {{ timeRange(row) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm font-medium text-slate-700">
                                {{ row.room_code ?? 'TBA' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- OVERVIEW: weekly timetable grid -->
            <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex" style="min-width: 820px">
                    <!-- Time gutter -->
                    <div class="w-16 flex-shrink-0 border-r border-slate-100">
                        <div class="h-10 border-b border-slate-200"></div>
                        <div class="relative" :style="{ height: gridHeight + 'px' }">
                            <div
                                v-for="mark in hourMarks"
                                :key="mark.minutes"
                                class="absolute right-2 -translate-y-1/2 text-[10px] font-medium text-slate-400"
                                :style="{ top: (mark.minutes - schoolStart) * PX_PER_MINUTE + 'px' }"
                            >
                                {{ mark.label }}
                            </div>
                        </div>
                    </div>

                    <!-- Day columns -->
                    <div
                        v-for="day in workingDays"
                        :key="day.field"
                        class="flex-1 border-r border-slate-100 last:border-r-0"
                    >
                        <div class="flex h-10 items-center justify-center border-b border-slate-200 text-xs font-bold uppercase tracking-wide text-slate-600">
                            {{ day.label }}
                        </div>

                        <div class="relative" :style="{ height: gridHeight + 'px' }">
                            <!-- Hour gridlines -->
                            <div
                                v-for="mark in hourMarks"
                                :key="'line-' + mark.minutes"
                                class="absolute left-0 right-0 border-t border-slate-100"
                                :style="{ top: (mark.minutes - schoolStart) * PX_PER_MINUTE + 'px' }"
                            ></div>

                            <!-- Lunch band -->
                            <div
                                v-if="lunchStyle"
                                class="absolute left-0 right-0 bg-slate-50"
                                :style="lunchStyle"
                            ></div>

                            <!-- Scheduled blocks -->
                            <div
                                v-for="a in assignmentsForDay(day.field)"
                                :key="a.id"
                                class="absolute left-1 right-1 overflow-hidden rounded-lg border border-indigo-300 bg-indigo-100 px-2 py-1 shadow-sm"
                                :style="blockStyle(a)"
                            >
                                <p class="truncate text-[11px] font-bold text-indigo-900">{{ a.subject_code }}</p>
                                <p class="truncate text-[10px] text-indigo-700">
                                    {{ a.section_code ?? '—' }} · {{ a.room_code ?? 'TBA' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="viewMode === 'overview' && unscheduledCount > 0" class="mt-3 text-xs italic text-slate-400">
                {{ unscheduledCount }} subject(s) don't have a day/time yet and aren't shown above — switch to List view to see them.
            </p>

            <p v-if="!assignments.length" class="mt-10 text-center text-sm text-slate-400">
                No Teaching Assignments for this faculty member yet.
            </p>
        </div>
    </AppLayout>
</template>

<style>
/* Sidebar/Topbar come from AppLayout and are visible during
   normal browsing (same chrome as every other page). They're only
   suppressed here for the *printed* output, so the "Print" button above
   still produces a clean Subject/Block/Day/Room report. */
@media print {
    #app-sidebar,
    #sidebar-overlay {
        display: none !important;
    }
}
</style>