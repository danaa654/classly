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

            <h1 class="text-3xl font-bold">
                Manage Items
            </h1>

            <p class="text-gray-500 mt-1">
                {{ curriculumLabel }}
            </p>

        </div>

        <div class="flex items-center gap-3">

            <Link
                :href="route('curriculums.index')"
                class="text-gray-600 hover:underline"
            >
                &larr; Back to Curriculums
            </Link>

            <Link
                :href="route('curriculum-items.create', { curriculum_id: curriculum.id })"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
            >
                + Add Item
            </Link>

        </div>

    </div>

    <!-- Empty State -->

    <div
        v-if="curriculumItems.length === 0"
        class="bg-white rounded-lg shadow p-8 text-center text-gray-500"
    >
        No items have been added to this curriculum yet.
    </div>

    <!-- Grouped Prospectus -->

    <div v-else class="space-y-8">

        <div
            v-for="year in yearKeys"
            :key="year"
            class="bg-white rounded-lg shadow overflow-hidden"
        >

            <div class="bg-slate-900 text-white px-5 py-3">
                <h2 class="text-lg font-bold">
                    {{ yearLabel(year) }}
                </h2>
            </div>

            <div
                v-for="semester in semesterKeysFor(year)"
                :key="semester"
                class="border-t"
            >

                <div class="flex justify-between items-center px-5 py-3 bg-gray-50">

                    <h3 class="font-semibold text-gray-700">
                        {{ semesterLabel(semester) }}
                    </h3>

                    <span class="text-sm text-gray-500 space-x-3">
                        <span>{{ totalUnits(grouped[year][semester]) }} units</span>
                        <span v-if="totalOjtHours(grouped[year][semester]) > 0">
                            {{ totalOjtHours(grouped[year][semester]) }} Practicum hours
                        </span>
                    </span>

                </div>

                <table class="min-w-full">

                    <thead class="bg-gray-100 text-xs uppercase text-gray-500">

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
                            class="border-t hover:bg-gray-50"
                        >

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
                                {{ item.item_type === 'Subject' ? item.subject?.units : `${item.ojt_hours} hrs` }}
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
                                    @click="removeItem(item)"
                                    class="text-red-600 hover:underline"
                                >
                                    Remove
                                </button>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</template>