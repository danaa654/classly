<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { useFlashToast } from '@/Composables/useFlashToast'

defineProps({
    academicTerms: Array,
})

// Server-driven success/warning/error toasts (created, updated, archived,
// deleted, and the "can't delete" guards all flow through this).
const { toast, show } = useFlashToast()

function formatDate(value) {
    if (!value) {
        return '-'
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

function formatTime(value) {
    if (!value) {
        return null
    }

    const [hour, minute] = value.split(':')
    const date = new Date()
    date.setHours(hour, minute)

    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    })
}

function statusClasses(status) {
    return {
        Draft: 'bg-gray-100 text-gray-700',
        Published: 'bg-green-100 text-green-700',
        Archived: 'bg-red-100 text-red-700',
    }[status] ?? 'bg-gray-100 text-gray-700'
}

/*
|--------------------------------------------------------------------------
| Delete — Double Confirmation
|--------------------------------------------------------------------------
|
| Step 1: a plain "Delete Academic Term?" confirm dialog.
| Step 2: a modal requiring the user to type DELETE exactly before the
|         Delete button enables. Only then is the destroy request sent.
|
| The active-term guard is also checked client-side first for instant
| feedback, but the server (AcademicTermController::destroy) is the real
| source of truth — it also blocks deletion of terms with scheduling data
| and reports back via a warning toast either way.
|
*/

const pendingTerm = ref(null)   // term selected for the "Delete Academic Term?" step
const confirmingTerm = ref(null) // term in the "type DELETE" step
const deleteConfirmText = ref('')

const deleteConfirmValid = computed(() => deleteConfirmText.value === 'DELETE')

function requestDelete(term) {
    if (term.active) {
        show('The active Academic Term cannot be deleted. Activate another Academic Term first.', 'warning')
        return
    }

    if (term.status === 'Archived') {
        show('Archived Academic Terms are permanent historical record and cannot be deleted.', 'warning')
        return
    }

    pendingTerm.value = term
}

function proceedToTypedConfirm() {
    confirmingTerm.value = pendingTerm.value
    pendingTerm.value = null
    deleteConfirmText.value = ''
}

function cancelDelete() {
    pendingTerm.value = null
    confirmingTerm.value = null
    deleteConfirmText.value = ''
}

function finalizeDelete() {
    if (! deleteConfirmValid.value || ! confirmingTerm.value) {
        return
    }

    const term = confirmingTerm.value

    router.delete(route('academic-terms.destroy', term.id), {
        onFinish: () => cancelDelete(),
        onError: () => show('Something went wrong while deleting this Academic Term.', 'error'),
    })
}
</script>

<template>
    <DashboardLayout>

        <Toast :toast="toast" />

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Academic Terms
            </h1>

            <Link
                :href="route('academic-terms.create')"
                class="btn-save"
            >
                Add Academic Term
            </Link>

        </div>

        <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden transition-colors duration-300">

            <table class="w-full">

                <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">

                    <tr>
                        <th class="p-4 text-left w-12 text-[var(--text-secondary)]">#</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Academic Year</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Semester</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Class Dates</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">School Hours</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Status</th>
                        <th class="p-4 text-left text-[var(--text-secondary)]">Active</th>
                        <th class="p-4 text-center whitespace-nowrap text-[var(--text-secondary)]">
                            Actions
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(term, index) in academicTerms"
                        :key="term.id"
                        class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                    >

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-medium text-[var(--text-primary)]">
                            {{ term.academic_year }}
                        </td>

                        <td class="p-4 text-[var(--text-secondary)]">
                            {{ term.semester_label }}
                        </td>

                        <td class="p-4 whitespace-nowrap text-[var(--text-secondary)]">
                            {{ formatDate(term.class_start_date) }}
                            &ndash;
                            {{ formatDate(term.class_end_date) }}
                        </td>

                        <td class="p-4 whitespace-nowrap text-[var(--text-secondary)]">
                            {{ formatTime(term.school_start_time) }}
                            &ndash;
                            {{ formatTime(term.school_end_time) }}
                        </td>

                        <td class="p-4">
                            <span
                                class="px-3 py-1 rounded-full text-sm"
                                :class="statusClasses(term.status)"
                            >
                                {{ term.status }}
                            </span>
                        </td>

                        <td class="p-4">

                            <span
                                v-if="term.active"
                                class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-sm"
                            >
                                Inactive
                            </span>

                        </td>

                        <td class="p-4 whitespace-nowrap">

                            <div class="flex justify-center gap-2">

                                <Link
                                    v-if="term.status !== 'Archived'"
                                    :href="route('academic-terms.edit', term.id)"
                                    class="btn-edit"
                                >
                                    Edit
                                </Link>

                                <span
                                    v-else
                                    title="Archived Academic Terms are read-only."
                                    class="rounded-full bg-[var(--page-bg)] text-[var(--text-muted)] px-5 py-2 text-sm font-semibold cursor-not-allowed"
                                >
                                    Edit
                                </span>

                                <button
                                    @click="requestDelete(term)"
                                    :disabled="term.active || term.status === 'Archived'"
                                    :title="term.active
                                        ? 'Activate a different term before deleting this one.'
                                        : term.status === 'Archived'
                                            ? 'Archived Academic Terms are permanent historical record and cannot be deleted.'
                                            : 'Delete this Academic Term'"
                                    class="btn-delete"
                                    :class="(term.active || term.status === 'Archived') && 'opacity-50 cursor-not-allowed'"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="academicTerms.length === 0">

                        <td
                            colspan="8"
                            class="text-center p-8 text-[var(--text-muted)]"
                        >
                            No academic terms found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <!-- Step 1: Delete Academic Term? -->
        <div
            v-if="pendingTerm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">
                    Delete Academic Term?
                </h3>

                <p class="text-[var(--text-secondary)] text-sm mb-6">
                    {{ pendingTerm.academic_year }} &bull; {{ pendingTerm.semester_label }} will be permanently removed. This cannot be undone.
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

        <!-- Step 2: Type DELETE to confirm -->
        <div
            v-if="confirmingTerm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow-xl w-full max-w-sm p-6">

                <h3 class="text-lg font-semibold mb-2 text-[var(--text-primary)]">
                    Type DELETE to permanently delete this Academic Term.
                </h3>

                <p class="text-[var(--text-secondary)] text-sm mb-4">
                    {{ confirmingTerm.academic_year }} &bull; {{ confirmingTerm.semester_label }}
                </p>

                <input
                    v-model="deleteConfirmText"
                    type="text"
                    placeholder="DELETE"
                    class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] p-2 mb-6 uppercase tracking-wide"
                    @keyup.enter="finalizeDelete"
                >

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
                        @click="finalizeDelete"
                        :disabled="! deleteConfirmValid"
                        class="btn-delete"
                    >
                        Delete
                    </button>

                </div>

            </div>
        </div>

    </DashboardLayout>
</template>