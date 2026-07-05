<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import { Link, router } from '@inertiajs/vue3'
import { useFlashToast } from '@/Composables/useFlashToast'

defineProps({
    departments: Array,
})

// Create/Update/Delete all redirect back to this index page, so this is
// the one place in the Colleges module that needs to render the flash.
const { toast } = useFlashToast()

function destroy(id) {
    if (confirm('Are you sure you want to delete this college?')) {
        router.delete(`/departments/${id}`)
    }
}
</script>

<template>
    <DashboardLayout>

        <Toast :toast="toast" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Colleges
            </h1>

            <Link
                href="/departments/create"
                class="btn-save"
            >
                Add College
            </Link>
        </div>

        <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden transition-colors duration-300">

            <table class="w-full">

                <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">
                    <tr>
                        <th class="text-left p-4 text-[var(--text-secondary)]">Abbreviation</th>
                        <th class="text-left p-4 text-[var(--text-secondary)]">College Name</th>
                        <th class="text-left p-4 text-[var(--text-secondary)]">Description</th>
                        <th class="text-center p-4 text-[var(--text-secondary)]">Status</th>
                        <th class="text-center p-4 text-[var(--text-secondary)]">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="department in departments"
                        :key="department.id"
                        class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >
                        <td class="p-4 font-semibold text-[var(--text-primary)]">
                            {{ department.abbreviation }}
                        </td>

                        <td class="p-4 text-[var(--text-primary)]">
                            {{ department.name }}
                        </td>

                        <td class="p-4 text-[var(--text-secondary)]">
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
                                class="btn-edit"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroy(department.id)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <tr v-if="departments.length === 0">
                        <td
                            colspan="5"
                            class="text-center p-6 text-[var(--text-muted)]"
                        >
                            No colleges found.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>