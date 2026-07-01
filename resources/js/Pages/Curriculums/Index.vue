<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    curricula: Array,
})

function curriculumLabel(curriculum) {
    if (curriculum.specialization) {
        return `${curriculum.program.code} - ${curriculum.specialization.name}`
    }

    return curriculum.program.code
}

function destroyCurriculum(curriculum) {
    if (!confirm(`Delete ${curriculum.code}? This cannot be undone.`)) {
        return
    }

    router.delete(route('curriculums.destroy', curriculum.id), {
        preserveScroll: true,
    })
}
</script>

<template>

<Head title="Curriculums" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold">
                Curriculums
            </h1>

            <p class="text-gray-500 mt-1">
                Manage curriculums and their assigned subjects.
            </p>

        </div>

        <Link
            :href="route('curriculums.create')"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
        >
            + New Curriculum
        </Link>

    </div>

    <!-- Table -->

    <div class="bg-white rounded-lg shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-gray-100">

                <tr>

                    <th class="px-4 py-3 text-left">
                        Code
                    </th>

                    <th class="px-4 py-3 text-left">
                        Name
                    </th>

                    <th class="px-4 py-3 text-left">
                        Program
                    </th>

                    <th class="px-4 py-3 text-left">
                        Academic Year
                    </th>

                    <th class="px-4 py-3 text-center">
                        Effective Year
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
                    v-for="curriculum in curricula"
                    :key="curriculum.id"
                    class="border-t hover:bg-gray-50"
                >

                    <td class="px-4 py-3 font-semibold">
                        {{ curriculum.code }}
                    </td>

                    <td class="px-4 py-3">
                        {{ curriculum.name }}
                    </td>

                    <td class="px-4 py-3">
                        {{ curriculumLabel(curriculum) }}
                        <span class="text-gray-400 text-xs block">
                            {{ curriculum.program.department?.abbreviation }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        {{ curriculum.academic_year }}
                    </td>

                    <td class="px-4 py-3 text-center">
                        {{ curriculum.effective_year }}
                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="curriculum.active"
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

                    <td class="px-4 py-3 text-center whitespace-nowrap">

                        <Link
                            :href="route('curriculums.items.manage', curriculum.id)"
                            class="text-indigo-600 hover:underline mr-3"
                        >
                            Manage Items
                        </Link>

                        <Link
                            :href="route('curriculums.edit', curriculum.id)"
                            class="text-blue-600 hover:underline mr-3"
                        >
                            Edit
                        </Link>

                        <button
                            @click="destroyCurriculum(curriculum)"
                            class="text-red-600 hover:underline"
                        >
                            Delete
                        </button>

                    </td>

                </tr>

                <tr v-if="curricula.length === 0">

                    <td
                        colspan="7"
                        class="text-center py-8 text-gray-500"
                    >
                        No curriculums found.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

</template>