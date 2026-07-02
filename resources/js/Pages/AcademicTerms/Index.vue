<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    academicTerms: Array,
})

function formatDate(value) {
    if (!value) {
        return '-'
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

function formatTime(value) {
    if (!value) {
        return null
    }

    const [hour, minute] = value.split(':')
    const date = new Date()
    date.setHours(hour, minute)

    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    })
}

function statusClasses(status) {
    return {
        Draft: 'bg-gray-100 text-gray-700',
        Published: 'bg-green-100 text-green-700',
        Archived: 'bg-red-100 text-red-700',
    }[status] ?? 'bg-gray-100 text-gray-700'
}

function destroy(id) {
    if (confirm('Are you sure you want to delete this academic term?')) {
        router.delete(route('academic-terms.destroy', id))
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                Academic Terms
            </h1>

            <Link
                :href="route('academic-terms.create')"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Academic Term
            </Link>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>
                        <th class="p-4 text-left w-12">#</th>
                        <th class="p-4 text-left">Academic Year</th>
                        <th class="p-4 text-left">Semester</th>
                        <th class="p-4 text-left">Registration Dates</th>
                        <th class="p-4 text-left">Class Dates</th>
                        <th class="p-4 text-left">School Hours</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-left">Active</th>
                        <th class="p-4 text-center whitespace-nowrap">
                            Actions
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(term, index) in academicTerms"
                        :key="term.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="p-4">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-medium">
                            {{ term.academic_year }}
                        </td>

                        <td class="p-4">
                            {{ term.semester_label }}
                        </td>

                        <td class="p-4 whitespace-nowrap">
                            {{ formatDate(term.registration_start_date) }}
                            &ndash;
                            {{ formatDate(term.registration_end_date) }}
                        </td>

                        <td class="p-4 whitespace-nowrap">
                            {{ formatDate(term.class_start_date) }}
                            &ndash;
                            {{ formatDate(term.class_end_date) }}
                        </td>

                        <td class="p-4 whitespace-nowrap">
                            {{ formatTime(term.school_start_time) }}
                            &ndash;
                            {{ formatTime(term.school_end_time) }}
                        </td>

                        <td class="p-4">
                            <span
                                class="px-3 py-1 rounded-full text-sm"
                                :class="statusClasses(term.status)"
                            >
                                {{ term.status }}
                            </span>
                        </td>

                        <td class="p-4">

                            <span
                                v-if="term.active"
                                class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm"
                            >
                                Inactive
                            </span>

                        </td>

                        <td class="p-4 whitespace-nowrap">

                            <div class="flex justify-center gap-2">

                                <Link
                                    :href="route('academic-terms.edit', term.id)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(term.id)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="academicTerms.length === 0">

                        <td
                            colspan="9"
                            class="text-center p-8 text-gray-500"
                        >
                            No academic terms found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>