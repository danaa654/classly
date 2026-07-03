<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    curriculumItems: Array,
})

const YEAR_LABELS = {
    1: '1st Year',
    2: '2nd Year',
    3: '3rd Year',
    4: '4th Year',
    
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

function curriculumLabel(curriculum) {
    return `${curriculum.code} — ${curriculum.name}`
}

// Falls back to computing these client-side in case the backend
// resource hasn't appended display_title / display_code. Subject and
// Practicum/OJT items both carry a subject_id now, so both resolve
// through the linked subject; `title` is kept only as a fallback for
// any legacy free-text OJT rows.
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

function destroyCurriculumItem(item) {
    if (!confirm(`Remove ${itemLabel(item)} from ${item.curriculum.code}? This cannot be undone.`)) {
        return
    }

    router.delete(route('curriculum-items.destroy', item.id), {
        preserveScroll: true,
    })
}
</script>

<template>

<Head title="Curriculum Items" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Curriculum Items
            </h1>

            <p class="text-[var(--text-muted)] mt-1">
                Every Subject and Practicum/OJT item across all curriculums.
            </p>

        </div>

        <Link
            :href="route('curriculum-items.create')"
            class="btn-save"
        >
            + Add Item
        </Link>

    </div>

    <!-- Table -->

    <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">

                <tr>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Curriculum
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Type
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Subject Code
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Title
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Year
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Semester
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Status
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr
                    v-for="item in curriculumItems"
                    :key="item.id"
                    class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                >

                    <td class="px-4 py-3">
                        <Link
                            :href="route('curriculums.items.manage', item.curriculum.id)"
                            class="text-blue-500 hover:underline"
                        >
                            {{ curriculumLabel(item.curriculum) }}
                        </Link>
                    </td>

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
                        {{ yearLabel(item.year_level) }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        {{ semesterLabel(item.semester) }}
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
                                @click="destroyCurriculumItem(item)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                        </div>

                    </td>

                </tr>

                <tr v-if="curriculumItems.length === 0">

                    <td
                        colspan="8"
                        class="text-center py-8 text-[var(--text-muted)]"
                    >
                        No curriculum items found.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

</template>