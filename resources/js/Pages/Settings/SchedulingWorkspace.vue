<script setup>
import { computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    activeTerm: Object,
    planningTerm: Object,
    academicTerms: Array,
    can: Object,
});

const form = useForm({
    academic_term_id: props.planningTerm?.id ?? null,
});

const submit = () => {
    form.put(route('settings.scheduling-workspace.update'), {
        preserveScroll: true,
    });
};

const isDirty = computed(
    () => form.academic_term_id !== (props.planningTerm?.id ?? null)
);
</script>

<template>
    <Head title="Scheduling Workspace" />

    <AppLayout>
        <div class="mx-auto max-w-3xl space-y-6">

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Settings &raquo; Scheduling Workspace
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Control which Academic Term the whole system reports on, and which one the
                    scheduling modules use while a semester is being prepared.
                </p>
            </div>

            <!-- Active Academic Term (read-only, everyone) -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="p-6">
                    <h3 class="text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">
                        Active Academic Term
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                        The official semester currently running. Used by every operational module
                        (Dashboard, Reports, Student Records, Enrollment, Grades, Attendance) and
                        changed only when the Administrator officially activates a new term.
                    </p>

                    <div v-if="activeTerm" class="mt-4 rounded-md border border-emerald-300 bg-emerald-50 px-4 py-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            AY {{ activeTerm.academic_year }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-slate-300">{{ activeTerm.semester_label }}</p>
                        <span class="mt-1 inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                            Status: Active
                        </span>
                    </div>
                    <div v-else class="mt-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">
                        No Academic Term is currently active.
                    </div>
                </div>
            </div>

            <!-- Planning / Working Academic Term (editable by Admin/Registrar only) -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form @submit.prevent="submit">
                    <div class="p-6">
                        <h3 class="text-sm font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">
                            Planning Academic Term
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                            The semester currently being prepared by the scheduling team. All
                            scheduling modules — Subject Offerings, Faculty Loading, Teaching
                            Assignments, Room Management, Master Grid, and Conflict Detection —
                            automatically use this term instead of the Active term, so the
                            Registrar can prepare schedules months before a semester officially
                            starts. This is also shown as the "Working Term" in the top bar.
                        </p>

                        <div class="mt-4">
                            <label for="academic_term_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                                Select Academic Term
                            </label>

                            <select
                                id="academic_term_id"
                                v-model="form.academic_term_id"
                                :disabled="!can.edit"
                                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                            >
                                <option
                                    v-for="term in academicTerms"
                                    :key="term.id"
                                    :value="term.id"
                                >
                                    AY {{ term.academic_year }} &bull; {{ term.semester_label }}
                                </option>
                            </select>

                            <p v-if="form.errors.academic_term_id" class="mt-1 text-sm text-rose-500">
                                {{ form.errors.academic_term_id }}
                            </p>

                            <p v-if="!can.edit" class="mt-2 text-sm text-gray-500 dark:text-slate-500">
                                You have view-only access to the Scheduling Workspace. Only an
                                Administrator or Registrar can change the Planning Academic Term.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="can.edit"
                        class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-900/40"
                    >
                        <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600 dark:text-emerald-400">
                            Saved.
                        </span>
                        <button
                            type="submit"
                            :disabled="form.processing || !isDirty"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </AppLayout>
</template>