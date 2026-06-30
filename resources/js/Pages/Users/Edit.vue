<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    user: Object,
    roles: Array,
    departments: Array,
})

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.user.roles[0]?.name ?? '',
    department_id: props.user.department_id ?? '',
})

function submit() {
    form.put(`/users/${props.user.id}`)
}
</script>

<template>
    <DashboardLayout>

        <div class="max-w-2xl">

            <h1 class="text-3xl font-bold mb-6">
                Edit User
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
                </div>

                <!-- Password -->
                <div>
                    <label class="block mb-2 font-medium">
                        Password
                    </label>

                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Leave blank to keep current password"
                        class="w-full border rounded p-2"
                    />
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
                        <option
                            v-for="role in roles"
                            :key="role.id"
                            :value="role.name"
                        >
                            {{ role.name }}
                        </option>
                    </select>
                </div>

                <!-- Department -->
                <div v-if="['Dean','OIC'].includes(form.role)">
                    <label class="block mb-2 font-medium">
                        Department
                    </label>

                    <select
                        v-model="form.department_id"
                        class="w-full border rounded p-2"
                    >
                        <option value="">
                            Select Department
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

                <button
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700"
                >
                    Update User
                </button>

            </form>

        </div>

    </DashboardLayout>
</template>