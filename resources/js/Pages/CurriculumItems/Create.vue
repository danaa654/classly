<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    curricula: Array,
    subjects: Array,
    practicumSubjects: Array,
    selectedCurriculumId: Number,
    assignedSubjectIds: Array,
})

const form = useForm({
    curriculum_id: props.selectedCurriculumId ?? '',
    item_type: 'Subject',
    subject_ids: [],
    subject_id: '',
    ojt_hours: '',
    year_level: 1,
    semester: 1,
    active: true,
})

// When arriving from a curriculum's Manage Items page, the curriculum
// is already known — lock the dropdown so the user can't accidentally
// attach the item somewhere else.
const curriculumLocked = computed(() => !!props.selectedCurriculumId)

const isSubject = computed(() => form.item_type === 'Subject')
const isOjt = computed(() => form.item_type === 'OJT')

// Switching item type clears whichever fields belonged to the previous
// type, so a half-filled Practicum/OJT form can't accidentally submit
// alongside chosen subjects (or vice versa).
watch(() => form.item_type, () => {
    form.subject_ids = []
    form.subject_id = ''
    form.ojt_hours = ''

    // 5th Year isn't a valid placement for Practicum/OJT items — see the
    // Year Level select below.
    if (form.item_type === 'OJT' && form.year_level === 5) {
        form.year_level = 4
    }
})

const assignedSet = computed(() => new Set(props.assignedSubjectIds ?? []))

const alreadyAssignedSubjects = computed(() => {
    return props.subjects.filter((subject) => assignedSet.value.has(subject.id))
})

/*
|--------------------------------------------------------------------------
| Subject Checklist — Search / Classification Filter (local UI state)
|--------------------------------------------------------------------------
|
| These live outside the Inertia form on purpose: they only control what
| the checklist *displays*, never what gets submitted, and they must
| survive checkbox clicks untouched (Goal 7 — search/filter state is
| never reset just because the user selected something).
|
*/

const subjectSearch = ref('')
const classificationFilter = ref('All') // 'All' | 'Minor' | 'Major'

/*
|--------------------------------------------------------------------------
| Goal 1 — Automatic Program Filtering
|--------------------------------------------------------------------------
|
| A curriculum belongs to a Program (e.g. BSIT). The checklist should
| only ever offer:
|   - every Minor subject (General Education — shared across programs)
|   - Major subjects whose required_room_group matches the curriculum's
|     program code
|
| The mapping is derived entirely from data already sent to this page
| (curricula[].program.code and subjects[].required_room_group), so this
| runs instantly on the client with no extra request.
|
*/

const selectedCurriculum = computed(() => {
    return props.curricula.find((curriculum) => curriculum.id === form.curriculum_id) ?? null
})

const curriculumProgramCode = computed(() => {
    return selectedCurriculum.value?.program?.code ?? null
})

const availableSubjects = computed(() => {
    return props.subjects.filter((subject) => !assignedSet.value.has(subject.id))
})

const programFilteredSubjects = computed(() => {
    if (!form.curriculum_id) {
        return []
    }

    return availableSubjects.value.filter((subject) => {
        // Minors are General Education — every curriculum can use them.
        if (!subject.is_major) return true

        // Majors are scoped to the curriculum's own program.
        return subject.required_room_group === curriculumProgramCode.value
    })
})

// Dropping a selected subject the moment it stops being valid for the
// chosen curriculum (e.g. the user switches curriculum after picking
// some subjects) keeps the form from ever submitting a mismatched pair.
watch(() => form.curriculum_id, () => {
    const allowedIds = new Set(programFilteredSubjects.value.map((subject) => subject.id))
    form.subject_ids = form.subject_ids.filter((id) => allowedIds.has(id))

    const allowedPracticumIds = new Set(filteredPracticumSubjects.value.map((subject) => subject.id))
    if (form.subject_id && !allowedPracticumIds.has(form.subject_id)) {
        form.subject_id = ''
    }
})

/*
|--------------------------------------------------------------------------
| Practicum / OJT — Practicum Subject Filtering
|--------------------------------------------------------------------------
|
| The `practicumSubjects` prop is already scoped to is_practicum = true
| at the controller level (a Practicum subject is never mixed into the
| `subjects` prop used above), so the only filtering left to do here is
| the same program-matching rule used for Major subjects: only offer
| subjects whose required_room_group matches the curriculum's own
| program code.
|
*/

const availablePracticumSubjects = computed(() => {
    return props.practicumSubjects.filter((subject) => !assignedSet.value.has(subject.id))
})

const filteredPracticumSubjects = computed(() => {
    if (!form.curriculum_id) {
        return []
    }

    return availablePracticumSubjects.value.filter((subject) => {
        return subject.required_room_group === curriculumProgramCode.value
    })
})

const selectedPracticumSubject = computed(() => {
    return filteredPracticumSubjects.value.find((subject) => subject.id === form.subject_id) ?? null
})

/*
|--------------------------------------------------------------------------
| Goal 2 & 3 — Search + Classification Filter
|--------------------------------------------------------------------------
*/

const searchedSubjects = computed(() => {
    const term = subjectSearch.value.trim().toLowerCase()

    if (!term) {
        return programFilteredSubjects.value
    }

    return programFilteredSubjects.value.filter((subject) => {
        return subject.subject_code.toLowerCase().includes(term)
            || subject.descriptive_title.toLowerCase().includes(term)
    })
})

const visibleSubjects = computed(() => {
    if (classificationFilter.value === 'Minor') {
        return searchedSubjects.value.filter((subject) => !subject.is_major)
    }

    if (classificationFilter.value === 'Major') {
        return searchedSubjects.value.filter((subject) => subject.is_major)
    }

    return searchedSubjects.value
})

/*
|--------------------------------------------------------------------------
| Goal 9 (enhancement) — Group + Sort
|--------------------------------------------------------------------------
|
| Minors ("General Education") first, then Majors — each group sorted
| alphabetically by subject code, so a long prospectus is easy to scan.
|
*/

function sortByCode(subjects) {
    return [...subjects].sort((a, b) => a.subject_code.localeCompare(b.subject_code))
}

const visibleMinorSubjects = computed(() => {
    return sortByCode(visibleSubjects.value.filter((subject) => !subject.is_major))
})

const visibleMajorSubjects = computed(() => {
    return sortByCode(visibleSubjects.value.filter((subject) => subject.is_major))
})

/*
|--------------------------------------------------------------------------
| Goal 5 — Selection Counter
|--------------------------------------------------------------------------
|
| "shown" reflects the current search/filter view; "selected" reflects
| the full selection made so far (not just what's currently visible), so
| narrowing the list never makes it look like earlier picks were lost.
|
*/

const shownCount = computed(() => visibleSubjects.value.length)
const selectedCount = computed(() => form.subject_ids.length)

/*
|--------------------------------------------------------------------------
| Goal 6 — Select All (visible subjects only)
|--------------------------------------------------------------------------
*/

const allVisibleSelected = computed(() => {
    return visibleSubjects.value.length > 0
        && visibleSubjects.value.every((subject) => form.subject_ids.includes(subject.id))
})

function toggleSelectAll() {
    const visibleIds = visibleSubjects.value.map((subject) => subject.id)

    if (allVisibleSelected.value) {
        // Deselect only what's currently visible — selections made
        // under a different search/filter view are left untouched.
        form.subject_ids = form.subject_ids.filter((id) => !visibleIds.includes(id))
        return
    }

    const merged = new Set(form.subject_ids)
    visibleIds.forEach((id) => merged.add(id))
    form.subject_ids = Array.from(merged)
}

function resetSubjectFilters() {
    subjectSearch.value = ''
    classificationFilter.value = 'All'
}

const backHref = computed(() => {
    return curriculumLocked.value
        ? route('curriculums.items.manage', props.selectedCurriculumId)
        : route('curriculum-items.index')
})

function curriculumLabel(curriculum) {
    return `${curriculum.code} — ${curriculum.name}`
}

function submit() {
    form.post(route('curriculum-items.store'))
}
</script>

<template>

<Head title="Add Curriculum Item" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold">
                Add Curriculum Item
            </h1>

            <p class="text-gray-500 mt-1">
                Attach one or more Subjects, or a single Practicum/OJT item, to a curriculum's prospectus.
            </p>

        </div>

        <Link
            :href="backHref"
            class="text-gray-600 hover:underline"
        >
            &larr; Back
        </Link>

    </div>

    <!-- Form -->

    <form
        @submit.prevent="submit"
        class="bg-white rounded-lg shadow p-6 space-y-6"
    >

        <!-- Item Type -->

        <div>

            <label class="block text-sm font-medium text-gray-700 mb-1">
                Item Type
            </label>

            <select
                v-model="form.item_type"
                class="w-full border-gray-300 rounded-lg md:w-1/3"
            >
                <option value="Subject">Subject</option>
                <option value="OJT">Practicum / OJT</option>
            </select>

            <p v-if="form.errors.item_type" class="text-red-600 text-sm mt-1">
                {{ form.errors.item_type }}
            </p>

        </div>

        <!-- Curriculum / Year Level / Semester -->

        <div class="grid grid-cols-3 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Curriculum
                </label>

                <select
                    v-model="form.curriculum_id"
                    :disabled="curriculumLocked"
                    class="w-full border-gray-300 rounded-lg disabled:bg-gray-100 disabled:text-gray-500"
                >
                    <option value="" disabled>Select curriculum</option>

                    <option
                        v-for="curriculum in curricula"
                        :key="curriculum.id"
                        :value="curriculum.id"
                    >
                        {{ curriculumLabel(curriculum) }}
                    </option>
                </select>

                <p v-if="form.errors.curriculum_id" class="text-red-600 text-sm mt-1">
                    {{ form.errors.curriculum_id }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Year Level
                </label>

                <select
                    v-model.number="form.year_level"
                    class="w-full border-gray-300 rounded-lg"
                >
                    <option :value="1">1st Year</option>
                    <option :value="2">2nd Year</option>
                    <option :value="3">3rd Year</option>
                    <option :value="4">4th Year</option>
                    
                </select>

                <p v-if="form.errors.year_level" class="text-red-600 text-sm mt-1">
                    {{ form.errors.year_level }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Semester
                </label>

                <select
                    v-model.number="form.semester"
                    class="w-full border-gray-300 rounded-lg"
                >
                    <option :value="1">First Semester</option>
                    <option :value="2">Second Semester</option>
                    <option :value="3">Summer</option>
                </select>

                <p v-if="form.errors.semester" class="text-red-600 text-sm mt-1">
                    {{ form.errors.semester }}
                </p>

            </div>

        </div>

        <!-- Subject Checklist -->

        <div v-if="isSubject">

            <p class="text-xs text-gray-400 mb-2">
                All subjects you check below get placed into this same Year Level / Semester.
                Need different subjects in a different term? Submit this batch first, then run
                Add Item again for the next term.
            </p>

            <!-- No curriculum chosen yet -->

            <div
                v-if="!form.curriculum_id"
                class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-400"
            >
                Select a curriculum above to see the subjects available for it.
            </div>

            <template v-else>

                <!-- Search / Classification Filter -->

                <div class="flex flex-col sm:flex-row gap-3 mb-3">

                    <input
                        v-model="subjectSearch"
                        type="text"
                        placeholder="Search by subject code or title..."
                        class="w-full sm:flex-1 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
                    />

                    <select
                        v-model="classificationFilter"
                        class="w-full sm:w-44 border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="All">All Classifications</option>
                        <option value="Minor">Minor</option>
                        <option value="Major">Major</option>
                    </select>

                    <button
                        v-if="subjectSearch || classificationFilter !== 'All'"
                        type="button"
                        @click="resetSubjectFilters"
                        class="text-sm text-gray-500 hover:underline whitespace-nowrap sm:px-2"
                    >
                        Clear filters
                    </button>

                </div>

                <!-- Counter / Select All -->

                <div class="flex justify-between items-center mb-2">

                    <label class="block text-sm font-medium text-gray-700">
                        Subjects
                        <span class="text-gray-400 font-normal">
                            ({{ shownCount }} shown &bull; {{ selectedCount }} selected)
                        </span>
                    </label>

                    <button
                        type="button"
                        @click="toggleSelectAll"
                        :disabled="visibleSubjects.length === 0"
                        class="text-sm text-blue-600 hover:underline disabled:text-gray-300 disabled:no-underline"
                    >
                        {{ allVisibleSelected ? 'Clear shown' : 'Select all shown' }}
                    </button>

                </div>

                <div class="border border-gray-300 rounded-lg max-h-96 overflow-y-auto">

                    <!-- General Education (Minors) -->

                    <div v-if="visibleMinorSubjects.length > 0">

                        <p class="sticky top-0 bg-gray-50 text-xs font-semibold uppercase text-gray-500 px-4 py-1.5 border-b">
                            General Education
                        </p>

                        <div class="divide-y">

                            <label
                                v-for="subject in visibleMinorSubjects"
                                :key="subject.id"
                                class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="subject.id"
                                    v-model="form.subject_ids"
                                    class="rounded"
                                />
                                <span class="text-sm text-gray-700">
                                    {{ subject.subject_code }} &mdash; {{ subject.descriptive_title }}
                                </span>
                                <span class="ml-auto shrink-0 bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-xs">
                                    Minor
                                </span>
                            </label>

                        </div>

                    </div>

                    <!-- Major Subjects -->

                    <div v-if="visibleMajorSubjects.length > 0">

                        <p class="sticky top-0 bg-gray-50 text-xs font-semibold uppercase text-gray-500 px-4 py-1.5 border-b border-t">
                            Major Subjects
                        </p>

                        <div class="divide-y">

                            <label
                                v-for="subject in visibleMajorSubjects"
                                :key="subject.id"
                                class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="subject.id"
                                    v-model="form.subject_ids"
                                    class="rounded"
                                />
                                <span class="text-sm text-gray-700">
                                    {{ subject.subject_code }} &mdash; {{ subject.descriptive_title }}
                                </span>
                                <span class="ml-auto shrink-0 bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs">
                                    Major
                                </span>
                            </label>

                        </div>

                    </div>

                    <!-- No results for the current search/filter -->

                    <p
                        v-if="visibleSubjects.length === 0"
                        class="px-4 py-6 text-center text-sm text-gray-400"
                    >
                        No subjects match your search/filter.
                    </p>

                    <!-- Already assigned to this curriculum -->

                    <div
                        v-if="alreadyAssignedSubjects.length > 0"
                        class="bg-gray-50 border-t"
                    >

                        <p class="text-xs font-semibold uppercase text-gray-400 px-4 py-1.5">
                            Already Assigned
                        </p>

                        <div
                            v-for="subject in alreadyAssignedSubjects"
                            :key="subject.id"
                            class="flex items-center gap-3 px-4 py-2.5 text-gray-400"
                        >
                            <span class="text-sm">
                                {{ subject.subject_code }} &mdash; {{ subject.descriptive_title }}
                            </span>
                            <span class="ml-auto shrink-0 text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">
                                Already assigned
                            </span>
                        </div>

                    </div>

                </div>

            </template>

            <p v-if="form.errors.subject_ids" class="text-red-600 text-sm mt-1">
                {{ form.errors.subject_ids }}
            </p>

        </div>

        <!-- Practicum / OJT Fields -->

        <div v-else-if="isOjt">

            <!-- No curriculum chosen yet -->

            <div
                v-if="!form.curriculum_id"
                class="border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center text-sm text-gray-400"
            >
                Select a curriculum above to see the practicum subjects available for its program.
            </div>

            <template v-else>

                <div class="grid grid-cols-2 gap-4">

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Practicum Subject
                        </label>

                        <select
                            v-model="form.subject_id"
                            class="w-full border-gray-300 rounded-lg"
                        >
                            <option value="" disabled>Select practicum subject</option>

                            <option
                                v-for="subject in filteredPracticumSubjects"
                                :key="subject.id"
                                :value="subject.id"
                            >
                                {{ subject.subject_code }} &mdash; {{ subject.descriptive_title }}
                            </option>
                        </select>

                        <p
                            v-if="filteredPracticumSubjects.length === 0"
                            class="text-gray-400 text-xs mt-1"
                        >
                            No practicum subjects are set up yet for this curriculum's program.
                        </p>

                        <p v-if="form.errors.subject_id" class="text-red-600 text-sm mt-1">
                            {{ form.errors.subject_id }}
                        </p>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Hours
                        </label>

                        <input
                            v-model.number="form.ojt_hours"
                            type="number"
                            min="1"
                            placeholder="e.g. 486"
                            class="w-full border-gray-300 rounded-lg"
                        />

                        <p v-if="form.errors.ojt_hours" class="text-red-600 text-sm mt-1">
                            {{ form.errors.ojt_hours }}
                        </p>

                    </div>

                </div>

                <!-- Auto-filled Subject Code / Title (Goal 4) -->

                <div
                    v-if="selectedPracticumSubject"
                    class="mt-4 flex justify-between items-center rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm"
                >
                    <span class="font-semibold text-gray-700">
                        {{ selectedPracticumSubject.subject_code }}
                    </span>
                    <span class="text-gray-500">
                        {{ selectedPracticumSubject.descriptive_title }}
                    </span>
                </div>

            </template>

        </div>

        <!-- Toggles -->

        <div class="flex items-center gap-8">

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.active" class="rounded" />
                <span class="text-sm text-gray-700">Active</span>
            </label>

        </div>

        <!-- Actions -->

        <div class="flex justify-end gap-3 pt-4 border-t">

            <Link
                :href="backHref"
                class="px-5 py-2 rounded-lg border text-gray-600 hover:bg-gray-50"
            >
                Cancel
            </Link>

            <button
                type="submit"
                :disabled="form.processing
                    || (isSubject && form.subject_ids.length === 0)
                    || (isOjt && (!form.subject_id || !form.ojt_hours || form.ojt_hours < 1))"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg disabled:opacity-50"
            >
                <template v-if="isSubject">
                    Add
                    {{ form.subject_ids.length > 0 ? form.subject_ids.length : '' }}
                    Subject{{ form.subject_ids.length === 1 ? '' : 's' }}
                </template>
                <template v-else>
                    Add Item
                </template>
            </button>

        </div>

    </form>

</div>

</template>