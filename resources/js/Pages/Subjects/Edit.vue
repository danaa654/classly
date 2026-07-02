<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    subject: Object,
    subjects: Array,
})

const form = useForm({
    subject_code: props.subject.subject_code,
    descriptive_title: props.subject.descriptive_title,
    units: props.subject.units,
    lecture_hours: props.subject.lecture_hours,
    laboratory_hours: props.subject.laboratory_hours,
    is_major: props.subject.is_major,
    required_room_type: props.subject.required_room_type,
    required_room_group: props.subject.required_room_group,
    is_practicum: props.subject.is_practicum,
    allow_split_schedule: props.subject.allow_split_schedule,
    prerequisite_id: props.subject.prerequisite_id ?? '',
    active: props.subject.active,
})

const totalHours = computed(() => {
    return (Number(form.lecture_hours) || 0) + (Number(form.laboratory_hours) || 0)
})

// Room Group only makes sense when the subject actually needs a room.
const roomGroupDisabled = computed(() => form.required_room_type === 'None')

// "General" is a Lecture-only room group — Laboratory subjects must pick a
// specific program, so General is hidden from the dropdown whenever Room
// Type is Laboratory (enforced server-side too, this is just UX).
const roomGroupOptions = computed(() => {
    const all = [
        { value: 'General', label: 'General' },
        { value: 'BSIT', label: 'BSIT' },
        { value: 'BSED', label: 'BSED' },
        { value: 'BSHM', label: 'BSHM' },
        { value: 'BSTM', label: 'BSTM' },
        { value: 'BSCRIM', label: 'BSCRIM' },
    ]

    if (form.required_room_type === 'Laboratory') {
        return all.filter(option => option.value !== 'General')
    }

    return all
})

// Checking Practicum/OJT forces Room Type to "None" and locks the dropdown,
// since a Practicum subject never gets assigned a room.
watch(() => form.is_practicum, (isPracticum) => {
    if (isPracticum) {
        form.required_room_type = 'None'
    } else if (form.required_room_type === 'None') {
        form.required_room_type = 'Lecture'
    }
})

// Keeps Required Room Group in sync with Room Type. The backend enforces
// all of this too (a disabled/tampered field can't smuggle in a bad
// value), but mirroring it here keeps the form from ever showing/
// submitting a value that doesn't make sense for the selected room type:
//   - None          -> room group cleared (Practicum/OJT gets no room)
//   - Lecture        -> defaults to "General" (standard classrooms)
//   - Laboratory     -> "General" isn't valid, so it's cleared and the
//                        user must explicitly pick a program
watch(() => form.required_room_type, (roomType) => {
    if (roomType === 'None') {
        form.required_room_group = null
    } else if (roomType === 'Lecture') {
        form.required_room_group = 'General'
    } else if (roomType === 'Laboratory') {
        if (!form.required_room_group || form.required_room_group === 'General') {
            form.required_room_group = null
        }
    }
})

function submit() {
    form.put(route('subjects.update', props.subject.id))
}
</script>

<template>

<Head title="Edit Subject" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold">
                Edit Subject
            </h1>

            <p class="text-gray-500 mt-1">
                Update {{ subject.subject_code }} — {{ subject.descriptive_title }}
            </p>

        </div>

        <Link
            :href="route('subjects.index')"
            class="text-gray-600 hover:underline"
        >
            &larr; Back to Subjects
        </Link>

    </div>

    <!-- Form -->

    <form
        @submit.prevent="submit"
        class="bg-white rounded-lg shadow p-6 space-y-6"
    >

        <!-- Subject Code / Descriptive Title -->

        <div class="grid grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Subject Code
                </label>

                <input
                    v-model="form.subject_code"
                    type="text"
                    placeholder="e.g. IT101"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.subject_code" class="text-red-600 text-sm mt-1">
                    {{ form.errors.subject_code }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Descriptive Title
                </label>

                <input
                    v-model="form.descriptive_title"
                    type="text"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.descriptive_title" class="text-red-600 text-sm mt-1">
                    {{ form.errors.descriptive_title }}
                </p>

            </div>

        </div>

        <!-- Units / Hours -->

        <div class="grid grid-cols-4 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Units
                </label>

                <input
                    v-model.number="form.units"
                    type="number"
                    min="1"
                    max="6"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.units" class="text-red-600 text-sm mt-1">
                    {{ form.errors.units }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lecture Hours
                </label>

                <input
                    v-model.number="form.lecture_hours"
                    type="number"
                    min="0"
                    max="10"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.lecture_hours" class="text-red-600 text-sm mt-1">
                    {{ form.errors.lecture_hours }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Laboratory Hours
                </label>

                <input
                    v-model.number="form.laboratory_hours"
                    type="number"
                    min="0"
                    max="10"
                    class="w-full border-gray-300 rounded-lg"
                />

                <p v-if="form.errors.laboratory_hours" class="text-red-600 text-sm mt-1">
                    {{ form.errors.laboratory_hours }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Total Hours
                </label>

                <input
                    :value="totalHours"
                    type="number"
                    disabled
                    class="w-full border-gray-300 rounded-lg bg-gray-100 text-gray-500"
                />

            </div>

        </div>

        <!-- Classification / Required Room Type / Required Room Group -->

        <div class="grid grid-cols-3 gap-4">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Classification
                </label>

                <select
                    v-model="form.is_major"
                    class="w-full border-gray-300 rounded-lg"
                >
                    <option :value="true">Major</option>
                    <option :value="false">Minor</option>
                </select>

                <p v-if="form.errors.is_major" class="text-red-600 text-sm mt-1">
                    {{ form.errors.is_major }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Required Room Type
                </label>

                <select
                    v-model="form.required_room_type"
                    :disabled="form.is_practicum"
                    class="w-full border-gray-300 rounded-lg disabled:bg-gray-100 disabled:text-gray-500"
                >
                    <option value="Lecture">Lecture</option>
                    <option value="Laboratory">Laboratory</option>
                    <option value="None">None (Practicum/OJT)</option>
                </select>

                <p v-if="form.errors.required_room_type" class="text-red-600 text-sm mt-1">
                    {{ form.errors.required_room_type }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Required Room Group
                </label>

                <select
                    v-model="form.required_room_group"
                    :disabled="roomGroupDisabled"
                    class="w-full border-gray-300 rounded-lg disabled:bg-gray-100 disabled:text-gray-500"
                >
                    <option
                        v-for="option in roomGroupOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>

                <p v-if="form.errors.required_room_group" class="text-red-600 text-sm mt-1">
                    {{ form.errors.required_room_group }}
                </p>

            </div>

        </div>

        <!-- Prerequisite -->

        <div>

            <label class="block text-sm font-medium text-gray-700 mb-1">
                Prerequisite (optional)
            </label>

            <select
                v-model="form.prerequisite_id"
                class="w-full border-gray-300 rounded-lg"
            >
                <option value="">None</option>

                <option
                    v-for="subject in subjects"
                    :key="subject.id"
                    :value="subject.id"
                >
                    {{ subject.subject_code }} - {{ subject.descriptive_title }}
                </option>
            </select>

            <p v-if="form.errors.prerequisite_id" class="text-red-600 text-sm mt-1">
                {{ form.errors.prerequisite_id }}
            </p>

        </div>

        <!-- Toggles -->

        <div class="flex items-center gap-8">

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.allow_split_schedule" class="rounded" />
                <span class="text-sm text-gray-700">Allow Split Schedule</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.active" class="rounded" />
                <span class="text-sm text-gray-700">Active</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.is_practicum" class="rounded" />
                <span class="text-sm text-gray-700">Practicum/OJT</span>
            </label>

        </div>

        <!-- Actions -->

        <div class="flex justify-end gap-3 pt-4 border-t">

            <Link
                :href="route('subjects.index')"
                class="px-5 py-2 rounded-lg border text-gray-600 hover:bg-gray-50"
            >
                Cancel
            </Link>

            <button
                type="submit"
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg disabled:opacity-50"
            >
                Update Subject
            </button>

        </div>

    </form>

</div>

</template>