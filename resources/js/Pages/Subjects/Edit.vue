<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    subject: Object,
    subjects: Array,
})

const form = useForm({
    subject_code: props.subject.subject_code,
    descriptive_title: props.subject.descriptive_title,
    units: props.subject.units,
    lecture_hours: props.subject.lecture_hours,
    laboratory_hours: props.subject.laboratory_hours,
    is_major: props.subject.is_major,
    required_room: props.subject.required_room,
    allow_split_schedule: props.subject.allow_split_schedule,
    prerequisite_id: props.subject.prerequisite_id ?? '',
    active: props.subject.active,
})

const totalHours = computed(() => {
    return (Number(form.lecture_hours) || 0) + (Number(form.laboratory_hours) || 0)
})

function submit() {
    form.put(route('subjects.update', props.subject.id))
}
</script>

<template>

<Head title="Edit Subject" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold">
                Edit Subject
            </h1>

            <p class="text-gray-500 mt-1">
                Update {{ subject.subject_code }} — {{ subject.descriptive_title }}
            </p>

        </div>

        <Link
            :href="route('subjects.index')"
            class="text-gray-600 hover:underline"
        >
            &larr; Back to Subjects
        </Link>

    </div>

    <!-- Form -->

    <form
        @submit.prevent="submit"
        class="bg-white rounded-lg shadow p-6 space-y-6"
    >

        <!-- Subject Code / Descriptive Title -->

        <div class="grid grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Subject Code
                </label>

                <input
                    v-model="form.subject_code"
                    type="text"
                    placeholder="e.g. IT101"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.subject_code" class="text-red-600 text-sm mt-1">
                    {{ form.errors.subject_code }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Descriptive Title
                </label>

                <input
                    v-model="form.descriptive_title"
                    type="text"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.descriptive_title" class="text-red-600 text-sm mt-1">
                    {{ form.errors.descriptive_title }}
                </p>

            </div>

        </div>

        <!-- Units / Hours -->

        <div class="grid grid-cols-4 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Units
                </label>

                <input
                    v-model.number="form.units"
                    type="number"
                    min="1"
                    max="6"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.units" class="text-red-600 text-sm mt-1">
                    {{ form.errors.units }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lecture Hours
                </label>

                <input
                    v-model.number="form.lecture_hours"
                    type="number"
                    min="0"
                    max="10"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.lecture_hours" class="text-red-600 text-sm mt-1">
                    {{ form.errors.lecture_hours }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Laboratory Hours
                </label>

                <input
                    v-model.number="form.laboratory_hours"
                    type="number"
                    min="0"
                    max="10"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.laboratory_hours" class="text-red-600 text-sm mt-1">
                    {{ form.errors.laboratory_hours }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Total Hours
                </label>

                <input
                    :value="totalHours"
                    type="number"
                    disabled
                    class="w-full border-gray-300 rounded-lg bg-gray-100 text-gray-500"
                />

            </div>

        </div>

        <!-- Classification / Required Room -->

        <div class="grid grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Classification
                </label>

                <select
                    v-model="form.is_major"
                    class="w-full border-gray-300 rounded-lg"
                >
                    <option :value="true">Major</option>
                    <option :value="false">Minor</option>
                </select>

                <p v-if="form.errors.is_major" class="text-red-600 text-sm mt-1">
                    {{ form.errors.is_major }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Required Room
                </label>

                <select
                    v-model="form.required_room"
                    class="w-full border-gray-300 rounded-lg"
                >
                    <option value="Lecture">Lecture</option>
                    <option value="Computer Laboratory">Computer Laboratory</option>
                    <option value="Science Laboratory">Science Laboratory</option>
                    <option value="Speech Laboratory">Speech Laboratory</option>
                    <option value="PE Area">PE Area</option>
                    <option value="Any">Any</option>
                </select>

                <p v-if="form.errors.required_room" class="text-red-600 text-sm mt-1">
                    {{ form.errors.required_room }}
                </p>

            </div>

        </div>

        <!-- Prerequisite -->

        <div>

            <label class="block text-sm font-medium text-gray-700 mb-1">
                Prerequisite (optional)
            </label>

            <select
                v-model="form.prerequisite_id"
                class="w-full border-gray-300 rounded-lg"
            >
                <option value="">None</option>

                <option
                    v-for="subject in subjects"
                    :key="subject.id"
                    :value="subject.id"
                >
                    {{ subject.subject_code }} - {{ subject.descriptive_title }}
                </option>
            </select>

            <p v-if="form.errors.prerequisite_id" class="text-red-600 text-sm mt-1">
                {{ form.errors.prerequisite_id }}
            </p>

        </div>

        <!-- Toggles -->

        <div class="flex items-center gap-8">

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.allow_split_schedule" class="rounded" />
                <span class="text-sm text-gray-700">Allow Split Schedule</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.active" class="rounded" />
                <span class="text-sm text-gray-700">Active</span>
            </label>

        </div>

        <!-- Actions -->

        <div class="flex justify-end gap-3 pt-4 border-t">

            <Link
                :href="route('subjects.index')"
                class="px-5 py-2 rounded-lg border text-gray-600 hover:bg-gray-50"
            >
                Cancel
            </Link>

            <button
                type="submit"
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg disabled:opacity-50"
            >
                Update Subject
            </button>

        </div>

    </form>

</div>

</template>