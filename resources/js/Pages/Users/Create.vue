<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    email: '',
    password: '',
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
                class="bg-white rounded-lg shadow p-6 space-y-4"
            >
                <div>
                    <label class="block mb-2">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border rounded p-2"
                    />
                </div>

                <div>
                    <label class="block mb-2">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full border rounded p-2"
                    />
                </div>

                <div>
                    <label class="block mb-2">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="w-full border rounded p-2"
                    />
                </div>

                <button
                    type="submit"
                    class="bg-green-500 text-white px-5 py-2 rounded hover:bg-green-600"
                >
                    Save User
                </button>
            </form>
        </div>
    </DashboardLayout>
</template>