<script setup>
import { computed } from 'vue'
import { collegeLabel } from '@/Utils/collegeColors'
import { accentColor } from '@/Utils/roomAccentColor'

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
</script>

<template>
    <aside
        class="room-sidebar shrink-0 flex flex-col transition-all duration-200"
        style="background: var(--card-bg)"
        :class="collapsed ? 'w-10' : 'w-[220px]'"
    >
        <div class="flex items-center justify-between px-3 py-2.5 shrink-0" style="border-bottom: 1px solid var(--card-border)">
            <button
                type="button"
                class="text-xs font-bold hover:opacity-70"
                style="color: var(--text-primary)"
                @click="toggle"
            >
                {{ collapsed ? '«' : 'Hide Rooms »' }}
            </button>
            <p v-if="!collapsed" class="text-[11px] font-black uppercase tracking-widest" style="color: var(--text-primary)">
                Rooms <span style="color: var(--text-muted)">({{ count }})</span>
            </p>
        </div>

        <div v-if="!collapsed" class="flex-1 overflow-y-auto custom-scrollbar-theme p-2 space-y-2">
            <p v-if="count === 0" class="text-xs text-center py-8" style="color: var(--text-muted)">
                No active rooms.
            </p>

            <button
                v-for="room in rooms"
                :key="room.id"
                type="button"
                class="room-card w-full text-left rounded-lg px-2.5 py-2 transition-all duration-150 ease-out hover:-translate-y-0.5 hover:shadow-md active:translate-y-0 active:shadow-sm"
                :class="isSelected(room) ? 'ring-2 ring-offset-1 ring-indigo-400' : ''"
                :style="{
                    background: 'var(--card-bg)',
                    border: '1px solid var(--card-border)',
                    borderLeft: '4px solid var(--room-accent)',
                    '--room-accent': accentColor(room.college_code, room.room_group_codes),
                }"
                @click="emit('select', room)"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="font-black text-[12px]" style="color: var(--text-primary)">
                        {{ room.room_code }}
                    </p>
                    <span
                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase text-white shrink-0"
                        :style="{ background: accentColor(room.college_code, room.room_group_codes) }"
                    >
                        {{ collegeLabel(room.college_code, room.room_group_codes) }}
                    </span>
                </div>

                <p class="text-[11px] font-semibold" style="color: var(--text-secondary)">
                    {{ room.building }}<span v-if="room.floor"> · Floor {{ room.floor }}</span>
                </p>

                <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 mt-1.5 text-[10px]" style="color: var(--text-secondary)">
                    <span>Capacity: <strong class="font-bold" style="color: var(--text-primary)">{{ room.capacity }}</strong></span>
                    <span>{{ room.room_type }}</span>
                    <span class="col-span-2 truncate">
                        Allowed:
                        <template v-if="room.room_group_codes?.length">
                            <template v-for="(code, i) in room.room_group_codes" :key="code">
                                <strong
                                    class="font-bold"
                                    :style="{ color: accentColor(null, [code]) }"
                                >{{ code }}</strong><span v-if="i < room.room_group_codes.length - 1">, </span>
                            </template>
                        </template>
                        <strong v-else class="font-bold" style="color: var(--text-primary)">—</strong>
                    </span>

                    <!-- Real Master Grid utilization — from the `schedules`
                         table (room_id + active term), i.e. classes that
                         are ACTUALLY scheduled here, not just preferenced. -->
                    <span class="col-span-2 flex items-center gap-1">
                        <span
                            class="w-1.5 h-1.5 rounded-full shrink-0"
                            :class="room.scheduled_count > 0 ? 'bg-emerald-500' : ''"
                            :style="room.scheduled_count > 0 ? '' : 'background: var(--text-muted)'"
                        ></span>
                        {{ room.scheduled_count > 0 ? `${room.scheduled_count} class(es) scheduled` : 'No classes scheduled' }}
                    </span>

                    <span class="col-span-2">
                        <strong class="font-bold" style="color: var(--text-primary)">{{ room.hours_used }}/{{ room.weekly_capacity_hours }}</strong> hrs
                        · {{ room.utilization_percent }}%
                    </span>

                    <div class="col-span-2 h-1.5 rounded-full overflow-hidden" style="background: var(--card-border)">
                        <div
                            class="h-full rounded-full transition-all"
                            :style="{
                                width: `${room.utilization_percent}%`,
                                background: room.utilization_percent > 0 ? '#10b981' : 'transparent',
                            }"
                        ></div>
                    </div>

                    <span class="col-span-2">Remaining: <strong class="font-bold" style="color: var(--text-primary)">{{ room.hours_remaining }} hrs</strong></span>
                </div>
            </button>
        </div>
    </aside>
</template>

<style scoped>
.room-card {
    border-left-width: 4px;
}
.room-card:hover {
    border-left-width: 6px;
    padding-left: calc(0.625rem - 2px);
}
</style>