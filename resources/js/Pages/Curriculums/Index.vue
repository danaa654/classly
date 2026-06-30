<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    curricula: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this curriculum?')) {
        router.delete(`/curriculums/${id}`)
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                Curricula
            </h1>

            <Link
                href="/curriculums/create"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Curriculum
            </Link>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>

                        <th class="text-left p-4">
                            Program
                        </th>

                        <th class="text-left p-4">
                            Specialization
                        </th>

                        <th class="text-left p-4">
                            Code
                        </th>

                        <th class="text-left p-4">
                            Curriculum Name
                        </th>

                        <th class="text-left p-4">
                            Academic Year
                        </th>

                        <th class="text-left p-4">
                            Effective Year
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
                        v-for="curriculum in curricula"
                        :key="curriculum.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <!-- Program -->
                        <td class="p-4 font-medium">

                            {{ curriculum.program?.department?.abbreviation }}
                            •
                            {{ curriculum.program?.code }}

                        </td>

                        <!-- Specialization -->
                        <td class="p-4">

                            {{
                                curriculum.specialization?.name
                                    ?? 'General Program'
                            }}

                        </td>

                        <!-- Code -->
                        <td class="p-4 font-mono">

                            {{ curriculum.code }}

                        </td>

                        <!-- Curriculum Name -->
                        <td class="p-4">

                            {{ curriculum.name }}

                        </td>

                        <!-- Academic Year -->
                        <td class="p-4">

                            {{ curriculum.academic_year }}

                        </td>

                        <!-- Effective Year -->
                        <td class="p-4">

                            {{ curriculum.effective_year }}

                        </td>

                        <!-- Status -->
                        <td class="p-4">

                            <span
                                v-if="curriculum.active"
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

                        <!-- Actions -->
                        <td class="p-4 whitespace-nowrap">

                            <div class="flex gap-2">

                                <Link
                                    :href="`/curriculums/${curriculum.id}/edit`"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(curriculum.id)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="curricula.length === 0">

                        <td
                            colspan="8"
                            class="text-center p-8 text-gray-500"
                        >
                            No curricula found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>