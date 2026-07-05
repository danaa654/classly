<script setup>
import { computed } from 'vue'
import { useTimetableGrid } from '@/Composables/useTimetableGrid'
import { collegeClasses } from '@/Utils/collegeColors'

const props = defineProps({
    academicTerm: { type: Object, default: null },
    selectedRoom: { type: Object, default: null },
    // Future Greedy output: [{ subject_offering_id, day, start_minutes,
    //   end_minutes, room_id, subject_code, section_code, faculty_name,
    //   college_code }]. Empty today — nothing generates schedules yet.
    scheduledEvents: { type: Array, default: () => [] },
    collegeColors: { type: Object, default: () => ({}) },
    // Phase 2 — Interactive Schedule Review: subject_offering_ids that
    // currently have a conflict (set right before Save Schedule when a
    // block still fails validation), so they can be highlighted red
    // without the caller having to touch each block's own data.
    conflictingIds: { type: Array, default: () => [] },
    editable: { type: Boolean, default: false },
})

const emit = defineEmits(['edit-block'])

function isConflicting(event) {
    return props.conflictingIds.includes(event.subject_offering_id)
}

const { workingDays, timeRows } = useTimetableGrid(computed(() => props.academicTerm))

/**
 * "Room View" — when a room is selected, only events assigned to
 * that room are shown. With no Greedy Scheduler yet, scheduledEvents
 * is always empty, but the filtering logic is ready for when it
 * isn't.
 */
const visibleEvents = computed(() => {
    if (!props.selectedRoom) return props.scheduledEvents
    return props.scheduledEvents.filter((event) => event.room_id === props.selectedRoom.id)
})

function eventsFor(dayField, row) {
    if (row.type === 'lunch') return []
    return visibleEvents.value.filter(
        (event) => event.day === dayField
            && event.start_minutes < row.endMinutes
            && event.end_minutes > row.startMinutes
    )
}

// "8:00 AM - 8:30 AM" -> ["8:00 AM", "8:30 AM"] — stacked on two lines in
// the Time column instead of one long line, so it can't run wide enough
// to creep into the Monday column next to it.
function splitLabel(label) {
    return label.split(' - ')
}
</script>

<template>
    <div class="timetable-wrapper p-4">
        <div v-if="timeRows.length === 0" class="p-10 text-center text-sm text-slate-400">
            This Academic Term has no valid school hours configured yet.
        </div>

        <div v-else class="min-w-[660px]">
            <table class="w-full border-separate border-spacing-0 select-none table-fixed">
                <colgroup>
                    <col class="timetable-time-col" />
                    <col v-for="day in workingDays" :key="'col-' + day.field" />
                </colgroup>

                <thead>
                    <tr>
                        <th class="timetable-time-col sticky left-0 bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-3 py-2 text-[11px] font-black uppercase tracking-wider text-black dark:text-slate-300 z-10">
                            Time
                        </th>
                        <th
                            v-for="day in workingDays"
                            :key="day.field"
                            class="border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 px-2 py-2 text-[11px] font-black uppercase tracking-wider text-black dark:text-slate-300"
                        >
                            {{ day.label }}
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="row in timeRows" :key="row.key">
                        <!-- Lunch break spans every column -->
                        <template v-if="row.type === 'lunch'">
                            <td
                                class="timetable-time-col sticky left-0 bg-slate-50 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-[10px] font-semibold text-black dark:text-slate-400"
                            >
                                <div class="flex flex-col leading-tight">
                                    <span>{{ splitLabel(row.label)[0] }}</span>
                                    <span>{{ splitLabel(row.label)[1] }}</span>
                                </div>
                            </td>
                            <td
                                :colspan="workingDays.length"
                                class="border border-slate-300 dark:border-slate-600 bg-slate-100/70 dark:bg-slate-800/40 text-center py-1.5"
                            >
                                <span class="text-[11px] font-black uppercase tracking-[0.2em] text-black dark:text-slate-400">
                                    Lunch Break
                                </span>
                            </td>
                        </template>

                        <!-- Regular time slot row -->
                        <template v-else>
                            <td
                                class="timetable-time-col sticky left-0 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 px-2 py-1 text-[10px] font-semibold text-black dark:text-slate-400"
                            >
                                <div class="flex flex-col leading-tight">
                                    <span>{{ splitLabel(row.label)[0] }}</span>
                                    <span>{{ splitLabel(row.label)[1] }}</span>
                                </div>
                            </td>

                            <td
                                v-for="day in workingDays"
                                :key="day.field + row.key"
                                class="timetable-cell border border-slate-300 dark:border-slate-600 align-top p-0.5 h-[28px]"
                            >
                                <div
                                    v-for="event in eventsFor(day.field, row)"
                                    :key="event.subject_offering_id"
                                    class="rounded-md border px-1.5 py-0.5 text-[10px] font-semibold leading-tight"
                                    :class="[
                                        collegeClasses(event.college_code).block,
                                        editable ? 'cursor-pointer hover:ring-2 hover:ring-blue-400' : 'cursor-default',
                                        isConflicting(event) ? '!border-red-500 !bg-red-100 dark:!bg-red-900/40 ring-2 ring-red-500' : '',
                                    ]"
                                    @click="editable && emit('edit-block', event)"
                                >
                                    <p class="font-black">{{ event.subject_code }} · {{ event.section_code }}</p>
                                    <p class="truncate font-semibold">{{ event.faculty_name ?? 'Unassigned' }}</p>
                                    <p class="truncate font-semibold">{{ event.room_code ?? 'Unassigned' }}</p>
                                </div>
                            </td>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<style scoped>
/* table-layout: fixed (via .table-fixed) means every unsized <col> in
   the colgroup splits the remaining width evenly — so the day columns
   stretch to fill whatever room is left when a sidebar collapses, and
   shrink back down when it reopens. Only this fixed time column keeps
   an explicit width; the wrapper's min-w-[660px] is the floor that
   stops columns from getting too cramped, kicking in the wrapper's own
   overflow-auto scroll instead. */
.timetable-time-col {
    width: 72px;
}
</style>