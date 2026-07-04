<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { watch } from 'vue'

const props = defineProps({
    departments: Array,
})

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',

    gender: '',

    email: '',
    contact_number: '',

    faculty_scope: 'departmental',

    department_id: '',

    employment_type: 'Full-Time',

    max_units: 24,

    status: true,
})

watch(
    () => form.employment_type,
    (value) => {
        form.max_units = value === 'Full-Time' ? 24 : 18
    }
)

watch(
    () => form.faculty_scope,
    (value) => {
        if (value === 'general') {
            form.department_id = ''
        }
    }
)

function submit() {
    form.post(route('faculty.store'))
}
</script>

<template>
    <DashboardLayout>

        <div class="max-w-5xl">

            <h1 class="text-3xl font-bold mb-6" style="color: var(--text-primary)">
                Add Faculty Member
            </h1>

            <form
                @submit.prevent="submit"
                class="rounded-lg shadow p-6 space-y-6 border"
                style="background: var(--card-bg); border-color: var(--card-border); color: var(--text-primary)"
            >

                <!-- PERSONAL INFORMATION -->

                <div>

                    <h2 class="text-lg font-semibold mb-4">
                        Personal Information
                    </h2>

                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <label class="block mb-2">First Name</label>

                            <input
                                v-model="form.first_name"
                                type="text"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.first_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.first_name }}
                            </p>
                        </div>

                        <div>
                            <label class="block mb-2">Middle Name</label>

                            <input
                                v-model="form.middle_name"
                                type="text"
                                class="w-full border rounded p-2"
                            >
                        </div>

                        <div>
                            <label class="block mb-2">Last Name</label>

                            <input
                                v-model="form.last_name"
                                type="text"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.last_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.last_name }}
                            </p>
                        </div>

                        <div>
                            <label class="block mb-2">Suffix</label>

                            <input
                                v-model="form.suffix"
                                type="text"
                                class="w-full border rounded p-2"
                                placeholder="Jr., Sr., III"
                            >
                        </div>

                    </div>

                </div>

                <!-- GENDER -->

                <div>

                    <label class="block mb-2">
                        Gender
                    </label>

                    <select
                        v-model="form.gender"
                        class="w-full border rounded p-2"
                    >
                        <option disabled value="">
                            Select Gender
                        </option>

                        <option value="Male">
                            Male
                        </option>

                        <option value="Female">
                            Female
                        </option>
                    </select>

                    <p
                        v-if="form.errors.gender"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.gender }}
                    </p>

                </div>

                <!-- CONTACT -->

                <div>

                    <h2 class="text-lg font-semibold mb-4">
                        Contact Information
                    </h2>

                    <div class="grid grid-cols-2 gap-4">

                        <div>

                            <label class="block mb-2">
                                Email
                            </label>

                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.email"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.email }}
                            </p>

                        </div>

                        <div>

                            <label class="block mb-2">
                                Contact Number
                            </label>

                            <input
                                v-model="form.contact_number"
                                type="text"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.contact_number"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.contact_number }}
                            </p>

                        </div>

                    </div>

                </div>

                <!-- FACULTY SCOPE -->

                <div>

                    <h2 class="text-lg font-semibold mb-4">
                        Teaching Permissions
                    </h2>

                    <label class="block mb-2">
                        Faculty Scope
                    </label>

                    <select
                        v-model="form.faculty_scope"
                        class="w-full border rounded p-2"
                    >
                        <option value="general">
                            General Education
                        </option>

                        <option value="departmental">
                            Departmental
                        </option>

                        <option value="cross_department">
                            Cross Department
                        </option>
                    </select>

                    <p class="text-sm text-gray-500 mt-2">
                        <span v-if="form.faculty_scope === 'general'">
                            Not tied to any department. Can only teach Minor subjects, across all programs.
                        </span>
                        <span v-else-if="form.faculty_scope === 'departmental'">
                            Can teach Major and Minor subjects, but only within their own department.
                        </span>
                        <span v-else>
                            Can teach Major subjects only within their own department, and Minor subjects both inside and outside their department.
                        </span>
                    </p>

                    <p
                        v-if="form.errors.faculty_scope"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.faculty_scope }}
                    </p>

                </div>

                <!-- DEPARTMENT -->

                <div>

                    <label class="block mb-2">
                        Department
                    </label>

                    <select
                        v-model="form.department_id"
                        :disabled="form.faculty_scope === 'general'"
                        class="w-full border rounded p-2 disabled:bg-gray-100 disabled:text-gray-400"
                    >

                        <option value="">
                            Select Department
                        </option>

                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="department.id"
                        >
                            {{ department.abbreviation }} - {{ department.name }}
                        </option>

                    </select>

                    <p
                        v-if="form.faculty_scope === 'general'"
                        class="text-sm text-gray-500 mt-1"
                    >
                        General Education faculty are not assigned to a department.
                    </p>

                    <p
                        v-if="form.errors.department_id"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.department_id }}
                    </p>

                </div>

                <!-- EMPLOYMENT -->

                <div class="grid grid-cols-2 gap-4">

                    <div>

                        <label class="block mb-2">
                            Employment Type
                        </label>

                        <select
                            v-model="form.employment_type"
                            class="w-full border rounded p-2"
                        >
                            <option value="Full-Time">
                                Full-Time
                            </option>

                            <option value="Part-Time">
                                Part-Time
                            </option>

                        </select>

                    </div>

                    <div>

                        <label class="block mb-2">
                            Maximum Units
                        </label>

                        <input
                            v-model="form.max_units"
                            type="number"
                            min="1"
                            max="24"
                            :readonly="form.employment_type === 'Full-Time'"
                            class="w-full border rounded p-2 bg-gray-50"
                        >

                    </div>

                </div>

                <!-- STATUS -->

                <div>

                    <label class="block mb-2">
                        Status
                    </label>

                    <select
                        v-model="form.status"
                        class="w-full border rounded p-2"
                    >
                        <option :value="true">
                            Active
                        </option>

                        <option :value="false">
                            Inactive
                        </option>

                    </select>

                </div>

                <!-- BUTTONS -->

                <div class="flex gap-3">

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn-save"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Faculty' }}
                    </button>

                    <Link
                        :href="route('faculty.index')"
                        class="btn-neutral"
                    >
                        Cancel
                    </Link>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>