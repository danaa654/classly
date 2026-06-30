<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    roles: Array,
    departments: Array,
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    department_id: '',
})

function submit() {
    form.post('/users')
}
</script>

<template>
    <DashboardLayout>
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold mb-6">
                Add User
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-lg shadow p-6 space-y-5"
            >

                <!-- Name -->
                <div>
                    <label class="block mb-2 font-medium">
                        Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border rounded p-2"
                    />

                    <p
                        v-if="form.errors.name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block mb-2 font-medium">
                        Email
                    </label>

                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full border rounded p-2"
                    />

                    <p
                        v-if="form.errors.email"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block mb-2 font-medium">
                        Password
                    </label>

                    <input
                        v-model="form.password"
                        type="password"
                        class="w-full border rounded p-2"
                    />

                    <p
                        v-if="form.errors.password"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Role -->
                <div>
                    <label class="block mb-2 font-medium">
                        Role
                    </label>

                    <select
                        v-model="form.role"
                        class="w-full border rounded p-2"
                    >
                        <option value="">
                            Select Role
                        </option>

                        <option
                            v-for="role in roles"
                            :key="role.id"
                            :value="role.name"
                        >
                            {{ role.name }}
                        </option>
                    </select>

                    <p
                        v-if="form.errors.role"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.role }}
                    </p>
                </div>

                <!-- Department -->
                    <div v-if="form.role === 'Dean' || form.role === 'OIC'">
                        <label class="block mb-2 font-medium">
                            Department
                        </label>

                        <select
                            v-model="form.department_id"
                            class="w-full border rounded p-2"
                        >
                            <option value="">Select Department</option>

                            <option
                                v-for="department in departments"
                                :key="department.id"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.department_id"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ form.errors.department_id }}
                        </p>
                    </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-green-500 text-white px-5 py-2 rounded hover:bg-green-600 disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save User' }}
                </button>

            </form>
        </div>
    </DashboardLayout>
</template>