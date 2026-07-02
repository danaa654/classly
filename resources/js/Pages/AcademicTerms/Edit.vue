<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const SEMESTERS = [
    { value: 1, label: '1st Semester' },
    { value: 2, label: '2nd Semester' },
    { value: 3, label: 'Summer' },
]

const DAYS = [
    { key: 'monday', label: 'Monday' },
    { key: 'tuesday', label: 'Tuesday' },
    { key: 'wednesday', label: 'Wednesday' },
    { key: 'thursday', label: 'Thursday' },
    { key: 'friday', label: 'Friday' },
    { key: 'saturday', label: 'Saturday' },
    { key: 'sunday', label: 'Sunday' },
]

const props = defineProps({
    academicTerm: Object,
})

const form = useForm({
    academic_year: props.academicTerm.academic_year,
    semester: props.academicTerm.semester,

    registration_start_date: props.academicTerm.registration_start_date,
    registration_end_date: props.academicTerm.registration_end_date,

    class_start_date: props.academicTerm.class_start_date,
    class_end_date: props.academicTerm.class_end_date,

    school_start_time: props.academicTerm.school_start_time,
    school_end_time: props.academicTerm.school_end_time,

    lunch_start_time: props.academicTerm.lunch_start_time,
    lunch_end_time: props.academicTerm.lunch_end_time,

    time_interval: props.academicTerm.time_interval,

    monday: props.academicTerm.monday,
    tuesday: props.academicTerm.tuesday,
    wednesday: props.academicTerm.wednesday,
    thursday: props.academicTerm.thursday,
    friday: props.academicTerm.friday,
    saturday: props.academicTerm.saturday,
    sunday: props.academicTerm.sunday,

    status: props.academicTerm.status,
    active: props.academicTerm.active,
})

function submit() {
    form.put(route('academic-terms.update', props.academicTerm.id))
}
</script>

<template>
    <DashboardLayout>

        <h1 class="text-3xl font-bold mb-6">
            Edit Academic Term
        </h1>

        <div class="bg-white rounded-lg shadow p-6 max-w-3xl">

            <form @submit.prevent="submit">

                <!-- Academic Period -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Academic Period
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Academic Year
                            </label>

                            <input
                                v-model="form.academic_year"
                                type="text"
                                placeholder="e.g. 2026-2027"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.academic_year" class="text-red-500 text-sm mt-1">
                                {{ form.errors.academic_year }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Semester
                            </label>

                            <select
                                v-model="form.semester"
                                class="w-full border rounded p-2"
                            >
                                <option value="" disabled>
                                    Select a semester
                                </option>
                                <option
                                    v-for="semester in SEMESTERS"
                                    :key="semester.value"
                                    :value="semester.value"
                                >
                                    {{ semester.label }}
                                </option>
                            </select>

                            <p v-if="form.errors.semester" class="text-red-500 text-sm mt-1">
                                {{ form.errors.semester }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Registration Period -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Registration Period
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Registration Start
                            </label>

                            <input
                                v-model="form.registration_start_date"
                                type="date"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.registration_start_date" class="text-red-500 text-sm mt-1">
                                {{ form.errors.registration_start_date }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Registration End
                            </label>

                            <input
                                v-model="form.registration_end_date"
                                type="date"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.registration_end_date" class="text-red-500 text-sm mt-1">
                                {{ form.errors.registration_end_date }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Class Period -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Class Period
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Class Start
                            </label>

                            <input
                                v-model="form.class_start_date"
                                type="date"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.class_start_date" class="text-red-500 text-sm mt-1">
                                {{ form.errors.class_start_date }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Class End
                            </label>

                            <input
                                v-model="form.class_end_date"
                                type="date"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.class_end_date" class="text-red-500 text-sm mt-1">
                                {{ form.errors.class_end_date }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- School Hours -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        School Hours
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                School Start Time
                            </label>

                            <input
                                v-model="form.school_start_time"
                                type="time"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.school_start_time" class="text-red-500 text-sm mt-1">
                                {{ form.errors.school_start_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                School End Time
                            </label>

                            <input
                                v-model="form.school_end_time"
                                type="time"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.school_end_time" class="text-red-500 text-sm mt-1">
                                {{ form.errors.school_end_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Lunch Start
                                <span class="text-gray-400 font-normal">(optional)</span>
                            </label>

                            <input
                                v-model="form.lunch_start_time"
                                type="time"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.lunch_start_time" class="text-red-500 text-sm mt-1">
                                {{ form.errors.lunch_start_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block font-medium mb-1">
                                Lunch End
                                <span class="text-gray-400 font-normal">(optional)</span>
                            </label>

                            <input
                                v-model="form.lunch_end_time"
                                type="time"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.lunch_end_time" class="text-red-500 text-sm mt-1">
                                {{ form.errors.lunch_end_time }}
                            </p>
                        </div>

                    </div>

                </div>

                <!-- Scheduler Settings -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Scheduler Settings
                    </h2>

                    <div class="max-w-xs">
                        <label class="block font-medium mb-1">
                            Time Interval (minutes)
                        </label>

                        <input
                            v-model="form.time_interval"
                            type="number"
                            min="5"
                            max="120"
                            class="w-full border rounded p-2"
                        >

                        <p v-if="form.errors.time_interval" class="text-red-500 text-sm mt-1">
                            {{ form.errors.time_interval }}
                        </p>
                    </div>

                </div>

                <!-- Working Days -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Working Days
                    </h2>

                    <div class="flex flex-wrap gap-4">

                        <label
                            v-for="day in DAYS"
                            :key="day.key"
                            class="flex items-center gap-2 border rounded px-3 py-2 cursor-pointer"
                        >
                            <input
                                v-model="form[day.key]"
                                type="checkbox"
                            >
                            {{ day.label }}
                        </label>

                    </div>

                </div>

                <!-- Status & Activation -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Status & Activation
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">

                        <div>
                            <label class="block font-medium mb-1">
                                Status
                            </label>

                            <select
                                v-model="form.status"
                                class="w-full border rounded p-2"
                            >
                                <option value="Draft">Draft</option>
                                <option value="Published">Published</option>
                                <option value="Archived">Archived</option>
                            </select>

                            <p v-if="form.errors.status" class="text-red-500 text-sm mt-1">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 border rounded px-3 py-2 cursor-pointer mt-1">
                                <input
                                    v-model="form.active"
                                    type="checkbox"
                                >
                                Set as the active Academic Term
                            </label>

                            <p class="text-gray-400 text-sm mt-1">
                                Activating this term will automatically deactivate any other active term.
                            </p>
                        </div>

                    </div>

                </div>

                <div class="flex justify-end gap-2">

                    <Link
                        :href="route('academic-terms.index')"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded disabled:opacity-50"
                    >
                        Update Academic Term
                    </button>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>