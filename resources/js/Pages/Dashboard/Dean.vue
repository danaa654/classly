<script setup>
import { computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import ChartCard from '@/Components/Dashboard/ChartCard.vue'
import ProgressBar from '@/Components/Dashboard/ProgressBar.vue'
import { collegeClasses, collegeLabel } from '@/Utils/collegeColors'

const props = defineProps({
    stats: { type: Object, required: true },
    charts: { type: Object, required: true },
    tables: { type: Object, required: true },
    // 'Dean' or 'OIC' — same dashboard/data for both, only the heading
    // text changes so an OIC user isn't shown someone else's title.
    roleLabel: { type: String, default: 'Dean' },
})

// A Dean/OIC dashboard is scoped to a single College — once
// DashboardService starts sending stats.college_code, every card on
// this page tints itself automatically. Until then this is simply
// null and every component below falls back to its neutral default,
// so nothing breaks in the meantime.
const college = computed(() => props.stats.college_code ?? null)
const palette = computed(() => (college.value ? collegeClasses(college.value) : null))
const collegeName = computed(() => (college.value ? collegeLabel(college.value) : null))
</script>

<template>
    <DashboardLayout>

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-[var(--text-primary)]">
                    {{ roleLabel }} Dashboard
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

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
            <StatCard label="Faculty" :value="stats.faculty" :college="college" />
            <StatCard label="Programs" :value="stats.programs" :college="college" />
            <StatCard label="Sections" :value="stats.sections" :college="college" />
            <StatCard label="Scheduled Subjects" :value="stats.scheduled_subjects" accent="text-emerald-500" :college="college" />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-6">
            <ChartCard
                title="Faculty Load"
                type="bar"
                :labels="charts.faculty_load.labels"
                :datasets="[{ label: 'Units', data: charts.faculty_load.data }]"
                :college="college"
            />
            <ProgressBar
                label="Department Scheduling Progress"
                :percent="charts.department_progress.percent"
                :college="college"
            />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

            <div
                class="bg-[var(--card-bg)] rounded-xl shadow p-4 border border-[var(--card-border)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                :class="palette ? palette.hoverBorder : 'hover:border-indigo-300 dark:hover:border-indigo-500'"
            >
                <h2 class="text-base font-semibold mb-3 text-[var(--text-primary)]">Faculty Needing Assignment</h2>
                <ul v-if="tables.faculty_needing_assignment.length" class="divide-y divide-[var(--card-border)] text-sm max-h-72 overflow-y-auto overflow-x-hidden pr-1 thin-scrollbar">
                    <li
                        v-for="f in tables.faculty_needing_assignment"
                        :key="f.id"
                        class="py-2 text-[var(--text-primary)] rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >
                        {{ f.first_name }} {{ f.last_name }}
                    </li>
                </ul>
                <p v-else class="text-sm text-[var(--text-secondary)]">Every active faculty member has an assignment.</p>
            </div>

            <div
                class="bg-[var(--card-bg)] rounded-xl shadow p-4 border border-[var(--card-border)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                :class="palette ? palette.hoverBorder : 'hover:border-indigo-300 dark:hover:border-indigo-500'"
            >
                <h2 class="text-base font-semibold mb-3 text-[var(--text-primary)]">Faculty Overload</h2>
                <ul v-if="tables.faculty_overload.length" class="divide-y divide-[var(--card-border)] text-sm max-h-72 overflow-y-auto overflow-x-hidden pr-1 thin-scrollbar">
                    <li
                        v-for="f in tables.faculty_overload"
                        :key="f.id"
                        class="py-2 flex justify-between gap-3 rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >
                        <span class="text-[var(--text-primary)] truncate">{{ f.first_name }} {{ f.last_name }}</span>
                        <span class="text-amber-500 font-medium shrink-0">{{ f.assigned_units }}/{{ f.max_units }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-[var(--text-secondary)]">No faculty are currently overloaded.</p>
            </div>

            <div
                class="bg-[var(--card-bg)] rounded-xl shadow p-4 border border-[var(--card-border)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                :class="palette ? palette.hoverBorder : 'hover:border-indigo-300 dark:hover:border-indigo-500'"
            >
                <h2 class="text-base font-semibold mb-3 text-[var(--text-primary)]">Subjects Without Faculty</h2>
                <ul v-if="tables.subjects_without_faculty.length" class="divide-y divide-[var(--card-border)] text-sm max-h-72 overflow-y-auto overflow-x-hidden pr-1 thin-scrollbar">
                    <li
                        v-for="s in tables.subjects_without_faculty"
                        :key="s.id"
                        class="py-2 text-[var(--text-primary)] rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >
                        {{ s.subject?.subject_code }} — {{ s.section?.section_code }}
                    </li>
                </ul>
                <p v-else class="text-sm text-[var(--text-secondary)]">Every offering has a faculty member assigned.</p>
            </div>

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