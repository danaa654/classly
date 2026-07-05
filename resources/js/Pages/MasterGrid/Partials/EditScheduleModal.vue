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
})

const emit = defineEmits(['close', 'field-changed', 'apply'])

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
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-lg p-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-black text-slate-800 dark:text-slate-100">Edit Schedule</h3>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="close">✕</button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                Changes are checked instantly. Nothing is saved until you click Save Schedule on the Master Grid.
            </p>

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
                <p class="text-xs font-black text-red-700 dark:text-red-300 mb-1">⚠ Conflicts found — see suggestions below</p>
                <ul class="text-[11px] text-red-700 dark:text-red-300 list-disc list-inside space-y-0.5">
                    <li v-for="(c, i) in conflicts" :key="i">{{ c.reason }}</li>
                </ul>
            </div>

            <div v-else-if="warnings.length" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-3">
                <ul class="text-[11px] text-amber-700 dark:text-amber-300 list-disc list-inside space-y-0.5">
                    <li v-for="(w, i) in warnings" :key="i">{{ w.message }}</li>
                </ul>
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