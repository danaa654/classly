<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    sections: Array,
})

function curriculumLabel(section) {
    const curriculum = section.curriculum

    if (!curriculum) {
        return '-'
    }

    return curriculum.display_name
        ?? [
            curriculum.program?.code,
            curriculum.specialization?.name,
        ].filter(Boolean).join(' - ')
}

function destroy(id) {
    if (confirm('Are you sure you want to delete this section?')) {
        router.delete(route('sections.destroy', id))
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                Sections
            </h1>

            <Link
                :href="route('sections.create')"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Section
            </Link>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>
                        <th class="p-4 text-left w-12">#</th>
                        <th class="p-4 text-left">Curriculum</th>
                        <th class="p-4 text-left">Section Code</th>
                        <th class="p-4 text-left">Section Name</th>
                        <th class="p-4 text-left">Capacity</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-center whitespace-nowrap">
                            Actions
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(section, index) in sections"
                        :key="section.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="p-4">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4">
                            {{ curriculumLabel(section) }}
                        </td>

                        <td class="p-4 font-medium">
                            {{ section.section_code }}
                        </td>

                        <td class="p-4">
                            {{ section.section_name }}
                        </td>

                        <td class="p-4">
                            {{ section.capacity }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="section.status === 'Active'"
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
                                    :href="route('sections.edit', section.id)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(section.id)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="sections.length === 0">

                        <td
                            colspan="7"
                            class="text-center p-8 text-gray-500"
                        >
                            No sections found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>