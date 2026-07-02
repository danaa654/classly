<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Form from './Partials/Form.vue';

const props = defineProps({
    academicTerms: { type: Array, required: true },
    sections: { type: Array, required: true },
    curriculumItems: { type: Array, required: true },
    faculties: { type: Array, required: true },
    facultySubjects: { type: Array, required: true },
});

// Pre-select the currently active term, if any — saves a click on the
// most common path (loading faculty for the term that's open now).
const activeTermId = props.academicTerms.find((term) => term.active)?.id ?? null;

const form = useForm({
    academic_term_id: activeTermId,
    section_id: null,
    curriculum_item_id: null,
    faculty_id: null,
    remarks: '',
    active: true,
});

const submit = () => {
    form.post(route('teaching-assignments.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="New Teaching Assignment" />

        <div class="mx-auto max-w-2xl px-8 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">New Teaching Assignment</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Assign a faculty member to teach a subject for a section this term.
                    </p>
                </div>
                <Link :href="route('teaching-assignments.index')" class="text-sm text-gray-500 hover:text-gray-900">
                    ← Back
                </Link>
            </div>

            <form
                @submit.prevent="submit"
                class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                <Form
                    :form="form"
                    :academic-terms="academicTerms"
                    :sections="sections"
                    :curriculum-items="curriculumItems"
                    :faculties="faculties"
                    :faculty-subjects="facultySubjects"
                />

                <div class="mt-8 flex justify-end gap-3">
                    <Link
                        :href="route('teaching-assignments.index')"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-900"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Saving...' : 'Create Assignment' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>