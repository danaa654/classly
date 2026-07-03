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
                <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                    Curriculums
                </h1>

                <p class="text-[var(--text-muted)] mt-1">
                    Manage curriculums and their assigned subjects.
                </p>
            </div>

            <Link
                :href="route('curriculums.create')"
                class="btn-save"
            >
                + Add Curriculum
            </Link>

        </div>

        <!-- Toolbar with Filters -->
        <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-4 mb-4 flex flex-col sm:flex-row gap-3 transition-colors duration-300">

            <input
                v-model="search"
                type="text"
                placeholder="Search curriculum code or name..."
                class="w-full sm:flex-1 rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                @keyup.enter="applyFiltersNow"
            >

            <select
                v-model="programId"
                class="w-full sm:w-56 rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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
                class="w-full sm:w-40 rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
            >
                <option value="">All</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>

        </div>

        <!-- Table or Empty State -->
        <div v-if="curricula.length === 0" class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-12 text-center transition-colors duration-300">

            <div class="text-[var(--text-muted)] mb-3">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>

            <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">
                {{ hasActiveFilters ? 'No curriculums match your filters' : 'No curriculums found' }}
            </h3>

            <p class="text-[var(--text-muted)] mb-6">
                {{ hasActiveFilters ? 'Try adjusting your search criteria.' : 'Create your first curriculum to get started.' }}
            </p>

            <Link
                v-if="!hasActiveFilters"
                :href="route('curriculums.create')"
                class="inline-block btn-save"
            >
                + Add Curriculum
            </Link>

        </div>

        <div v-else class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-x-auto transition-colors duration-300">

            <table class="w-full min-w-[800px]">

                <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">

                    <tr>
                        <th class="p-4 text-left w-12 text-[var(--text-secondary)]">#</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Code</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Name</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Program</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Academic Year</th>
                        <th class="p-4 text-center text-[var(--text-secondary)]">Effective Year</th>
                        <th class="p-4 text-center text-[var(--text-secondary)]">Status</th>
                        <th class="p-4 text-center whitespace-nowrap text-[var(--text-secondary)]">Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(curriculum, index) in curricula"
                        :key="curriculum.id"
                        class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-semibold text-[var(--text-primary)]">
                            {{ curriculum.code }}
                        </td>

                        <td class="p-4 text-[var(--text-primary)]">
                            {{ curriculum.name }}
                        </td>

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ curriculumLabel(curriculum) }}
                            <span class="text-[var(--text-muted)] text-xs block">
                                {{ curriculum.program.department?.abbreviation }}
                            </span>
                        </td>

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ curriculum.academic_year }}
                        </td>

                        <td class="p-4 text-center text-[var(--text-secondary)]">
                            {{ curriculum.effective_year }}
                        </td>

                        <td class="p-4 text-center">

                            <span
                                v-if="curriculum.active"
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

                        <td class="p-4 text-center whitespace-nowrap">

                            <div class="flex justify-center gap-2">

                                <Link
                                    :href="route('curriculums.items.manage', curriculum.id)"
                                    class="btn-info"
                                >
                                    Manage Subjects
                                </Link>

                                <Link
                                    :href="route('curriculums.edit', curriculum.id)"
                                    class="btn-edit"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="requestDelete(curriculum)"
                                    class="btn-delete"
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
            <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">
                    Delete Curriculum?
                </h3>

                <p class="text-[var(--text-muted)] text-sm mb-6">
                    {{ pendingCurriculum.code }} ({{ pendingCurriculum.name }})
                    will be permanently removed.
                    <br><br>
                    This action cannot be undone.
                </p>

                <div class="flex justify-end gap-2">

                    <button
                        type="button"
                        @click="cancelDelete"
                        class="btn-neutral"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="proceedToTypedConfirm"
                        class="btn-delete"
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
            <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">
                    Unable to Delete
                </h3>

                <p class="text-[var(--text-muted)] text-sm mb-6">
                    This curriculum is currently being used by the system.
                    <br><br>
                    Please remove all related sections and subjects before deleting this curriculum.
                </p>

                <div class="flex justify-end">

                    <button
                        type="button"
                        @click="cancelDelete"
                        class="btn-neutral"
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
            <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">
                    Final Confirmation
                </h3>

                <p class="text-[var(--text-muted)] text-sm mb-4">
                    To prevent accidental deletion, please type the curriculum code.
                </p>

                <p class="text-sm text-[var(--text-muted)] mb-2">
                    Type:
                    <span class="font-mono font-semibold text-[var(--text-primary)]">
                        {{ confirmingCurriculum.code }}
                    </span>
                </p>

                <input
                    v-model="deleteConfirmText"
                    type="text"
                    :placeholder="confirmingCurriculum.code"
                    autocomplete="off"
                    spellcheck="false"
                    class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] font-mono uppercase transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                    @keyup.enter="finalizeDelete"
                >

                <p v-if="deleteConfirmMismatch" class="text-red-500 text-sm mt-1">
                    Curriculum code does not match.
                </p>

                <div class="flex justify-end gap-2 mt-6">

                    <button
                        type="button"
                        @click="cancelDelete"
                        class="btn-neutral"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="finalizeDelete"
                        :disabled="!deleteConfirmValid"
                        class="btn-delete"
                    >
                        Delete Curriculum
                    </button>

                </div>

            </div>
        </div>

    </DashboardLayout>
</template>