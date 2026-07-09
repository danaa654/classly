<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    subjects: Object,
    filters: Object,
    roomGroupOptions: Array,
})

/*
|--------------------------------------------------------------------------
| Write Access
|--------------------------------------------------------------------------
|
| Subjects is a shared master list (GenEd/NSTP subjects span multiple
| programs at once), so Create/Edit/Delete is centralized to
| Admin/Registrar server-side — see SubjectController::middleware().
| This mirrors that same check purely so Dean/Assistant Dean/OIC (who
| can still view this list) don't see action buttons that would just
| 403 if clicked; the controller guard above is the real source of
| truth, this is only a UI convenience.
*/

const canManageSubjects = computed(() => {
    const roles = usePage().props.auth?.user?.roles ?? []

    return roles.includes('Admin') || roles.includes('Registrar')
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

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Subjects
            </h1>

            <p class="text-[var(--text-muted)] mt-1">
                Manage the master list of subjects.
            </p>

        </div>

        <Link
            v-if="canManageSubjects"
            :href="route('subjects.create')"
            class="btn-save"
        >
            + New Subject
        </Link>

    </div>

    <!-- Search & Filters -->

    <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-4 mb-6">

        <div class="flex flex-col lg:flex-row lg:items-center gap-3">

            <!-- Search -->

            <input
                v-model="form.search"
                type="text"
                placeholder="Search by subject code or title..."
                class="w-full lg:flex-1 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            />

            <!-- Room Type -->

            <select
                v-model="form.room_type"
                @change="applyFilters()"
                class="w-full lg:w-44 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
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
                class="w-full lg:w-40 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            >
                <option value="">All Classifications</option>
                <option value="Major">Major</option>
                <option value="Minor">Minor</option>
            </select>

            <!-- Program (Room Group) -->

            <select
                v-model="form.room_group"
                @change="applyFilters()"
                class="w-full lg:w-40 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            >
                <option value="">All Programs</option>
                <option
                    v-for="option in props.roomGroupOptions"
                    :key="option"
                    :value="option"
                >
                    {{ option }}
                </option>
            </select>

            <!-- Status -->

            <select
                v-model="form.status"
                @change="applyFilters()"
                class="w-full lg:w-36 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            >
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

            <!-- Reset -->

            <button
                @click="resetFilters"
                type="button"
                class="w-full lg:w-auto px-4 py-2 text-sm rounded-lg border border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] whitespace-nowrap"
            >
                Reset Filters
            </button>

        </div>

    </div>

    <!-- Table -->

    <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-[var(--page-bg)]">

                <tr>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Subject Code
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Title
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Units
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Hours
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Classification
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Room Type
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Programs
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Practicum
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
                    v-for="subject in subjects.data"
                    :key="subject.id"
                    class="border-t hover:bg-[var(--page-bg)]"
                >

                    <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">
                        {{ subject.subject_code }}
                    </td>

                    <td class="px-4 py-3 text-[var(--text-primary)]">
                        {{ subject.descriptive_title }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        {{ subject.units }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        {{ subject.total_hours }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        {{ subject.is_major ? 'Major' : 'Minor' }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
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
                                class="inline-flex px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-medium whitespace-nowrap"
                            >
                                {{ group }}
                            </span>
                        </div>

                        <span v-else class="text-[var(--text-muted)] text-sm">
                            —
                        </span>

                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="subject.is_practicum"
                            class="inline-flex px-2 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-medium"
                        >
                            Yes
                        </span>

                        <span
                            v-else
                            class="inline-flex px-2 py-1 rounded-full bg-[var(--card-border)]/40 text-[var(--text-muted)] text-xs font-medium"
                        >
                            No
                        </span>

                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="subject.active"
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

                    <td class="px-4 py-3 text-center">

                        <template v-if="canManageSubjects">

                            <Link
                                :href="route('subjects.edit', subject.id) + filterQueryString"
                                class="btn-edit"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroySubject(subject)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                        </template>

                        <span v-else class="text-[var(--text-muted)] text-xs">
                            View only
                        </span>

                    </td>

                </tr>

                <tr v-if="subjects.data.length === 0">

                    <td
                        colspan="10"
                        class="text-center py-8 text-[var(--text-muted)]"
                    >
                        No subjects found.
                    </td>

                </tr>

            </tbody>

        </table>

        <!-- Pagination -->

        <div
            v-if="subjects.links.length > 3"
            class="flex flex-wrap items-center justify-between gap-2 border-t border-[var(--card-border)] px-4 py-3"
        >

            <p class="text-sm text-[var(--text-muted)]">
                Showing {{ subjects.from ?? 0 }}–{{ subjects.to ?? 0 }} of {{ subjects.total }} subjects
            </p>

            <div class="flex flex-wrap gap-1">

                <template v-for="(link, index) in subjects.links" :key="index">

                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        preserve-scroll
                        class="px-3 py-1.5 text-sm rounded-lg border transition-colors duration-150"
                        :class="link.active
                            ? 'bg-[#D4A62A] border-[#D4A62A] text-[#0B1220] font-semibold'
                            : 'border-[var(--card-border)] text-[var(--text-secondary)] hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)]'"
                        v-html="link.label"
                    />

                    <span
                        v-else
                        class="px-3 py-1.5 text-sm rounded-lg border border-[var(--card-border)] text-[var(--text-muted)] cursor-not-allowed"
                        v-html="link.label"
                    />

                </template>

            </div>

        </div>

    </div>

</div>

</template>