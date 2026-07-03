<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useFlashToast } from '@/Composables/useFlashToast'

const props = defineProps({
    curricula: Array,
    programs: Array,
    filters: {
        type: Object,
        default: () => ({ search: '', program_id: null, status: null }),
    },
})

/*
|--------------------------------------------------------------------------
| Search / Program / Status filtering
|--------------------------------------------------------------------------
|
| The URL query string is the source of truth for the current filter
| state, and these refs are just the form-bound mirror of it. Changes
| re-query immediately; Search debounces on typing but also submits
| instantly on Enter. Every visit uses preserveState + replace so
| filtering never pollutes browser history.
|
*/

const search = ref(props.filters.search ?? '')
const programId = ref(props.filters.program_id ?? '')
const status = ref(props.filters.status ?? '')

let searchDebounce = null

function applyFilters() {
    router.get(route('curriculums.index'), {
        search: search.value || undefined,
        program_id: programId.value || undefined,
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function applyFiltersNow() {
    clearTimeout(searchDebounce)
    applyFilters()
}

watch(search, () => {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(applyFilters, 300)
})

watch([programId, status], applyFilters)

onBeforeUnmount(() => clearTimeout(searchDebounce))

const hasActiveFilters = computed(() =>
    !!search.value || !!programId.value || !!status.value
)

// Server-driven success/warning/error toasts
const { toast, show } = useFlashToast()

/*
|--------------------------------------------------------------------------
| Delete — Double Confirmation
|--------------------------------------------------------------------------
|
| Same pattern as Sections:
|   Step 1: a plain "Delete Curriculum?" confirm dialog.
|   Step 2: a modal requiring the user to type the curriculum code
|           exactly before the Delete button enables.
|
*/

const pendingCurriculum = ref(null)
const blockedCurriculum = ref(null)
const confirmingCurriculum = ref(null)
const deleteConfirmText = ref('')

const deleteConfirmValid = computed(() =>
    !!confirmingCurriculum.value
    && deleteConfirmText.value === confirmingCurriculum.value.code
)

const deleteConfirmMismatch = computed(() =>
    deleteConfirmText.value.length > 0 && !deleteConfirmValid.value
)

function requestDelete(curriculum) {
    if (curriculum.has_sections || curriculum.has_items) {
        blockedCurriculum.value = curriculum
        return
    }

    pendingCurriculum.value = curriculum
}

function proceedToTypedConfirm() {
    confirmingCurriculum.value = pendingCurriculum.value
    pendingCurriculum.value = null
    deleteConfirmText.value = ''
}

function cancelDelete() {
    pendingCurriculum.value = null
    blockedCurriculum.value = null
    confirmingCurriculum.value = null
    deleteConfirmText.value = ''
}

function finalizeDelete() {
    if (!deleteConfirmValid.value || !confirmingCurriculum.value) {
        return
    }

    const curriculum = confirmingCurriculum.value

    router.delete(route('curriculums.destroy', curriculum.id), {
        onFinish: () => cancelDelete(),
        onError: () => show('Unable to delete the selected curriculum.', 'error'),
    })
}

function curriculumLabel(curriculum) {
    if (curriculum.specialization) {
        return `${curriculum.program.code} - ${curriculum.specialization.name}`
    }

    return curriculum.program.code
}
</script>

<template>
    <DashboardLayout>

        <Head title="Curriculums" />

        <Toast :toast="toast" />

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">

            <div>
                <h1 class="text-3xl font-bold">
                    Curriculums
                </h1>

                <p class="text-gray-500 mt-1">
                    Manage curriculums and their assigned subjects.
                </p>
            </div>

            <Link
                :href="route('curriculums.create')"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                + Add Curriculum
            </Link>

        </div>

        <!-- Toolbar with Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-col sm:flex-row gap-3">

            <input
                v-model="search"
                type="text"
                placeholder="Search curriculum code or name..."
                class="w-full sm:flex-1 border rounded p-2"
                @keyup.enter="applyFiltersNow"
            >

            <select
                v-model="programId"
                class="w-full sm:w-56 border rounded p-2"
            >
                <option value="">All Programs</option>
                <option
                    v-for="program in programs"
                    :key="program.id"
                    :value="program.id"
                >
                    {{ program.code }} - {{ program.name }}
                </option>
            </select>

            <select
                v-model="status"
                class="w-full sm:w-40 border rounded p-2"
            >
                <option value="">All</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

        </div>

        <!-- Table or Empty State -->
        <div v-if="curricula.length === 0" class="bg-white rounded-lg shadow p-12 text-center">

            <div class="text-gray-400 mb-3">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>

            <h3 class="text-lg font-semibold text-gray-700 mb-2">
                {{ hasActiveFilters ? 'No curriculums match your filters' : 'No curriculums found' }}
            </h3>

            <p class="text-gray-500 mb-6">
                {{ hasActiveFilters ? 'Try adjusting your search criteria.' : 'Create your first curriculum to get started.' }}
            </p>

            <Link
                v-if="!hasActiveFilters"
                :href="route('curriculums.create')"
                class="inline-block bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                + Add Curriculum
            </Link>

        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-x-auto">

            <table class="w-full min-w-[800px]">

                <thead class="bg-gray-100">

                    <tr>
                        <th class="p-4 text-left w-12">#</th>
                        <th class="p-4 text-left">Code</th>
                        <th class="p-4 text-left">Name</th>
                        <th class="p-4 text-left">Program</th>
                        <th class="p-4 text-left">Academic Year</th>
                        <th class="p-4 text-center">Effective Year</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-center whitespace-nowrap">Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(curriculum, index) in curricula"
                        :key="curriculum.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="p-4">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-semibold">
                            {{ curriculum.code }}
                        </td>

                        <td class="p-4">
                            {{ curriculum.name }}
                        </td>

                        <td class="p-4">
                            {{ curriculumLabel(curriculum) }}
                            <span class="text-gray-400 text-xs block">
                                {{ curriculum.program.department?.abbreviation }}
                            </span>
                        </td>

                        <td class="p-4">
                            {{ curriculum.academic_year }}
                        </td>

                        <td class="p-4 text-center">
                            {{ curriculum.effective_year }}
                        </td>

                        <td class="p-4 text-center">

                            <span
                                v-if="curriculum.active"
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

                        <td class="p-4 text-center whitespace-nowrap">

                            <div class="flex justify-center gap-2">

                                <Link
                                    :href="route('curriculums.items.manage', curriculum.id)"
                                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded text-sm"
                                >
                                    Manage Subjects
                                </Link>

                                <Link
                                    :href="route('curriculums.edit', curriculum.id)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="requestDelete(curriculum)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <!-- Step 1: Delete Curriculum? -->
        <div
            v-if="pendingCurriculum"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2">
                    Delete Curriculum?
                </h3>

                <p class="text-gray-500 text-sm mb-6">
                    {{ pendingCurriculum.code }} ({{ pendingCurriculum.name }})
                    will be permanently removed.
                    <br><br>
                    This action cannot be undone.
                </p>

                <div class="flex justify-end gap-2">

                    <button
                        type="button"
                        @click="cancelDelete"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="proceedToTypedConfirm"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                    >
                        Continue
                    </button>

                </div>

            </div>
        </div>

        <!-- Blocked: curriculum is in use -->
        <div
            v-if="blockedCurriculum"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2">
                    Unable to Delete
                </h3>

                <p class="text-gray-500 text-sm mb-6">
                    This curriculum is currently being used by the system.
                    <br><br>
                    Please remove all related sections and subjects before deleting this curriculum.
                </p>

                <div class="flex justify-end">

                    <button
                        type="button"
                        @click="cancelDelete"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded"
                    >
                        OK
                    </button>

                </div>

            </div>
        </div>

        <!-- Step 2: Type the curriculum code to confirm -->
        <div
            v-if="confirmingCurriculum"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2">
                    Final Confirmation
                </h3>

                <p class="text-gray-500 text-sm mb-4">
                    To prevent accidental deletion, please type the curriculum code.
                </p>

                <p class="text-sm text-gray-500 mb-2">
                    Type:
                    <span class="font-mono font-semibold text-gray-800">
                        {{ confirmingCurriculum.code }}
                    </span>
                </p>

                <input
                    v-model="deleteConfirmText"
                    type="text"
                    :placeholder="confirmingCurriculum.code"
                    autocomplete="off"
                    spellcheck="false"
                    class="w-full border rounded p-2 font-mono uppercase"
                    @keyup.enter="finalizeDelete"
                >

                <p v-if="deleteConfirmMismatch" class="text-red-500 text-sm mt-1">
                    Curriculum code does not match.
                </p>

                <div class="flex justify-end gap-2 mt-6">

                    <button
                        type="button"
                        @click="cancelDelete"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="finalizeDelete"
                        :disabled="!deleteConfirmValid"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Delete Curriculum
                    </button>

                </div>

            </div>
        </div>

    </DashboardLayout>
</template>