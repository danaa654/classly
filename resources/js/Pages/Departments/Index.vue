<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    departments: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this college?')) {
        router.delete(`/departments/${id}`)
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">
                Colleges
            </h1>

            <Link
                href="/departments/create"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add College
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left p-4">Abbreviation</th>
                        <th class="text-left p-4">College Name</th>
                        <th class="text-left p-4">Description</th>
                        <th class="text-center p-4">Status</th>
                        <th class="text-center p-4">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="department in departments"
                        :key="department.id"
                        class="border-t hover:bg-gray-50"
                    >
                        <td class="p-4 font-semibold">
                            {{ department.abbreviation }}
                        </td>

                        <td class="p-4">
                            {{ department.name }}
                        </td>

                        <td class="p-4 text-gray-600">
                            {{ department.description || '—' }}
                        </td>

                        <td class="p-4 text-center">
                            <span
                                v-if="department.active"
                                class="inline-flex px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-medium"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="inline-flex px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-medium"
                            >
                                Inactive
                            </span>
                        </td>

                        <td class="p-4 text-center space-x-2">

                            <Link
                                :href="`/departments/${department.id}/edit`"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroy(department.id)"
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <tr v-if="departments.length === 0">
                        <td
                            colspan="5"
                            class="text-center p-6 text-gray-500"
                        >
                            No colleges found.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>