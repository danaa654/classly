<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    users: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/users/${id}`)
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">
                Users
            </h1>

            <Link
                href="/users/create"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add User
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>
                        <th class="text-left p-4">Name</th>
                        <th class="text-left p-4">Email</th>
                        <th class="text-left p-4">Role</th>
                        <th class="text-left p-4">Department</th>
                        <th class="text-center p-4">Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-t"
                    >
                        <td class="p-4">
                            {{ user.name }}
                        </td>

                        <td class="p-4">
                            {{ user.email }}
                        </td>

                        <td class="p-4">
                            {{ user.roles[0]?.name }}
                        </td>

                        <td class="p-4">
                            {{ user.department?.short_name ?? 'All Departments' }}
                        </td>

                        <td class="p-4">

                            <div class="flex justify-center gap-2">

                                <Link
                                    :href="`/users/${user.id}/edit`"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(user.id)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>