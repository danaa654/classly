<script setup>
import { computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import { collegeClasses, collegeLabel } from '@/Utils/collegeColors'

const props = defineProps({
    stats: { type: Object, required: true },
})

// Same college_code convention as the Dean dashboard — currently
// optional/absent, falls back to neutral indigo until the backend
// sends it.
const college = computed(() => props.stats.college_code ?? null)
const palette = computed(() => (college.value ? collegeClasses(college.value) : null))
const collegeName = computed(() => (college.value ? collegeLabel(college.value) : null))
</script>

<template>
    <DashboardLayout>

        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                    Assistant Dean Dashboard
                </h1>
                <span
                    v-if="collegeName"
                    class="text-xs font-semibold px-2.5 py-1 rounded-full border"
                    :class="palette.badge"
                >
                    {{ collegeName }}
                </span>
            </div>
            <p class="text-sm text-[var(--text-secondary)]">
                Working Term: <span class="font-semibold text-[var(--text-primary)]">{{ stats.working_term ?? 'None set' }}</span>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <StatCard label="Faculty Assignments" :value="stats.faculty_assignments" accent="text-emerald-500" :college="college" />
            <StatCard label="Pending Assignments" :value="stats.pending_assignments" accent="text-amber-500" :college="college" />
        </div>

        <div
            class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] mb-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
            :class="palette ? palette.hoverBorder : 'hover:border-indigo-300 dark:hover:border-indigo-500'"
        >
            <h2 class="text-lg font-semibold mb-4 text-[var(--text-primary)]">Conflicts</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ stats.conflicts.faculty }}</p>
                    <p class="text-[var(--text-secondary)]">Faculty</p>
                </div>
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ stats.conflicts.room }}</p>
                    <p class="text-[var(--text-secondary)]">Room</p>
                </div>
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ stats.conflicts.section }}</p>
                    <p class="text-[var(--text-secondary)]">Section</p>
                </div>
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ stats.conflicts.time }}</p>
                    <p class="text-[var(--text-secondary)]">Time</p>
                </div>
            </div>
        </div>

        <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-all duration-300 hover:shadow-lg">
            <h2 class="text-lg font-semibold mb-4 text-[var(--text-primary)]">Recent Updates</h2>
            <ul v-if="stats.recent_activity.length" class="divide-y divide-[var(--card-border)] text-sm max-h-80 overflow-y-auto overflow-x-hidden pr-1 thin-scrollbar">
                <li
                    v-for="(item, i) in stats.recent_activity"
                    :key="i"
                    class="py-2 text-[var(--text-secondary)] rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)] truncate"
                >
                    {{ item.description }} — {{ item.created_at }}
                </li>
            </ul>
            <p v-else class="text-sm text-[var(--text-secondary)]">No recent updates.</p>
        </div>

    </DashboardLayout>
</template>

<style scoped>
.thin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: var(--card-border) transparent;
}
.thin-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.thin-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.thin-scrollbar::-webkit-scrollbar-thumb {
    background-color: var(--card-border);
    border-radius: 9999px;
}
.thin-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: var(--text-secondary);
}
</style>