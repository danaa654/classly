<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { watch } from 'vue'

const props = defineProps({
    faculty: Object,
    departments: Array,
})

const form = useForm({
    first_name: props.faculty.first_name,
    middle_name: props.faculty.middle_name ?? '',
    last_name: props.faculty.last_name,
    suffix: props.faculty.suffix ?? '',

    gender: props.faculty.gender ?? '',

    email: props.faculty.email ?? '',
    contact_number: props.faculty.contact_number ?? '',

    department_id: props.faculty.department_id ?? '',

    employment_type: props.faculty.employment_type,

    max_units: props.faculty.max_units,

    teaching_qualification: props.faculty.teaching_qualification,

    status: props.faculty.status,
})

watch(() => form.employment_type, (value) => {
    if (value === 'Full-Time') {
        form.max_units = 24
    }
})

function submit() {
    form.put(route('faculty.update', props.faculty.id))
}
</script>

<template>
    <DashboardLayout>
        <div class="max-w-5xl">
            <h1 class="text-3xl font-bold mb-6">
                Edit Faculty
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-lg shadow p-6 space-y-6"
            >

                <!-- Personal Information -->
                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label class="block mb-2">First Name</label>
                        <input
                            v-model="form.first_name"
                            class="w-full border rounded p-2"
                        />
                        <p v-if="form.errors.first_name" class="text-red-500 text-sm">
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <div>
                        <label class="block mb-2">Middle Name</label>
                        <input
                            v-model="form.middle_name"
                            class="w-full border rounded p-2"
                        />
                    </div>

                    <div>
                        <label class="block mb-2">Last Name</label>
                        <input
                            v-model="form.last_name"
                            class="w-full border rounded p-2"
                        />
                        <p v-if="form.errors.last_name" class="text-red-500 text-sm">
                            {{ form.errors.last_name }}
                        </p>
                    </div>

                    <div>
                        <label class="block mb-2">Suffix</label>
                        <input
                            v-model="form.suffix"
                            class="w-full border rounded p-2"
                        />
                    </div>

                </div>

                <!-- Gender -->
                <div>
                    <label class="block mb-2">Gender</label>

                    <select
                        v-model="form.gender"
                        class="w-full border rounded p-2"
                    >
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <!-- Contact -->
                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label class="block mb-2">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full border rounded p-2"
                        />
                        <p v-if="form.errors.email" class="text-red-500 text-sm">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="block mb-2">Contact Number</label>
                        <input
                            v-model="form.contact_number"
                            class="w-full border rounded p-2"
                        />
                    </div>

                </div>

                <!-- Home College -->
                <div>

                    <label class="block mb-2">
                        Home College
                    </label>

                    <select
                        v-model="form.department_id"
                        class="w-full border rounded p-2"
                    >
                        <option value="">
                            General Education Faculty
                        </option>

                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="department.id"
                        >
                            {{ department.name }}
                        </option>

                    </select>

                </div>

                <!-- Employment -->
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
                        />

                    </div>

                </div>

                <!-- Teaching Qualification -->
                <div>

                    <label class="block mb-2">
                        Teaching Qualification
                    </label>

                    <select
                        v-model="form.teaching_qualification"
                        class="w-full border rounded p-2"
                    >
                        <option value="Major">Major</option>
                        <option value="Minor">Minor</option>
                        <option value="Both">Both</option>
                    </select>

                </div>

                <!-- Status -->
                <div>

                    <label class="block mb-2">
                        Status
                    </label>

                    <select
                        v-model="form.status"
                        class="w-full border rounded p-2"
                    >
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </select>

                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded"
                >
                    Update Faculty
                </button>

            </form>
        </div>
    </DashboardLayout>
</template>