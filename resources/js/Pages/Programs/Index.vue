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
            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Programs
            </h1>

            <Link
                href="/programs/create"
                class="btn-save"
            >
                Add Program
            </Link>
        </div>

        <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden transition-colors duration-300">

            <table class="w-full">

                <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">
                    <tr>
                        <th class="text-left p-4 text-[var(--text-secondary)]">College</th>
                        <th class="text-left p-4 text-[var(--text-secondary)]">Program Code</th>
                        <th class="text-left p-4 text-[var(--text-secondary)]">Program Name</th>
                        <th class="text-center p-4 text-[var(--text-secondary)]">Years</th>
                        <th class="text-center p-4 text-[var(--text-secondary)]">Status</th>
                        <th class="text-center p-4 text-[var(--text-secondary)]">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="program in programs"
                        :key="program.id"
                        class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ program.department?.abbreviation }}
                        </td>

                        <td class="p-4 font-semibold text-[var(--text-primary)]">
                            {{ program.code }}
                        </td>

                        <td class="p-4 text-[var(--text-primary)]">
                            {{ program.name }}
                        </td>

                        <td class="p-4 text-center text-[var(--text-secondary)]">
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
                                class="btn-edit"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroy(program.id)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                        </td>

                    </tr>

                    <tr v-if="programs.length === 0">
                        <td colspan="6" class="text-center p-6 text-[var(--text-muted)]">
                            No programs found.
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>