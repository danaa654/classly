<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    subjects: Array,
})

function destroySubject(subject) {
    if (!confirm(`Delete ${subject.subject_code} - ${subject.descriptive_title}? This cannot be undone.`)) {
        return
    }

    router.delete(route('subjects.destroy', subject.id), {
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

                    <th class="px-4 py-3 text-center">
                        Room Group
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
                    v-for="subject in subjects"
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

                    <td class="px-4 py-3 text-center">
                        {{ subject.required_room_group ?? '—' }}
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
                            :href="route('subjects.edit', subject.id)"
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

                <tr v-if="subjects.length === 0">

                    <td
                        colspan="10"
                        class="text-center py-8 text-gray-500"
                    >
                        No subjects found.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

</template>