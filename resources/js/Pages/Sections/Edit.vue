<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    section: Object,
    curriculums: Array,
})

const form = useForm({
    curriculum_id: props.section.curriculum_id,
    section_code: props.section.section_code,
    section_name: props.section.section_name,
    capacity: props.section.capacity,
    status: props.section.status,
})

function submit() {
    form.put(route('sections.update', props.section.id))
}
</script>

<template>
    <DashboardLayout>

        <h1 class="text-3xl font-bold mb-6">
            Edit Section
        </h1>

        <div class="bg-white rounded-lg shadow p-6 max-w-2xl">

            <form @submit.prevent="submit">

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Curriculum
                    </label>

                    <select
                        v-model="form.curriculum_id"
                        class="w-full border rounded p-2"
                    >
                        <option value="" disabled>
                            Select a curriculum
                        </option>
                        <option
                            v-for="curriculum in curriculums"
                            :key="curriculum.id"
                            :value="curriculum.id"
                        >
                            {{ curriculum.display_name }}
                        </option>
                    </select>

                    <p v-if="form.errors.curriculum_id" class="text-red-500 text-sm mt-1">
                        {{ form.errors.curriculum_id }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Section Code
                    </label>

                    <input
                        v-model="form.section_code"
                        type="text"
                        placeholder="e.g. BSIT-1A"
                        class="w-full border rounded p-2"
                    >

                    <p v-if="form.errors.section_code" class="text-red-500 text-sm mt-1">
                        {{ form.errors.section_code }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Section Name
                    </label>

                    <input
                        v-model="form.section_name"
                        type="text"
                        placeholder="e.g. BS Information Technology 1A"
                        class="w-full border rounded p-2"
                    >

                    <p v-if="form.errors.section_name" class="text-red-500 text-sm mt-1">
                        {{ form.errors.section_name }}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block font-medium mb-1">
                        Capacity
                    </label>

                    <input
                        v-model="form.capacity"
                        type="number"
                        min="1"
                        class="w-full border rounded p-2"
                    >

                    <p v-if="form.errors.capacity" class="text-red-500 text-sm mt-1">
                        {{ form.errors.capacity }}
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block font-medium mb-1">
                        Status
                    </label>

                    <select
                        v-model="form.status"
                        class="w-full border rounded p-2"
                    >
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>

                    <p v-if="form.errors.status" class="text-red-500 text-sm mt-1">
                        {{ form.errors.status }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">

                    <Link
                        :href="route('sections.index')"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded disabled:opacity-50"
                    >
                        Update Section
                    </button>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>