<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    teachingAssignments: { type: Array, required: true },
});

const confirmDelete = (assignment) => {
    const label = assignment.faculty?.full_name ?? 'this faculty member';

    if (!confirm(`Remove the teaching assignment for ${label}?`)) {
        return;
    }

    router.delete(route('teaching-assignments.destroy', assignment.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Teaching Assignments" />

        <div class="px-8 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Teaching Assignments</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Faculty loading — who teaches what, for which section, this term.
                    </p>
                </div>
                <Link
                    :href="route('teaching-assignments.create')"
                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                >
                    + New Assignment
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Academic Term
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Section
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Curriculum Item
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Assigned Faculty
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Remarks
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="teachingAssignments.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                                    No teaching assignments yet. Create one to get started.
                                </td>
                            </tr>
                            <tr
                                v-for="assignment in teachingAssignments"
                                :key="assignment.id"
                                class="transition hover:bg-gray-50"
                            >
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ assignment.academicTerm?.display_name }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ assignment.section?.section_code }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span v-if="assignment.curriculumItem?.display_code" class="mr-1 text-gray-400">
                                        {{ assignment.curriculumItem.display_code }}
                                    </span>
                                    {{ assignment.curriculumItem?.display_title }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ assignment.faculty?.full_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ assignment.remarks || '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span
                                        :class="
                                            assignment.active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    >
                                        {{ assignment.active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <Link
                                        :href="route('teaching-assignments.edit', assignment.id)"
                                        class="mr-2 inline-block rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-500"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="inline-block rounded-lg bg-red-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-400"
                                        @click="confirmDelete(assignment)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>