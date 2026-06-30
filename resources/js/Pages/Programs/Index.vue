<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    programs: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this program?')) {
        router.delete(`/programs/${id}`)
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">
                Programs
            </h1>

            <Link
                href="/programs/create"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Program
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left p-4">College</th>
                        <th class="text-left p-4">Program Code</th>
                        <th class="text-left p-4">Program Name</th>
                        <th class="text-center p-4">Years</th>
                        <th class="text-center p-4">Status</th>
                        <th class="text-center p-4">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="program in programs"
                        :key="program.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="p-4">
                            {{ program.department?.abbreviation }}
                        </td>

                        <td class="p-4 font-semibold">
                            {{ program.code }}
                        </td>

                        <td class="p-4">
                            {{ program.name }}
                        </td>

                        <td class="p-4 text-center">
                            {{ program.years }}
                        </td>

                        <td class="p-4 text-center">

                            <span
                                v-if="program.active"
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
                                :href="`/programs/${program.id}/edit`"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroy(program.id)"
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <tr v-if="programs.length === 0">
                        <td colspan="6" class="text-center p-6 text-gray-500">
                            No programs found.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>