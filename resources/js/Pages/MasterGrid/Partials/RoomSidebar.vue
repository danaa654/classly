<script setup>
import { computed } from 'vue'
import { collegeClasses, collegeLabel, programTextClass } from '@/Utils/collegeColors'

const props = defineProps({
    collapsed: { type: Boolean, default: false },
    rooms: { type: Array, default: () => [] },
    selectedRoom: { type: Object, default: null },
    collegeColors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:collapsed', 'select'])

function toggle() {
    emit('update:collapsed', !props.collapsed)
}

const count = computed(() => props.rooms.length)

function isSelected(room) {
    return props.selectedRoom?.id === room.id
}

// e.g. BSHM/BSTM render orange, BSCRIM renders violet — anything not
// in PROGRAM_COLLEGE_MAP just stays the card's normal black/slate.
function programColorClass(code) {
    return programTextClass(code) ?? 'text-black dark:!text-slate-100'
}
</script>

<template>
    <aside
        class="room-sidebar shrink-0 bg-white dark:bg-slate-800 flex flex-col transition-all duration-200"
        :class="collapsed ? 'w-10' : 'w-[220px]'"
    >
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-slate-200 dark:border-slate-700 shrink-0">
            <button
                type="button"
                class="text-xs font-bold text-black hover:text-slate-600 dark:text-slate-300 dark:hover:text-slate-200"
                @click="toggle"
            >
                {{ collapsed ? '«' : 'Hide Rooms »' }}
            </button>
            <p v-if="!collapsed" class="text-[11px] font-black uppercase tracking-widest text-black dark:text-slate-300">
                Rooms <span class="text-black dark:text-slate-400">({{ count }})</span>
            </p>
        </div>

        <div v-if="!collapsed" class="flex-1 overflow-y-auto custom-scrollbar-theme p-2 space-y-2">
            <p v-if="count === 0" class="text-xs text-black dark:text-slate-400 text-center py-8">
                No active rooms.
            </p>

            <button
                v-for="room in rooms"
                :key="room.id"
                type="button"
                class="room-card w-full text-left rounded-lg border px-2.5 py-2 transition-shadow dark:!bg-slate-800 dark:!border-slate-600"
                :class="[
                    collegeClasses(room.college_code, room.room_group_codes).bg,
                    collegeClasses(room.college_code, room.room_group_codes).border,
                    isSelected(room) ? 'ring-2 ring-offset-1 ring-indigo-400' : '',
                ]"
                @click="emit('select', room)"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="font-black text-[12px] text-black dark:!text-slate-100">
                        {{ room.room_code }}
                    </p>
                    <span
                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase border dark:!bg-slate-700 dark:!text-slate-200 dark:!border-slate-500"
                        :class="collegeClasses(room.college_code, room.room_group_codes).badge"
                    >
                        {{ collegeLabel(room.college_code, room.room_group_codes) }}
                    </span>
                </div>

                <p class="text-[11px] font-semibold text-black dark:!text-slate-200">
                    {{ room.building }}<span v-if="room.floor"> · Floor {{ room.floor }}</span>
                </p>

                <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 mt-1.5 text-[10px] text-black dark:!text-slate-300">
                    <span>Capacity: <strong class="font-bold text-black dark:!text-slate-100">{{ room.capacity }}</strong></span>
                    <span>{{ room.room_type }}</span>
                    <span class="col-span-2 truncate">
                        Allowed:
                        <template v-if="room.room_group_codes?.length">
                            <template v-for="(code, i) in room.room_group_codes" :key="code">
                                <strong class="font-bold" :class="programColorClass(code)">{{ code }}</strong><span v-if="i < room.room_group_codes.length - 1">, </span>
                            </template>
                        </template>
                        <strong v-else class="font-bold text-black dark:!text-slate-100">—</strong>
                    </span>

                    <!-- Real Master Grid utilization — from the `schedules`
                         table (room_id + active term), i.e. classes that
                         are ACTUALLY scheduled here, not just preferenced. -->
                    <span class="col-span-2 flex items-center gap-1">
                        <span
                            class="w-1.5 h-1.5 rounded-full shrink-0"
                            :class="room.scheduled_count > 0 ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
                        ></span>
                        {{ room.scheduled_count > 0 ? `${room.scheduled_count} class(es) scheduled` : 'No classes scheduled' }}
                    </span>

                    <span class="col-span-2">
                        <strong class="font-bold text-black dark:!text-slate-100">{{ room.hours_used }}/{{ room.weekly_capacity_hours }}</strong> hrs
                        · {{ room.utilization_percent }}%
                    </span>

                    <div class="col-span-2 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="room.utilization_percent > 0 ? 'bg-emerald-500' : ''"
                            :style="{ width: `${room.utilization_percent}%` }"
                        ></div>
                    </div>

                    <span class="col-span-2">Remaining: <strong class="font-bold text-black dark:!text-slate-100">{{ room.hours_remaining }} hrs</strong></span>
                </div>
            </button>
        </div>
    </aside>
</template>