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

            <h1 class="text-3xl font-bold">
                Curriculum Items
            </h1>

            <p class="text-gray-500 mt-1">
                Every Subject and Practicum/OJT item across all curriculums.
            </p>

        </div>

        <Link
            :href="route('curriculum-items.create')"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
        >
            + Add Item
        </Link>

    </div>

    <!-- Table -->

    <div class="bg-white rounded-lg shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-gray-100">

                <tr>

                    <th class="px-4 py-3 text-left">
                        Curriculum
                    </th>

                    <th class="px-4 py-3 text-left">
                        Type
                    </th>

                    <th class="px-4 py-3 text-left">
                        Subject Code
                    </th>

                    <th class="px-4 py-3 text-left">
                        Title
                    </th>

                    <th class="px-4 py-3 text-center">
                        Year
                    </th>

                    <th class="px-4 py-3 text-center">
                        Semester
                    </th>

                    <th class="px-4 py-3 text-center">
                        Status
                    </th>

                    <th class="px-4 py-3 text-center">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr
                    v-for="item in curriculumItems"
                    :key="item.id"
                    class="border-t hover:bg-gray-50"
                >

                    <td class="px-4 py-3">
                        <Link
                            :href="route('curriculums.items.manage', item.curriculum.id)"
                            class="text-blue-600 hover:underline"
                        >
                            {{ curriculumLabel(item.curriculum) }}
                        </Link>
                    </td>

                    <td class="px-4 py-3">

                        <span
                            v-if="item.item_type === 'Subject'"
                            class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs"
                        >
                            Subject
                        </span>

                        <span
                            v-else
                            class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs"
                        >
                            Practicum / OJT
                        </span>

                    </td>

                    <td class="px-4 py-3 font-semibold">
                        {{ displayCode(item) ?? '—' }}
                    </td>

                    <td class="px-4 py-3">
                        {{ displayTitle(item) }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ yearLabel(item.year_level) }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ semesterLabel(item.semester) }}
                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="item.active"
                            class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs"
                        >
                            Active
                        </span>

                        <span
                            v-else
                            class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs"
                        >
                            Inactive
                        </span>

                    </td>

                    <td class="px-4 py-3 text-center">

                        <Link
                            :href="route('curriculum-items.edit', item.id)"
                            class="text-blue-600 hover:underline mr-3"
                        >
                            Edit
                        </Link>

                        <button
                            @click="destroyCurriculumItem(item)"
                            class="text-red-600 hover:underline"
                        >
                            Delete
                        </button>

                    </td>

                </tr>

                <tr v-if="curriculumItems.length === 0">

                    <td
                        colspan="8"
                        class="text-center py-8 text-gray-500"
                    >
                        No curriculum items found.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

</template>