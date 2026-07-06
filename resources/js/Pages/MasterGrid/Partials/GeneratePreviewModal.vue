<script setup>
import { computed } from 'vue'

/**
 * Shown right after a successful Generate call — BEFORE anything
 * touches the real Master Grid. This is the "review, fix, then
 * commit" step: a Subject/Faculty/Room/Day/Time/Status table for
 * every offering the Greedy Scheduler touched (both placed and
 * failed).
 *
 * Any successfully-placed row can be clicked to open Edit Schedule
 * (see Index.vue's openEditModal(block, 'preview')) — the exact same
 * conflict-checked modal used on the live Master Grid, just scoped to
 * this in-memory preview instead of the saved schedule. Applying an
 * edit there updates the row in place, right here in this table;
 * nothing is written to the database until this modal's own Save
 * Changes is clicked.
 *
 * Nothing here is written anywhere until the user clicks "Save
 * Changes" — see Index.vue's applyGeneratedPreview(), which posts
 * straight to master-grid.save. Once that succeeds, the batch is
 * final: it is already on the Master Grid AND already in the
 * database, and each room's own timetable (see Room Sidebar -> click a
 * room) reflects it immediately.
 *
 * "Discard" throws the whole result away with zero side effects,
 * since nothing was ever written anywhere to produce this preview in
 * the first place.
 */
const props = defineProps({
    show: { type: Boolean, default: false },
    result: { type: Object, default: null }, // raw response from master-grid.generate
    saving: { type: Boolean, default: false },
    error: { type: String, default: null },
    // subject_offering_id => array of conflict objects (type, reason,
    // current, conflicting — see ScheduleValidationService::conflict()),
    // populated only when Save Changes itself came back 422. This is
    // what actually tells the Registrar WHICH of the "Success" rows was
    // the real problem — every row still reads Greedy-generation status
    // ('preview'/'unscheduled'/'skipped') on its own, which has nothing
    // to do with whether Save Schedule's own re-validation later
    // rejected it (e.g. it collided with something saved by someone
    // else a second ago). Without this, the generic banner above the
    // table was the only signal given, and it named zero rows.
    conflicts: { type: Object, default: null },
})

const emit = defineEmits(['save', 'discard', 'edit-block'])

const blocks = computed(() => props.result?.blocks ?? [])

const placedBlocks = computed(() => blocks.value.filter((b) => b.status === 'preview'))

const scheduledCount = computed(() => props.result?.scheduled_count ?? 0)
const unscheduledCount = computed(() => props.result?.unscheduled_count ?? 0)

/**
 * The specific conflict reason(s) Save Schedule rejected THIS block
 * for, or null if this block wasn't part of the rejected batch (or
 * nothing has failed yet). Keys of props.conflicts come back from
 * Laravel as strings even though subject_offering_id is numeric, so
 * this coerces both sides before comparing.
 */
function saveConflictsFor(block) {
    if (!props.conflicts) return null
    const entry = props.conflicts[block.subject_offering_id] ?? props.conflicts[String(block.subject_offering_id)]
    return entry && entry.length ? entry : null
}

function timeLabel(minutes) {
    if (minutes === null || minutes === undefined) return '—'
    const h24 = Math.floor(minutes / 60) % 24
    const m = minutes % 60
    const period = h24 >= 12 ? 'PM' : 'AM'
    const h12 = h24 % 12 === 0 ? 12 : h24 % 12
    return `${h12}:${String(m).padStart(2, '0')} ${period}`
}

function close() {
    if (props.saving) return
    emit('discard')
}

function save() {
    if (props.saving) return
    emit('save')
}

// Only successfully-placed rows can be edited — a 'skipped'/'unscheduled'
// row has no faculty/room/day/time to edit yet.
function rowClickable(block) {
    return block.status === 'preview'
}

function editBlock(block) {
    if (props.saving || !rowClickable(block)) return
    emit('edit-block', block)
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[88vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-start justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
                <div>
                    <h3 class="font-black text-slate-800 dark:text-slate-100">Schedule Preview</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Nothing has been saved yet. Click any row to adjust faculty, room, day, or time, then
                        Save Changes to commit it to the Master Grid, or Discard to throw it away.
                    </p>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-600" :disabled="saving" @click="close">✕</button>
            </div>

            <!-- Summary strip -->
            <div class="flex items-center gap-4 px-5 py-2.5 border-b border-slate-200 dark:border-slate-700 shrink-0 text-xs">
                <span class="font-black text-emerald-600 dark:text-emerald-400">{{ scheduledCount }} scheduled</span>
                <span v-if="unscheduledCount > 0" class="font-black text-red-600 dark:text-red-400">{{ unscheduledCount }} failed</span>
                <span v-else class="font-semibold text-slate-400">0 failed</span>
            </div>

            <!-- Error banner (e.g. a conflict surfaced only at save time) -->
            <div
                v-if="error"
                class="px-5 py-2 border-b border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300 shrink-0"
            >
                {{ error }}
            </div>

            <!-- Body: overview table. Each room already has its own timetable
                 on the Master Grid itself, so there is no separate per-room
                 drill-down here anymore — this table is the whole review. -->
            <div class="flex-1 min-h-0 overflow-auto custom-scrollbar-theme">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 bg-slate-50 dark:bg-slate-900 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="text-left px-4 py-2 border-b border-slate-200 dark:border-slate-700">Subject</th>
                            <th class="text-left px-4 py-2 border-b border-slate-200 dark:border-slate-700">Faculty</th>
                            <th class="text-left px-4 py-2 border-b border-slate-200 dark:border-slate-700">Room</th>
                            <th class="text-left px-4 py-2 border-b border-slate-200 dark:border-slate-700">Day</th>
                            <th class="text-left px-4 py-2 border-b border-slate-200 dark:border-slate-700">Time</th>
                            <th class="text-left px-4 py-2 border-b border-slate-200 dark:border-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="block in blocks"
                            :key="block.subject_offering_id"
                            class="border-b border-slate-100 dark:border-slate-700/60"
                            :class="[
                                block.status !== 'preview' || saveConflictsFor(block) ? 'bg-red-50/60 dark:bg-red-900/10' : '',
                                rowClickable(block) ? 'cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/40' : '',
                            ]"
                            :title="rowClickable(block) ? 'Click to edit faculty, room, day, or time' : ''"
                            @click="editBlock(block)"
                        >
                            <td class="px-4 py-2 font-bold text-slate-800 dark:text-slate-100">
                                {{ block.subject_code }}
                                <span class="block text-[10px] font-medium text-slate-400">{{ block.section_code }}</span>
                            </td>
                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">
                                <span v-if="block.faculty_name">
                                    {{ block.faculty_name }}
                                    <span v-if="block.faculty_source === 'auto'" class="ml-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-wide dark:bg-blue-500/10 dark:text-blue-300">
                                        Auto Assigned
                                    </span>
                                </span>
                                <span v-else class="text-slate-400 italic">Unassigned</span>
                            </td>
                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ block.room_code ?? '—' }}</td>
                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">{{ block.day ? block.day[0].toUpperCase() + block.day.slice(1) : '—' }}</td>
                            <td class="px-4 py-2 text-slate-600 dark:text-slate-300">
                                <template v-if="block.start_minutes !== null">
                                    {{ timeLabel(block.start_minutes) }} – {{ timeLabel(block.end_minutes) }}
                                </template>
                                <template v-else>—</template>
                            </td>
                            <td class="px-4 py-2">
                                <span
                                    v-if="block.status === 'preview' && !saveConflictsFor(block)"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    Success
                                </span>
                                <span v-else-if="block.status === 'preview'" class="inline-flex flex-col">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300 self-start">
                                        Conflict
                                    </span>
                                    <span
                                        v-for="(conflict, i) in saveConflictsFor(block)"
                                        :key="i"
                                        class="text-[10px] text-red-500 dark:text-red-400 mt-0.5"
                                    >
                                        {{ conflict.reason }}
                                    </span>
                                </span>
                                <span v-else class="inline-flex flex-col">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300 self-start">
                                        Failed
                                    </span>
                                    <span class="text-[10px] text-red-500 dark:text-red-400 mt-0.5">{{ block.reason }}</span>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-700 shrink-0">
                <p class="text-[11px] text-slate-400">
                    Click any successful row above to edit its faculty, room, day, or time before saving.
                    Once saved, the same edit is still available anytime by clicking the block on the Master Grid.
                </p>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-50"
                        :disabled="saving"
                        @click="close"
                    >
                        Discard
                    </button>
                    <button
                        type="button"
                        class="px-4 py-1.5 rounded-lg text-sm font-bold text-white transition bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-300 disabled:cursor-not-allowed"
                        :disabled="saving || placedBlocks.length === 0"
                        @click="save"
                    >
                        {{ saving ? 'Saving…' : `Save Changes (${placedBlocks.length})` }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>