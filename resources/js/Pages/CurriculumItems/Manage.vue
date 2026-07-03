<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    curriculum: Object,
    curriculumItems: Array,
})

const YEAR_LABELS = {
    1: 'First Year',
    2: 'Second Year',
    3: 'Third Year',
    4: 'Fourth Year',
    
}

const SEMESTER_LABELS = {
    1: 'First Semester',
    2: 'Second Semester',
    3: 'Summer',
}

function yearLabel(year) {
    return YEAR_LABELS[year] ?? `Year ${year}`
}

function semesterLabel(semester) {
    return SEMESTER_LABELS[semester] ?? '—'
}

// Subject and Practicum/OJT items both carry a subject_id now, so both
// resolve through the linked subject; `title` is kept only as a
// fallback for any legacy free-text OJT rows.
function displayTitle(item) {
    if (item.display_title !== undefined) return item.display_title
    return item.subject?.descriptive_title ?? item.title
}

function displayCode(item) {
    if (item.display_code !== undefined) return item.display_code
    return item.subject?.subject_code ?? null
}

function itemLabel(item) {
    return item.subject?.subject_code ?? item.title
}

/*
|--------------------------------------------------------------------------
| Group items by year_level, then by semester
|--------------------------------------------------------------------------
|
| Grouping is done client-side from the flat list the controller
| sends. This keeps the backend response simple (no nested key type
| issues going through JSON) and keeps the grouping/sorting logic in
| one place.
*/

const grouped = computed(() => {
    const years = {}

    for (const item of props.curriculumItems) {
        const year = item.year_level
        const semester = item.semester

        years[year] ??= {}
        years[year][semester] ??= []
        years[year][semester].push(item)
    }

    return years
})

const yearKeys = computed(() => {
    return Object.keys(grouped.value)
        .map(Number)
        .sort((a, b) => a - b)
})

function semesterKeysFor(year) {
    return Object.keys(grouped.value[year])
        .map(Number)
        .sort((a, b) => a - b)
}

// Units only make sense for Subject items — OJT items contribute
// hours instead, tallied separately below.
function totalUnits(items) {
    return items
        .filter((item) => item.item_type === 'Subject')
        .reduce((sum, item) => sum + (item.subject?.units ?? 0), 0)
}

function totalOjtHours(items) {
    return items
        .filter((item) => item.item_type === 'OJT')
        .reduce((sum, item) => sum + (item.ojt_hours ?? 0), 0)
}

const curriculumLabel = computed(() => {
    return `${props.curriculum.code} — ${props.curriculum.name}`
})

function removeItem(item) {
    if (!confirm(`Remove ${itemLabel(item)} from this curriculum? This cannot be undone.`)) {
        return
    }

    router.delete(route('curriculum-items.destroy', item.id), {
        preserveScroll: true,
    })
}
</script>

<template>

<Head :title="`Manage Items — ${curriculum.code}`" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Manage Items
            </h1>

            <p class="text-[var(--text-muted)] mt-1">
                {{ curriculumLabel }}
            </p>

        </div>

        <div class="flex items-center gap-3">

            <Link
                :href="route('curriculums.index')"
                class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:underline transition-colors duration-150"
            >
                &larr; Back to Curriculums
            </Link>

            <Link
                :href="route('curriculum-items.create', { curriculum_id: curriculum.id })"
                class="btn-save"
            >
                + Add Item
            </Link>

        </div>

    </div>

    <!-- Empty State -->

    <div
        v-if="curriculumItems.length === 0"
        class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-8 text-center text-[var(--text-muted)]"
    >
        No items have been added to this curriculum yet.
    </div>

    <!-- Grouped Prospectus -->

    <div v-else class="space-y-8">

        <div
            v-for="year in yearKeys"
            :key="year"
            class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden"
        >

            <div class="bg-slate-900 text-white px-5 py-3">
                <h2 class="text-lg font-bold">
                    {{ yearLabel(year) }}
                </h2>
            </div>

            <div
                v-for="semester in semesterKeysFor(year)"
                :key="semester"
                class="border-t border-[var(--card-border)]"
            >

                <div class="flex justify-between items-center px-5 py-3 bg-[var(--page-bg)]">

                    <h3 class="font-semibold text-[var(--text-primary)]">
                        {{ semesterLabel(semester) }}
                    </h3>

                    <span class="text-sm text-[var(--text-muted)] space-x-3">
                        <span>{{ totalUnits(grouped[year][semester]) }} units</span>
                        <span v-if="totalOjtHours(grouped[year][semester]) > 0">
                            {{ totalOjtHours(grouped[year][semester]) }} Practicum hours
                        </span>
                    </span>

                </div>

                <table class="min-w-full">

                    <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)] text-xs uppercase text-[var(--text-secondary)]">

                        <tr>

                            <th class="px-4 py-2 text-left">
                                Type
                            </th>

                            <th class="px-4 py-2 text-left">
                                Code
                            </th>

                            <th class="px-4 py-2 text-left">
                                Title
                            </th>

                            <th class="px-4 py-2 text-center">
                                Units / Hours
                            </th>

                            <th class="px-4 py-2 text-center">
                                Status
                            </th>

                            <th class="px-4 py-2 text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr
                            v-for="item in grouped[year][semester]"
                            :key="item.id"
                            class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                        >

                            <td class="px-4 py-3">

                                <span
                                    v-if="item.item_type === 'Subject'"
                                    class="inline-flex px-2 py-1 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-medium"
                                >
                                    Subject
                                </span>

                                <span
                                    v-else
                                    class="inline-flex px-2 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-medium"
                                >
                                    Practicum / OJT
                                </span>

                            </td>

                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">
                                {{ displayCode(item) ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-[var(--text-primary)]">
                                {{ displayTitle(item) }}
                            </td>

                            <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                                {{ item.item_type === 'Subject' ? item.subject?.units : `${item.ojt_hours} hrs` }}
                            </td>

                            <td class="px-4 py-3 text-center">

                                <span
                                    v-if="item.active"
                                    class="inline-flex px-2 py-1 rounded-full bg-green-500/10 text-green-600 dark:text-green-400 text-xs font-medium"
                                >
                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex px-2 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 text-xs font-medium"
                                >
                                    Inactive
                                </span>

                            </td>

                            <td class="px-4 py-3 text-center whitespace-nowrap">

                                <div class="flex justify-center gap-2">

                                    <Link
                                        :href="route('curriculum-items.edit', item.id)"
                                        class="btn-edit"
                                    >
                                        Edit
                                    </Link>

                                    <button
                                        @click="removeItem(item)"
                                        class="btn-delete"
                                    >
                                        Remove
                                    </button>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</template>