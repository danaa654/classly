<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    curriculumItem: Object,
    curricula: Array,
    subjects: Array,
    assignedSubjectIds: Array,
})

const form = useForm({
    curriculum_id: props.curriculumItem.curriculum_id,
    item_type: props.curriculumItem.item_type,
    subject_id: props.curriculumItem.subject_id ?? '',
    title: props.curriculumItem.title ?? '',
    ojt_hours: props.curriculumItem.ojt_hours ?? '',
    year_level: props.curriculumItem.year_level,
    semester: props.curriculumItem.semester,
    sort_order: props.curriculumItem.sort_order,
    active: props.curriculumItem.active,
})

const isSubject = computed(() => form.item_type === 'Subject')
const isOjt = computed(() => form.item_type === 'OJT')

// Only clear fields when the type is actually changed away from what
// was originally loaded — otherwise this would wipe out the existing
// subject_id / title+hours the moment the form mounts.
watch(() => form.item_type, (newType, oldType) => {
    if (newType === oldType) return

    form.subject_id = ''
    form.title = ''
    form.ojt_hours = ''
})

const assignedSet = computed(() => new Set(props.assignedSubjectIds ?? []))

const backHref = computed(() => {
    return route('curriculums.items.manage', form.curriculum_id)
})

function curriculumLabel(curriculum) {
    return `${curriculum.code} — ${curriculum.name}`
}

function subjectLabel(subject) {
    return `${subject.subject_code} - ${subject.descriptive_title}`
}

function submit() {
    form.put(route('curriculum-items.update', props.curriculumItem.id))
}
</script>

<template>

<Head title="Edit Curriculum Item" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold">
                Edit Curriculum Item
            </h1>

            <p class="text-gray-500 mt-1">
                Update {{ curriculumItem.display_title ?? curriculumItem.title ?? curriculumItem.subject?.subject_code }}
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
                    class="w-full border-gray-300 rounded-lg"
                >
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

        <!-- Subject Fields -->

        <div v-if="isSubject">

            <label class="block text-sm font-medium text-gray-700 mb-1">
                Subject
            </label>

            <select
                v-model="form.subject_id"
                class="w-full border-gray-300 rounded-lg"
            >
                <option value="" disabled>Select subject</option>

                <option
                    v-for="subject in subjects"
                    :key="subject.id"
                    :value="subject.id"
                    :disabled="assignedSet.has(subject.id)"
                >
                    {{ subjectLabel(subject) }}
                    {{ assignedSet.has(subject.id) ? ' (already assigned)' : '' }}
                </option>
            </select>

            <p v-if="form.errors.subject_id" class="text-red-600 text-sm mt-1">
                {{ form.errors.subject_id }}
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

        <!-- Sort Order / Toggles -->

        <div class="grid grid-cols-3 gap-4 items-end">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Sort Order
                </label>

                <input
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.sort_order" class="text-red-600 text-sm mt-1">
                    {{ form.errors.sort_order }}
                </p>

            </div>

            <label class="flex items-center gap-2 pb-2.5">
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
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg disabled:opacity-50"
            >
                Update Item
            </button>

        </div>

    </form>

</div>

</template>