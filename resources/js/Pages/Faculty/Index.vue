<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import FacultyDeleteModal from './FacultyDeleteModal.vue'

defineProps({
    faculties: Array,
    canManageFaculty: Boolean,
})

/*
|--------------------------------------------------------------------------
| Delete — Preview Modal for Already-Scheduled Faculty
|--------------------------------------------------------------------------
|
| A faculty member with no scheduled classes still just needs a plain
| confirm() — no need to open a modal to show an empty list. Once
| faculty.has_schedule is true (computed server-side in
| FacultyController::index(), from the SAME check destroy() enforces),
| Delete opens FacultyDeleteModal instead, which fetches the actual list
| of scheduled classes so Admin/Registrar can see exactly what they're
| about to orphan before confirming.
|
*/

const deleteModalOpen = ref(false)
const facultyPendingDelete = ref(null)

function destroy(faculty) {
    if (faculty.has_schedule) {
        facultyPendingDelete.value = faculty
        deleteModalOpen.value = true

        return
    }

    if (confirm(`Delete ${faculty.full_name}? This cannot be undone.`)) {
        router.delete(route('faculty.destroy', faculty.id))
    }
}

function closeDeleteModal() {
    deleteModalOpen.value = false
    facultyPendingDelete.value = null
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1
                class="text-3xl font-bold"
                style="color: var(--text-primary)"
            >
                Faculty Members
            </h1>

            <Link
                v-if="canManageFaculty"
                :href="route('faculty.create')"
                class="btn-save"
            >
                Add Faculty
            </Link>

        </div>

        <div
            class="rounded-lg shadow overflow-hidden border"
            style="background: var(--card-bg); border-color: var(--card-border)"
        >

            <table class="w-full">

                <thead style="background: var(--page-bg)">

                    <tr>
                        <th class="p-4 text-left w-12" style="color: var(--text-secondary)">#</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Faculty Name</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Email</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Department</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Employment</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Max Units</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Faculty Scope</th>
                        <th class="p-4 text-left" style="color: var(--text-secondary)">Status</th>
                        <th class="p-4 text-center whitespace-nowrap" style="color: var(--text-secondary)">
                            Actions
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(faculty, index) in faculties"
                        :key="faculty.id"
                        class="border-t hover:bg-black/[0.02]"
                        style="border-color: var(--card-border)"
                    >

                        <td class="p-4" style="color: var(--text-secondary)">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-medium" style="color: var(--text-primary)">
                            {{ faculty.full_name }}
                        </td>

                        <td class="p-4" style="color: var(--text-secondary)">
                            {{ faculty.email || '-' }}
                        </td>

                        <td class="p-4" style="color: var(--text-secondary)">
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

                        <td class="p-4" style="color: var(--text-secondary)">
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
                                    v-if="faculty.can_edit"
                                    :href="route('faculty.edit', faculty.id)"
                                    class="btn-edit"
                                >
                                    Edit
                                </Link>

                                <button
                                    v-if="canManageFaculty"
                                    @click="destroy(faculty)"
                                    class="btn-delete"
                                >
                                    Delete
                                </button>

                                <span
                                    v-if="!faculty.can_edit && !canManageFaculty"
                                    class="text-sm"
                                    style="color: var(--text-muted)"
                                >
                                    View only
                                </span>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="faculties.length === 0">

                        <td
                            colspan="9"
                            class="text-center p-8"
                            style="color: var(--text-muted)"
                        >
                            No faculty members found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <FacultyDeleteModal
            :show="deleteModalOpen"
            :faculty="facultyPendingDelete"
            @close="closeDeleteModal"
        />

    </DashboardLayout>
</template>