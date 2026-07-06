<script setup>
import { ref, computed } from 'vue'
import { collegeClasses } from '@/Utils/collegeColors'

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
        class="subject-sidebar shrink-0 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex flex-col transition-all duration-200"
        :class="collapsed ? 'w-10' : 'w-[220px]'"
    >
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-slate-200 dark:border-slate-700 shrink-0">
            <p v-if="!collapsed" class="text-[11px] font-black uppercase tracking-widest text-slate-500">
                Subjects <span class="text-slate-400">({{ count }})</span>
            </p>
            <button
                type="button"
                class="text-xs font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                @click="toggle"
            >
                {{ collapsed ? '»' : '« Hide Subjects' }}
            </button>
        </div>

        <label
            v-if="!collapsed && scheduledCount > 0"
            class="flex items-center gap-1.5 px-3 py-1.5 border-b border-slate-200 dark:border-slate-700 shrink-0 text-[10px] font-semibold text-slate-500 cursor-pointer select-none"
        >
            <input type="checkbox" v-model="showScheduled" class="rounded" />
            Show scheduled too ({{ scheduledCount }})
        </label>

        <div v-if="!collapsed" class="flex-1 overflow-y-auto custom-scrollbar-theme p-2 space-y-2">
            <p v-if="count === 0" class="text-xs text-slate-400 text-center py-8">
                No unscheduled Subject Offerings for this term.
            </p>

            <div
                v-for="offering in visibleOfferings"
                :key="offering.id"
                class="subject-card rounded-lg border px-2.5 py-2"
                :class="[
                    collegeClasses(offering.college_code).bg,
                    collegeClasses(offering.college_code).border,
                    offering.is_scheduled ? 'opacity-60 cursor-default' : 'cursor-grab',
                ]"
                :draggable="!offering.is_scheduled"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="font-black text-[12px]" :class="collegeClasses(offering.college_code).text">
                        {{ offering.subject_code }}
                        <span class="font-bold text-slate-500">· {{ offering.section_code }}</span>
                    </p>
                    <span
                        v-if="offering.is_scheduled"
                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase border bg-slate-100 border-slate-300 text-slate-500 dark:bg-slate-700 dark:border-slate-600"
                    >
                        {{ offering.overall_status }}
                    </span>
                    <span
                        v-else
                        class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase border"
                        :class="collegeClasses(offering.college_code).badge"
                    >
                        {{ offering.college_code }}
                    </span>
                </div>

                <p class="text-[11px] font-semibold text-slate-700 truncate">
                    {{ offering.descriptive_title }}
                </p>

                <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 mt-1.5 text-[10px] text-slate-600">
                    <span>{{ offering.program_code }} · Yr {{ offering.year_level }}</span>
                    <span>{{ offering.section_code }}</span>
                    <span>{{ offering.hours }} hrs</span>
                    <span>{{ offering.classification }}</span>
                    <span class="col-span-2 truncate">Faculty: <strong class="font-bold text-slate-800">{{ offering.faculty_assigned ?? '—' }}</strong></span>
                    <span class="col-span-2 truncate">Pref. Room: <strong class="font-bold text-slate-800">{{ offering.preferred_room_code ?? '—' }}</strong></span>
                    <span class="col-span-2 truncate">Pref. Faculty: <strong class="font-bold text-slate-800">{{ offering.preferred_faculty_name ?? '—' }}</strong></span>
                    <span class="col-span-2">Room Type: <strong class="font-bold text-slate-800">{{ offering.room_type ?? '—' }}</strong></span>
                </div>
            </div>
        </div>
    </aside>
</template>