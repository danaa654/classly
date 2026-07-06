<script setup>
import { ref, computed } from 'vue'
import { accentColor } from '@/Utils/roomAccentColor'

const props = defineProps({
    collapsed: { type: Boolean, default: false },
    offerings: { type: Array, default: () => [] },
    // Offerings already Scheduled/Completed/Archived for this term —
    // excluded from the tray by default since there's nothing left to
    // drag for them. Only shown when showScheduled is toggled on. See
    // MasterGridDataService::scheduledOfferings().
    scheduledOfferings: { type: Array, default: () => [] },
    collegeColors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:collapsed'])

function toggle() {
    emit('update:collapsed', !props.collapsed)
}

// Off by default — the sidebar's normal job is an unscheduled "drag-in"
// tray, and most of the time a Registrar doesn't want that cluttered
// with things that are already done. This is purely a local view
// preference, not persisted anywhere.
const showScheduled = ref(false)

// Unscheduled offerings first (these are draggable and are what the
// Registrar actually still needs to act on), then — only when the
// toggle is on — the already-Scheduled/Completed/Archived ones
// appended after, so they read as a distinct, secondary group rather
// than being shuffled in among the actionable ones.
const visibleOfferings = computed(() =>
    showScheduled.value
        ? [...props.offerings, ...props.scheduledOfferings]
        : props.offerings
)

const count = computed(() => visibleOfferings.value.length)
const scheduledCount = computed(() => props.scheduledOfferings.length)
</script>

<template>
    <aside
        class="subject-sidebar shrink-0 flex flex-col transition-all duration-200"
        style="background: var(--card-bg); border-right: 1px solid var(--card-border)"
        :class="collapsed ? 'w-10' : 'w-[220px]'"
    >
        <div class="flex items-center justify-between px-3 py-2.5 shrink-0" style="border-bottom: 1px solid var(--card-border)">
            <p v-if="!collapsed" class="text-[11px] font-black uppercase tracking-widest" style="color: var(--text-secondary)">
                Subjects <span style="color: var(--text-muted)">({{ count }})</span>
            </p>
            <button
                type="button"
                class="text-xs font-bold hover:opacity-70"
                style="color: var(--text-muted)"
                @click="toggle"
            >
                {{ collapsed ? '»' : '« Hide Subjects' }}
            </button>
        </div>

        <label
            v-if="!collapsed && scheduledCount > 0"
            class="flex items-center gap-1.5 px-3 py-1.5 shrink-0 text-[10px] font-semibold cursor-pointer select-none"
            style="border-bottom: 1px solid var(--card-border); color: var(--text-secondary)"
        >
            <input type="checkbox" v-model="showScheduled" class="rounded" />
            Show scheduled too ({{ scheduledCount }})
        </label>

        <div v-if="!collapsed" class="flex-1 overflow-y-auto custom-scrollbar-theme p-2 space-y-2">
            <p v-if="count === 0" class="text-xs text-center py-8" style="color: var(--text-muted)">
                No unscheduled Subject Offerings for this term.
            </p>

            <div
                v-for="offering in visibleOfferings"
                :key="offering.id"
                class="subject-card rounded-lg px-2.5 py-2 transition-all duration-150 ease-out"
                :class="offering.is_scheduled
                    ? 'opacity-60 cursor-default'
                    : 'cursor-grab active:cursor-grabbing hover:-translate-y-0.5 hover:shadow-md'"
                :style="{
                    background: 'var(--card-bg)',
                    border: '1px solid var(--card-border)',
                    borderLeft: '4px solid var(--subject-accent)',
                    '--subject-accent': accentColor(offering.college_code),
                }"
                :draggable="!offering.is_scheduled"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="font-black text-[12px]" style="color: var(--text-primary)">
                        {{ offering.subject_code }}
                        <span class="font-bold" style="color: var(--text-muted)">· {{ offering.section_code }}</span>
                    </p>
                    <span
                        v-if="offering.is_scheduled"
                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase"
                        style="background: var(--card-border); color: var(--text-muted)"
                    >
                        {{ offering.overall_status }}
                    </span>
                    <span
                        v-else
                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase text-white shrink-0"
                        :style="{ background: accentColor(offering.college_code) }"
                    >
                        {{ offering.college_code }}
                    </span>
                </div>

                <p class="text-[11px] font-semibold truncate" style="color: var(--text-secondary)">
                    {{ offering.descriptive_title }}
                </p>

                <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 mt-1.5 text-[10px]" style="color: var(--text-secondary)">
                    <span>{{ offering.program_code }} · Yr {{ offering.year_level }}</span>
                    <span>{{ offering.section_code }}</span>
                    <span>{{ offering.hours }} hrs</span>
                    <span>{{ offering.classification }}</span>
                    <span class="col-span-2 truncate">Faculty: <strong class="font-bold" style="color: var(--text-primary)">{{ offering.faculty_assigned ?? '—' }}</strong></span>
                    <span class="col-span-2 truncate">Pref. Room: <strong class="font-bold" style="color: var(--text-primary)">{{ offering.preferred_room_code ?? '—' }}</strong></span>
                    <span class="col-span-2 truncate">Pref. Faculty: <strong class="font-bold" style="color: var(--text-primary)">{{ offering.preferred_faculty_name ?? '—' }}</strong></span>
                    <span class="col-span-2">Room Type: <strong class="font-bold" style="color: var(--text-primary)">{{ offering.room_type ?? '—' }}</strong></span>
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.subject-card {
    border-left-width: 4px;
}
.subject-card:not(.cursor-default):hover {
    border-left-width: 6px;
    padding-left: calc(0.625rem - 2px);
}
</style>