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

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Specializations
            </h1>

            <Link
                :href="route('specializations.create')"
                class="btn-save"
            >
                Add Specialization
            </Link>

        </div>

        <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden transition-colors duration-300">

            <table class="w-full">

                <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">

                    <tr>

                        <th class="text-left p-4 text-[var(--text-secondary)]">
                            College
                        </th>

                        <th class="text-left p-4 text-[var(--text-secondary)]">
                            Program
                        </th>

                        <th class="text-left p-4 text-[var(--text-secondary)]">
                            Code
                        </th>

                        <th class="text-left p-4 text-[var(--text-secondary)]">
                            Specialization
                        </th>

                        <th class="text-left p-4 text-[var(--text-secondary)]">
                            Status
                        </th>

                        <th class="text-left p-4 text-[var(--text-secondary)]">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="specialization in specializations"
                        :key="specialization.id"
                        class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ specialization.program.department.abbreviation }}
                        </td>

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ specialization.program.code }}
                        </td>

                        <td class="p-4 font-semibold text-[var(--text-primary)]">
                            {{ specialization.code }}
                        </td>

                        <td class="p-4 text-[var(--text-primary)]">
                            {{ specialization.name }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="specialization.active"
                                class="inline-flex px-3 py-1 rounded-full bg-green-500/10 text-green-600 dark:text-green-400 text-sm font-medium"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="inline-flex px-3 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 text-sm font-medium"
                            >
                                Inactive
                            </span>

                        </td>

                        <td class="p-4 space-x-2">

                            <Link
                                :href="route('specializations.edit', specialization.id)"
                                class="btn-edit"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroy(specialization.id)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <tr v-if="specializations.length === 0">

                        <td
                            colspan="6"
                            class="text-center p-6 text-[var(--text-muted)]"
                        >
                            No specializations found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>