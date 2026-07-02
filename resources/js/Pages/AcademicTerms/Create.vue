<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useAcademicTermForm, SEMESTERS, DAYS } from '@/Composables/useAcademicTermForm'
import { useFlashToast } from '@/Composables/useFlashToast'

const form = useForm({
    // The user only ever types the first year (e.g. 2026). The
    // "2026-2027" academic_year string is generated server-side from
    // this value on save — see AcademicTermRequest::validatedForSave().
    start_year: '',
    semester: '',

    class_start_date: '',
    class_end_date: '',

    school_start_time: '',
    school_end_time: '',

    // New terms default to a 12:00 PM - 1:00 PM lunch break. The user can
    // still edit or clear both fields if the institution has none. Edit.vue
    // is unaffected — it always initializes from the saved record instead.
    lunch_start_time: '12:00',
    lunch_end_time: '13:00',

    time_interval: 30,

    monday: true,
    tuesday: true,
    wednesday: true,
    thursday: true,
    friday: true,
    saturday: false,
    sunday: false,

    status: 'Draft',
    active: false,
})

// Shared Start Year -> Academic Year preview, academic-year-aware date
// range restriction + auto-clearing, and inline validation hints. Kept in
// one composable so Create.vue and Edit.vue don't duplicate this logic.
const {
    academicYearPreview,
    semesterLabel,
    dateRange,
    classDatesInvalid,
    schoolHoursInvalid,
    lunchIncomplete,
    lunchOrderInvalid,
    lunchOutsideSchoolHours,
    activeRequiresPublished,
    onStartYearInput,
} = useAcademicTermForm(form)

const { toast } = useFlashToast()

/*
|--------------------------------------------------------------------------
| Save Confirmation
|--------------------------------------------------------------------------
|
| Submitting the form opens a review dialog summarizing the Academic
| Year, Semester, and Class Dates instead of saving immediately. The
| actual POST only fires once the user taps "Confirm Save".
|
*/

const showConfirm = ref(false)

function openConfirm() {
    showConfirm.value = true
}

function confirmSave() {
    showConfirm.value = false
    form.post(route('academic-terms.store'))
}
</script>

<template>
    <DashboardLayout>

        <Toast :toast="toast" />

        <h1 class="text-3xl font-bold mb-6">
            Add Academic Term
        </h1>

        <div class="bg-white rounded-lg shadow p-6 max-w-3xl">

            <form @submit.prevent="openConfirm">

                <!-- Academic Period -->
                <div class="mb-6">

                    <h2 class="text-lg font-semibold mb-3">
                        Academic Period
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block font-medium mb-1">
                                Start Year
                            </label>

                            <input
                                :value="form.start_year"
                                @input="onStartYearInput"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="4"
                                placeholder="e.g. 2026"
                                class="w-full border rounded p-2"
                            >

                            <!-- Live "Academic Year: 2026-2027" preview -->
                            <p v-if="academicYearPreview" class="text-gray-500 text-sm mt-1">
                                Academic Year: <span class="font-medium text-gray-700">{{ academicYearPreview }}</span>
                            </p>

                            <p v-if="form.errors.start_year" class="text-red-500 text-sm mt-1">
                                {{ form.errors.start_year }}
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
                                :min="dateRange.min"
                                :max="dateRange.max"
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
                                :min="dateRange.min"
                                :max="dateRange.max"
                                class="w-full border rounded p-2"
                            >

                            <p v-if="form.errors.class_end_date" class="text-red-500 text-sm mt-1">
                                {{ form.errors.class_end_date }}
                            </p>
                        </div>

                        <!-- Inline hint, in addition to any server-side error -->
                        <p v-if="classDatesInvalid" class="md:col-span-2 text-red-500 text-sm -mt-2">
                            Class End cannot be before Class Start.
                        </p>

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

                        <!-- Inline hint, in addition to any server-side error -->
                        <p v-if="schoolHoursInvalid" class="md:col-span-2 text-red-500 text-sm -mt-2">
                            School End Time must be after School Start Time.
                        </p>

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

                        <!-- Inline hints, in addition to any server-side error -->
                        <p v-if="lunchIncomplete" class="md:col-span-2 text-red-500 text-sm -mt-2">
                            Please fill in both Lunch Start and Lunch End, or leave both blank.
                        </p>
                        <p v-else-if="lunchOrderInvalid" class="md:col-span-2 text-red-500 text-sm -mt-2">
                            Lunch End must be after Lunch Start.
                        </p>
                        <p v-else-if="lunchOutsideSchoolHours" class="md:col-span-2 text-red-500 text-sm -mt-2">
                            Lunch Break must fall within School Hours.
                        </p>

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

                            <!-- Inline hint: only a Published term may be Active -->
                            <p v-if="activeRequiresPublished" class="text-red-500 text-sm mt-1">
                                Only a Published Academic Term can be set as Active.
                            </p>
                            <p v-else-if="form.errors.active" class="text-red-500 text-sm mt-1">
                                {{ form.errors.active }}
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
                        class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded disabled:opacity-50"
                    >
                        Save Academic Term
                    </button>

                </div>

            </form>

        </div>

        <!-- Review Confirmation Modal -->
        <div
            v-if="showConfirm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">

                <h3 class="text-lg font-semibold mb-1">
                    Review Academic Term
                </h3>

                <p class="text-gray-500 text-sm mb-4">
                    Please review the Academic Year, Semester and Class Dates before saving.
                </p>

                <dl class="space-y-2 text-sm mb-6">

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Academic Year</dt>
                        <dd class="font-medium">{{ academicYearPreview ?? '—' }}</dd>
                    </div>

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Semester</dt>
                        <dd class="font-medium">{{ semesterLabel ?? '—' }}</dd>
                    </div>

                    <div class="flex justify-between border-b pb-2">
                        <dt class="text-gray-500">Class Start</dt>
                        <dd class="font-medium">{{ form.class_start_date || '—' }}</dd>
                    </div>

                    <div class="flex justify-between">
                        <dt class="text-gray-500">Class End</dt>
                        <dd class="font-medium">{{ form.class_end_date || '—' }}</dd>
                    </div>

                </dl>

                <div class="flex justify-end gap-2">

                    <button
                        type="button"
                        @click="showConfirm = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="confirmSave"
                        :disabled="form.processing"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded disabled:opacity-50"
                    >
                        Confirm Save
                    </button>

                </div>

            </div>
        </div>

    </DashboardLayout>
</template>