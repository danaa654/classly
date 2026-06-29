<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    users: Array,
})
</script>

<template>
<DashboardLayout>

<div class="flex justify-between mb-6">
    <h1 class="text-3xl font-bold">
        Users
    </h1>

    <Link
    href="/users/create"
    class="bg-green-500 text-white px-4 py-2 rounded"
>
    Add User
</Link>
</div>

<table class="w-full bg-white rounded shadow">

<thead class="bg-gray-100">
<tr>
    <th class="p-3 text-left">Name</th>
    <th class="p-3 text-left">Email</th>
    <th class="p-3 text-left">Role</th>
    <th class="p-3 text-left">Department</th>
</tr>
</thead>

<tbody>

<tr
v-for="user in users"
:key="user.id"
>

<td class="p-3">{{ user.name }}</td>

<td class="p-3">{{ user.email }}</td>

<td class="p-3">
{{ user.roles[0]?.name }}
</td>

<td class="p-3">
{{ user.department?.short_name }}
</td>

</tr>

</tbody>

</table>

</DashboardLayout>
</template>