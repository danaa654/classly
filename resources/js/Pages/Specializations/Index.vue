<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import { Link, router } from '@inertiajs/vue3'
import { useFlashToast } from '@/Composables/useFlashToast'
import { SparklesIcon, PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'

defineProps({
    specializations: Array,
})

const { toast } = useFlashToast()

function destroy(id) {
    if (confirm('Are you sure you want to delete this specialization?')) {
        router.delete(route('specializations.destroy', id))
    }
}
</script>

<template>
    <DashboardLayout>

        <Toast :toast="toast" />

        <div class="relative">

            <!-- Subtle brand texture: faint grid + one soft gold glow, static (no animation) -->
            <div class="pointer-events-none absolute -inset-x-6 -inset-y-6 -z-10 overflow-hidden">
                <div
                    class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]"
                    style="background-image: linear-gradient(#1e3a5f 1px, transparent 1px), linear-gradient(90deg, #1e3a5f 1px, transparent 1px); background-size: 42px 42px;"
                ></div>
                <div class="absolute -top-16 right-0 h-64 w-64 rounded-full bg-[#D4A62A]/10 blur-3xl"></div>
            </div>

            <div class="flex justify-between items-center mb-6">

                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#D4A62A]/30 bg-[#D4A62A]/10 text-[#D4A62A]">
                        <SparklesIcon class="h-5.5 w-5.5" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold [font-family:'Fraunces',serif] text-[var(--text-primary)]">
                            Specializations
                        </h1>
                        <p class="text-sm text-[var(--text-muted)]">
                            {{ specializations.length }} {{ specializations.length === 1 ? 'specialization' : 'specializations' }} on record
                        </p>
                    </div>
                </div>

                <Link
                    :href="route('specializations.create')"
                    class="btn-save inline-flex items-center gap-1.5"
                >
                    <PlusIcon class="h-4 w-4" />
                    Add Specialization
                </Link>

            </div>

            <div class="relative overflow-hidden rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] shadow-lg transition-colors duration-300">

                <div class="pointer-events-none absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-[#D4A62A] to-transparent"></div>

                <table class="w-full">

                    <thead class="border-b border-[var(--card-border)] bg-[var(--page-bg)]">
                        <tr>
                            <th class="text-left p-4 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">College</th>
                            <th class="text-left p-4 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">Program</th>
                            <th class="text-left p-4 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">Code</th>
                            <th class="text-left p-4 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">Specialization</th>
                            <th class="text-center p-4 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">Status</th>
                            <th class="text-center p-4 text-[10px] font-semibold uppercase tracking-wider text-[var(--text-muted)]">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr
                            v-for="specialization in specializations"
                            :key="specialization.id"
                            class="group border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                        >

                            <td class="p-4 transition-shadow duration-150 group-hover:shadow-[inset_3px_0_0_#D4A62A]">
                                <span class="inline-flex items-center rounded-md border border-[#D4A62A]/30 bg-[#D4A62A]/10 px-2 py-1 text-xs font-semibold text-[#A8790E] dark:text-[#E8C766]">
                                    {{ specialization.program.department.abbreviation }}
                                </span>
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

                            <td class="p-4 text-center">

                                <span
                                    v-if="specialization.active"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-medium"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    Active
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-medium"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    Inactive
                                </span>

                            </td>

                            <td class="p-4 text-center space-x-2">

                                <Link
                                    :href="route('specializations.edit', specialization.id)"
                                    class="btn-edit inline-flex items-center gap-1.5"
                                >
                                    <PencilSquareIcon class="h-3.5 w-3.5" />
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(specialization.id)"
                                    class="btn-delete inline-flex items-center gap-1.5"
                                >
                                    <TrashIcon class="h-3.5 w-3.5" />
                                    Delete
                                </button>

                            </td>

                        </tr>

                        <tr v-if="specializations.length === 0">
                            <td colspan="6" class="p-12 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#D4A62A]/10 text-[#D4A62A]">
                                    <SparklesIcon class="h-6 w-6" />
                                </div>
                                <p class="font-medium text-[var(--text-secondary)]">No specializations yet</p>
                                <p class="mt-1 text-sm text-[var(--text-muted)]">Add your first specialization to start building tracks.</p>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </DashboardLayout>
</template>