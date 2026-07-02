<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    subjects: Object,
    filters: Object,
})

/*
|--------------------------------------------------------------------------
| Filter State
|--------------------------------------------------------------------------
|
| Seeded from the `filters` prop the controller echoes back, so a page
| refresh (or a bookmarked/shared URL) restores the exact same filtered
| view instead of resetting to "all subjects".
|
| room_group here still filters against a single program at a time — it
| matches any subject that has that program among its (possibly several)
| assigned programs, via the forRoomGroup scope server-side.
|
*/

const form = reactive({
    search: props.filters.search ?? '',
    room_type: props.filters.room_type ?? '',
    classification: props.filters.classification ?? '',
    room_group: props.filters.room_group ?? '',
    status: props.filters.status ?? '',
})

/*
|--------------------------------------------------------------------------
| Push filter state to the server
|--------------------------------------------------------------------------
|
| - preserveState keeps local component state (and scroll target) intact
|   between requests instead of re-mounting the page.
| - preserveScroll stops Inertia from jumping back to the top on every
|   keystroke/selection.
| - replace avoids stacking a new browser history entry per keystroke,
|   while still keeping the final query string in the URL (bookmarkable).
| - Empty values are stripped so the URL stays clean (?search=IT instead
|   of ?search=IT&room_type=&classification=&...).
|
*/

function applyFilters(options = {}) {
    const query = {}

    for (const key in form) {
        if (form[key] !== '' && form[key] !== null) {
            query[key] = form[key]
        }
    }

    router.get(route('subjects.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['subjects', 'filters'],
        ...options,
    })
}

/*
|--------------------------------------------------------------------------
| Filter Query String
|--------------------------------------------------------------------------
|
| Same non-empty-value logic as applyFilters() above, serialized to a
| "?search=NON-" style string. Appended to the Edit link and Delete
| request below so the round trip back to this page (via the controller's
| redirect) lands on the same filtered view instead of resetting.
|
| Built from `form` here rather than read from window.location directly
| in the template — referencing the bare `window` global inside a
| template expression isn't reliably resolved by Vue's compiler and can
| throw ("Cannot read properties of undefined (reading 'location')").
| Computing it here also keeps it reactive to filter changes.
|
*/

const filterQueryString = computed(() => {
    const query = {}

    for (const key in form) {
        if (form[key] !== '' && form[key] !== null) {
            query[key] = form[key]
        }
    }

    const params = new URLSearchParams(query).toString()

    return params ? `?${params}` : ''
})

/*
|--------------------------------------------------------------------------
| Debounced Search
|--------------------------------------------------------------------------
|
| Dropdown filters apply immediately (see their @change handlers below);
| only the free-text search is debounced, since it fires on every
| keystroke and would otherwise flood the server with requests.
|
*/

let searchTimeout = null

watch(() => form.search, () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 350)
})

function resetFilters() {
    form.search = ''
    form.room_type = ''
    form.classification = ''
    form.room_group = ''
    form.status = ''

    applyFilters()
}

function destroySubject(subject) {
    if (!confirm(`Delete ${subject.subject_code} - ${subject.descriptive_title}? This cannot be undone.`)) {
        return
    }

    // Carries the current search/filter query string along so the
    // controller's post-delete redirect lands back on the same filtered
    // view instead of resetting to "all subjects".
    router.delete(route('subjects.destroy', subject.id) + filterQueryString.value, {
        preserveScroll: true,
    })
}
</script>

<template>

<Head title="Subjects" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold">
                Subjects
            </h1>

            <p class="text-gray-500 mt-1">
                Manage the master list of subjects.
            </p>

        </div>

        <Link
            :href="route('subjects.create')"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
        >
            + New Subject
        </Link>

    </div>

    <!-- Search & Filters -->

    <div class="bg-white rounded-lg shadow p-4 mb-6">

        <div class="flex flex-col lg:flex-row lg:items-center gap-3">

            <!-- Search -->

            <input
                v-model="form.search"
                type="text"
                placeholder="Search by subject code or title..."
                class="w-full lg:flex-1 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
            />

            <!-- Room Type -->

            <select
                v-model="form.room_type"
                @change="applyFilters()"
                class="w-full lg:w-44 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">All Room Types</option>
                <option value="Lecture">Lecture</option>
                <option value="Laboratory">Laboratory</option>
                <option value="Practicum">Practicum/OJT</option>
            </select>

            <!-- Classification -->

            <select
                v-model="form.classification"
                @change="applyFilters()"
                class="w-full lg:w-40 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">All Classifications</option>
                <option value="Major">Major</option>
                <option value="Minor">Minor</option>
            </select>

            <!-- Program (Room Group) -->

            <select
                v-model="form.room_group"
                @change="applyFilters()"
                class="w-full lg:w-40 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">All Programs</option>
                <option value="General">General</option>
                <option value="BSIT">BSIT</option>
                <option value="BSED">BSED</option>
                <option value="BSHM">BSHM</option>
                <option value="BSTM">BSTM</option>
                <option value="BSCRIM">BSCRIM</option>
            </select>

            <!-- Status -->

            <select
                v-model="form.status"
                @change="applyFilters()"
                class="w-full lg:w-36 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
            >
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

            <!-- Reset -->

            <button
                @click="resetFilters"
                type="button"
                class="w-full lg:w-auto px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 whitespace-nowrap"
            >
                Reset Filters
            </button>

        </div>

    </div>

    <!-- Table -->

    <div class="bg-white rounded-lg shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-gray-100">

                <tr>

                    <th class="px-4 py-3 text-left">
                        Subject Code
                    </th>

                    <th class="px-4 py-3 text-left">
                        Title
                    </th>

                    <th class="px-4 py-3 text-center">
                        Units
                    </th>

                    <th class="px-4 py-3 text-center">
                        Hours
                    </th>

                    <th class="px-4 py-3 text-center">
                        Classification
                    </th>

                    <th class="px-4 py-3 text-center">
                        Room Type
                    </th>

                    <th class="px-4 py-3 text-left">
                        Programs
                    </th>

                    <th class="px-4 py-3 text-center">
                        Practicum
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
                    v-for="subject in subjects.data"
                    :key="subject.id"
                    class="border-t hover:bg-gray-50"
                >

                    <td class="px-4 py-3 font-semibold">
                        {{ subject.subject_code }}
                    </td>

                    <td class="px-4 py-3">
                        {{ subject.descriptive_title }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ subject.units }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ subject.total_hours }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ subject.is_major ? 'Major' : 'Minor' }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ subject.required_room_type }}
                    </td>

                    <!--
                        A subject can now carry several programs
                        (e.g. Business Marketing -> BSHM + BSTM), so this
                        renders one badge per assigned program instead of a
                        single value.
                    -->
                    <td class="px-4 py-3">

                        <div
                            v-if="subject.room_group_codes && subject.room_group_codes.length"
                            class="flex flex-wrap gap-1"
                        >
                            <span
                                v-for="group in subject.room_group_codes"
                                :key="group"
                                class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs whitespace-nowrap"
                            >
                                {{ group }}
                            </span>
                        </div>

                        <span v-else class="text-gray-400 text-sm">
                            —
                        </span>

                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="subject.is_practicum"
                            class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-xs"
                        >
                            Yes
                        </span>

                        <span
                            v-else
                            class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs"
                        >
                            No
                        </span>

                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="subject.active"
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
                            :href="route('subjects.edit', subject.id) + filterQueryString"
                            class="text-blue-600 hover:underline mr-3"
                        >
                            Edit
                        </Link>

                        <button
                            @click="destroySubject(subject)"
                            class="text-red-600 hover:underline"
                        >
                            Delete
                        </button>

                    </td>

                </tr>

                <tr v-if="subjects.data.length === 0">

                    <td
                        colspan="10"
                        class="text-center py-8 text-gray-500"
                    >
                        No subjects found.
                    </td>

                </tr>

            </tbody>

        </table>

        <!-- Pagination -->

        <div
            v-if="subjects.links.length > 3"
            class="flex flex-wrap items-center justify-between gap-2 border-t px-4 py-3"
        >

            <p class="text-sm text-gray-500">
                Showing {{ subjects.from ?? 0 }}–{{ subjects.to ?? 0 }} of {{ subjects.total }} subjects
            </p>

            <div class="flex flex-wrap gap-1">

                <template v-for="(link, index) in subjects.links" :key="index">

                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        preserve-scroll
                        class="px-3 py-1.5 text-sm rounded-lg border"
                        :class="link.active
                            ? 'bg-blue-600 border-blue-600 text-white'
                            : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                        v-html="link.label"
                    />

                    <span
                        v-else
                        class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-300 cursor-not-allowed"
                        v-html="link.label"
                    />

                </template>

            </div>

        </div>

    </div>

</div>

</template>