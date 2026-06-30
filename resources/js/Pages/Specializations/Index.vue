<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    specializations: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this specialization?')) {
        router.delete(route('specializations.destroy', id))
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                Specializations
            </h1>

            <Link
                :href="route('specializations.create')"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Specialization
            </Link>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>

                        <th class="text-left p-4">
                            College
                        </th>

                        <th class="text-left p-4">
                            Program
                        </th>

                        <th class="text-left p-4">
                            Code
                        </th>

                        <th class="text-left p-4">
                            Specialization
                        </th>

                        <th class="text-left p-4">
                            Status
                        </th>

                        <th class="text-left p-4">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="specialization in specializations"
                        :key="specialization.id"
                        class="border-t"
                    >

                        <td class="p-4">
                            {{ specialization.program.department.abbreviation }}
                        </td>

                        <td class="p-4">
                            {{ specialization.program.code }}
                        </td>

                        <td class="p-4">
                            {{ specialization.code }}
                        </td>

                        <td class="p-4">
                            {{ specialization.name }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="specialization.active"
                                class="text-green-600 font-semibold"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="text-red-600 font-semibold"
                            >
                                Inactive
                            </span>

                        </td>

                        <td class="p-4 space-x-2">

                            <Link
                                :href="route('specializations.edit', specialization.id)"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroy(specialization.id)"
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <tr v-if="specializations.length === 0">

                        <td
                            colspan="6"
                            class="text-center p-6 text-gray-500"
                        >
                            No specializations found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>