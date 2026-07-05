<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import ChartCard from '@/Components/Dashboard/ChartCard.vue'
import ProgressBar from '@/Components/Dashboard/ProgressBar.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    stats: { type: Object, required: true },
    conflicts: { type: Object, required: true },
    charts: { type: Object, required: true },
    widgets: { type: Object, required: true },
})

// NOTE: 'master-grid.generate' and 'master-grid.save'/'validate-block'
// are POST-only action endpoints in web.php, not pages — Generate
// Schedule should link to the Master Grid workspace itself, where that
// POST is triggered by a button. 'conflicts.index' has no route yet.
const quickActions = [
    { label: 'Master Grid', href: route('master-grid.index') },
    { label: 'Faculty Loading', href: route('teaching-assignments.index') },
    { label: 'Subject Offerings', href: route('subject-offerings.index') },
    { label: 'Generate Schedule', href: route('master-grid.index') },
    { label: 'Conflict Report', href: '#' },
]
</script>

<template>
    <DashboardLayout>

        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Registrar Dashboard
            </h1>
            <p class="text-sm text-[var(--text-secondary)]">
                Working Term: <span class="font-semibold text-[var(--text-primary)]">{{ stats.working_term ?? 'None set' }}</span>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-6">
            <StatCard label="Subject Offerings" :value="stats.subject_offerings" />
            <StatCard label="Scheduled Subjects" :value="stats.scheduled_subjects" accent="text-emerald-500" />
            <StatCard label="Remaining Subjects" :value="stats.remaining_subjects" accent="text-amber-500" />
            <StatCard label="Faculty Assigned" :value="stats.faculty_assigned" />
            <StatCard label="Rooms Assigned" :value="stats.rooms_assigned" />
        </div>

        <div class="mb-8">
            <ProgressBar label="Scheduling Completion" :percent="stats.completion_percent" />
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
            <ChartCard
                title="Faculty Load Distribution"
                type="bar"
                :labels="charts.faculty_load.labels"
                :datasets="[{ label: 'Units', data: charts.faculty_load.data }]"
            />
            <ChartCard
                title="Room Utilization (hrs / week)"
                type="bar"
                :labels="charts.room_utilization.labels"
                :datasets="[{ label: 'Hours Used', data: charts.room_utilization.data }]"
            />
            <ChartCard
                title="Schedule Completion"
                type="doughnut"
                :labels="charts.schedule_completion.labels"
                :datasets="[{ data: charts.schedule_completion.data }]"
            />
            <ChartCard
                title="Subjects by Department"
                type="bar"
                :labels="charts.subjects_by_department.labels"
                :datasets="[{ label: 'Offerings', data: charts.subjects_by_department.data }]"
            />
        </div>

        <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] mb-8 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-500">
            <h2 class="text-lg font-semibold mb-4 text-[var(--text-primary)]">Conflict Summary</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ conflicts.faculty }}</p>
                    <p class="text-[var(--text-secondary)]">Faculty</p>
                </div>
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ conflicts.room }}</p>
                    <p class="text-[var(--text-secondary)]">Room</p>
                </div>
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ conflicts.section }}</p>
                    <p class="text-[var(--text-secondary)]">Section</p>
                </div>
                <div class="text-center rounded-lg py-2 transition-colors duration-150 hover:bg-[var(--page-bg)]">
                    <p class="text-2xl font-bold text-[var(--text-primary)]">{{ conflicts.time }}</p>
                    <p class="text-[var(--text-secondary)]">Time</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

            <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-500">
                <h2 class="text-lg font-semibold mb-4 text-[var(--text-primary)]">Unscheduled Subjects</h2>
                <ul v-if="widgets.unscheduled_subjects.length" class="divide-y divide-[var(--card-border)] max-h-80 overflow-y-auto overflow-x-hidden pr-1 thin-scrollbar">
                    <li
                        v-for="item in widgets.unscheduled_subjects"
                        :key="item.id"
                        class="py-2 text-sm flex justify-between gap-3 rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >
                        <span class="text-[var(--text-primary)] truncate">{{ item.subject }} — {{ item.title }}</span>
                        <span class="text-[var(--text-secondary)] shrink-0">{{ item.section }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-[var(--text-secondary)]">All subject offerings are scheduled.</p>
            </div>

            <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-500">
                <h2 class="text-lg font-semibold mb-4 text-[var(--text-primary)]">Recent Activity</h2>
                <ul v-if="widgets.recent_activity.length" class="divide-y divide-[var(--card-border)] max-h-80 overflow-y-auto overflow-x-hidden pr-1 thin-scrollbar">
                    <li
                        v-for="(item, i) in widgets.recent_activity"
                        :key="i"
                        class="py-2 text-sm text-[var(--text-secondary)] rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)] truncate"
                    >
                        {{ item.description }} — {{ item.created_at }}
                    </li>
                </ul>
                <p v-else class="text-sm text-[var(--text-secondary)]">No recent activity recorded.</p>
            </div>

        </div>

        <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-all duration-300 hover:shadow-lg">
            <h2 class="text-lg font-semibold mb-4 text-[var(--text-primary)]">Quick Actions</h2>
            <div class="flex flex-wrap gap-3">
                <Link
                    v-for="action in quickActions"
                    :key="action.label"
                    :href="action.href"
                    class="px-4 py-2 rounded-lg bg-indigo-500 text-white text-sm font-medium
                           transition-all duration-200 ease-out
                           hover:bg-indigo-600 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-95"
                >
                    {{ action.label }}
                </Link>
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