<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Form from './Partials/Form.vue';

const props = defineProps({
    teachingAssignment: { type: Object, required: true },
    academicTerms: { type: Array, required: true },
    sections: { type: Array, required: true },
    curriculumItems: { type: Array, required: true },
    faculties: { type: Array, required: true },
    facultySubjects: { type: Array, required: true },
});

const form = useForm({
    academic_term_id: props.teachingAssignment.academic_term_id,
    section_id: props.teachingAssignment.section_id,
    curriculum_item_id: props.teachingAssignment.curriculum_item_id,
    faculty_id: props.teachingAssignment.faculty_id,
    remarks: props.teachingAssignment.remarks ?? '',
    active: props.teachingAssignment.active,
});

const submit = () => {
    form.put(route('teaching-assignments.update', props.teachingAssignment.id));
};
</script>

<template>
    <AppLayout>
        <Head title="Configure Faculty Load" />

        <div class="mx-auto max-w-2xl px-8 py-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Configure Faculty Load</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Update the assigned faculty, section, or remarks for this subject load.
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
                        {{ form.processing ? 'Saving...' : 'Update Assignment' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>