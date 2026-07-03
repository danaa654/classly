<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    faculties: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this faculty member?')) {
        router.delete(route('faculty.destroy', id))
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                Faculty Members
            </h1>

            <Link
                :href="route('faculty.create')"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Faculty
            </Link>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>
                        <th class="p-4 text-left w-12">#</th>
                        <th class="p-4 text-left">Faculty Name</th>
                        <th class="p-4 text-left">Email</th>
                        <th class="p-4 text-left">Department</th>
                        <th class="p-4 text-left">Employment</th>
                        <th class="p-4 text-left">Max Units</th>
                        <th class="p-4 text-left">Faculty Scope</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-center whitespace-nowrap">
                            Actions
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(faculty, index) in faculties"
                        :key="faculty.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="p-4">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-medium">
                            {{ faculty.full_name }}
                        </td>

                        <td class="p-4">
                            {{ faculty.email || '-' }}
                        </td>

                        <td class="p-4">
                            {{ faculty.department?.abbreviation ?? 'N/A' }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="faculty.employment_type === 'Full-Time'"
                                class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm"
                            >
                                Full-Time
                            </span>

                            <span
                                v-else
                                class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm"
                            >
                                Part-Time
                            </span>

                        </td>

                        <td class="p-4">
                            {{ faculty.max_units }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="faculty.faculty_scope === 'general'"
                                class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm"
                            >
                                General Education
                            </span>

                            <span
                                v-else-if="faculty.faculty_scope === 'departmental'"
                                class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm"
                            >
                                Departmental
                            </span>

                            <span
                                v-else
                                class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
                            >
                                Cross Department
                            </span>

                        </td>

                        <td class="p-4">

                            <span
                                v-if="faculty.status"
                                class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm"
                            >
                                Inactive
                            </span>

                        </td>

                        <td class="p-4 whitespace-nowrap">

                            <div class="flex justify-center gap-2">

                                <Link
                                    :href="route('faculty.edit', faculty.id)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(faculty.id)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="faculties.length === 0">

                        <td
                            colspan="9"
                            class="text-center p-8 text-gray-500"
                        >
                            No faculty members found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>