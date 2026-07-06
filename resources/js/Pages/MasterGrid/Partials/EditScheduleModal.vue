<script setup>
import { computed, reactive, watch } from 'vue'
import { useTimetableGrid } from '@/Composables/useTimetableGrid'

const props = defineProps({
    show: { type: Boolean, default: false },
    block: { type: Object, default: null }, // the schedule block being edited
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
})

const emit = defineEmits(['close', 'field-changed', 'apply', 'apply-faculty', 'apply-room', 'apply-time'])

const { workingDays, timeRows } = useTimetableGrid(computed(() => props.academicTerm))

// Only real (non-lunch) rows give valid start/end options.
const slotOptions = computed(() => timeRows.value.filter((r) => r.type === 'slot'))

const draft = reactive({
    faculty_id: null,
    room_id: null,
    day: null,
    start_minutes: null,
    end_minutes: null,
})

watch(() => props.block, (block) => {
    if (!block) return
    draft.faculty_id = block.faculty_id
    draft.room_id = block.room_id
    draft.day = block.day
    draft.start_minutes = block.start_minutes
    draft.end_minutes = block.end_minutes
}, { immediate: true })

function emitChange() {
    emit('field-changed', { ...draft })
}

const hasConflicts = computed(() => props.conflicts.length > 0)

function apply() {
    if (hasConflicts.value || props.validating) return
    emit('apply', { ...draft })
}

function close() {
    emit('close')
}

function facultyLabel(f) {
    return [f.first_name, f.last_name].filter(Boolean).join(' ')
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
                <h3 class="font-black text-slate-800 dark:text-slate-100">Edit Schedule</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="close">✕</button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                Changes are checked instantly. Nothing is saved until you click Save Schedule on the Master Grid.
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
                            <select v-model.number="draft.faculty_id" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30" @change="emitChange">
                                <option :value="null">Unassigned</option>
                                <option v-for="f in eligibleFaculties" :key="f.id" :value="f.id">{{ facultyLabel(f) }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Room</label>
                            <select v-model.number="draft.room_id" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30" @change="emitChange">
                                <option v-for="r in eligibleRooms" :key="r.id" :value="r.id">{{ r.room_code }} ({{ r.room_type }})</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Day</label>
                                <select v-model="draft.day" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30" @change="emitChange">
                                    <option v-for="d in workingDays" :key="d.field" :value="d.field">{{ d.label }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">Start</label>
                                <select v-model.number="draft.start_minutes" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30" @change="emitChange">
                                    <option v-for="s in slotOptions" :key="'s' + s.startMinutes" :value="s.startMinutes">
                                        {{ s.label.split(' - ')[0] }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 mb-1">End</label>
                                <select v-model.number="draft.end_minutes" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm px-2 py-1.5 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30" @change="emitChange">
                                    <option v-for="s in slotOptions" :key="'e' + s.endMinutes" :value="s.endMinutes">
                                        {{ s.label.split(' - ')[1] }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Inline validation state -->
                    <div v-if="validating" class="mt-4 text-xs text-slate-400 font-semibold">
                        Checking for conflicts…
                    </div>

                    <div v-else-if="hasConflicts" class="mt-4 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 p-3">
                        <p class="text-xs font-black text-red-700 dark:text-red-300 mb-1">⚠ Conflicts found — see suggestions {{ hasConflicts ? '\u2192' : 'below' }}</p>
                        <ul class="text-[11px] text-red-700 dark:text-red-300 list-disc list-inside space-y-0.5">
                            <li v-for="(c, i) in conflicts" :key="i">{{ c.reason }}</li>
                        </ul>
                    </div>

                    <div v-else-if="warnings.length" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-3">
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
                <div v-if="hasConflicts && recommendations" class="mt-4 md:mt-0 space-y-3 md:border-l md:border-slate-200 md:dark:border-slate-700 md:pl-6">
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
                            @click="emit('apply-time', { day: t.day, start_minutes: t.start_minutes, end_minutes: t.end_minutes })"
                        >
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ t.label }}</p>
                        </button>
                    </div>

                    <p v-if="!recommendations.faculty?.length && !recommendations.rooms?.length && !recommendations.times?.length" class="text-xs text-slate-400 italic">
                        No automatic alternatives found — try a different manual change.
                    </p>
                </div>

            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" class="btn-neutral" @click="close">
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="hasConflicts || validating"
                    class="btn-save"
                    @click="apply"
                >
                    Apply Changes
                </button>
            </div>
        </div>
    </div>
</template>