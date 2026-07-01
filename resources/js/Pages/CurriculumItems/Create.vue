<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    curricula: Array,
    subjects: Array,
    selectedCurriculumId: Number,
    assignedSubjectIds: Array,
})

const form = useForm({
    curriculum_id: props.selectedCurriculumId ?? '',
    item_type: 'Subject',
    subject_ids: [],
    title: '',
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
// type, so a half-filled OJT form can't accidentally submit alongside
// chosen subjects (or vice versa).
watch(() => form.item_type, () => {
    form.subject_ids = []
    form.title = ''
    form.ojt_hours = ''
})

const assignedSet = computed(() => new Set(props.assignedSubjectIds ?? []))

const availableSubjects = computed(() => {
    return props.subjects.filter((subject) => !assignedSet.value.has(subject.id))
})

const alreadyAssignedSubjects = computed(() => {
    return props.subjects.filter((subject) => assignedSet.value.has(subject.id))
})

const allSelected = computed(() => {
    return availableSubjects.value.length > 0
        && form.subject_ids.length === availableSubjects.value.length
})

const backHref = computed(() => {
    return curriculumLocked.value
        ? route('curriculums.items.manage', props.selectedCurriculumId)
        : route('curriculum-items.index')
})

function curriculumLabel(curriculum) {
    return `${curriculum.code} — ${curriculum.name}`
}

function subjectLabel(subject) {
    return `${subject.subject_code} - ${subject.descriptive_title}`
}

function toggleSelectAll() {
    form.subject_ids = allSelected.value
        ? []
        : availableSubjects.value.map((subject) => subject.id)
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
                Attach one or more Subjects, or a single OJT/Internship item, to a curriculum's prospectus.
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
                <option value="OJT">Internship / OJT</option>
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
                    <option :value="5">5th Year</option>
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

            <div class="flex justify-between items-center mb-2">

                <label class="block text-sm font-medium text-gray-700">
                    Subjects
                    <span class="text-gray-400 font-normal">
                        ({{ form.subject_ids.length }} selected)
                    </span>
                </label>

                <button
                    type="button"
                    @click="toggleSelectAll"
                    class="text-sm text-blue-600 hover:underline"
                >
                    {{ allSelected ? 'Clear all' : 'Select all' }}
                </button>

            </div>

            <div class="border border-gray-300 rounded-lg max-h-80 overflow-y-auto divide-y">

                <label
                    v-for="subject in availableSubjects"
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
                        {{ subjectLabel(subject) }}
                    </span>
                </label>

                <p
                    v-if="availableSubjects.length === 0"
                    class="px-4 py-6 text-center text-sm text-gray-400"
                >
                    Every active subject is already assigned to this curriculum.
                </p>

                <div
                    v-if="alreadyAssignedSubjects.length > 0"
                    class="bg-gray-50"
                >
                    <div
                        v-for="subject in alreadyAssignedSubjects"
                        :key="subject.id"
                        class="flex items-center justify-between gap-3 px-4 py-2.5 text-gray-400"
                    >
                        <span class="text-sm">
                            {{ subjectLabel(subject) }}
                        </span>
                        <span class="text-xs bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">
                            Already assigned
                        </span>
                    </div>
                </div>

            </div>

            <p v-if="form.errors.subject_ids" class="text-red-600 text-sm mt-1">
                {{ form.errors.subject_ids }}
            </p>

        </div>

        <!-- OJT Fields -->

        <div v-else-if="isOjt" class="grid grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Title
                </label>

                <input
                    v-model="form.title"
                    type="text"
                    placeholder="e.g. On-the-Job Training"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.title" class="text-red-600 text-sm mt-1">
                    {{ form.errors.title }}
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
                :disabled="form.processing || (isSubject && form.subject_ids.length === 0)"
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