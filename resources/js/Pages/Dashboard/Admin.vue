<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
</script>

<template>
    <DashboardLayout>

        <h1 class="text-3xl font-bold mb-8">
            Admin Dashboard
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-gray-500 text-sm">Users</h2>
                <p class="text-3xl font-bold mt-2">--</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-gray-500 text-sm">Faculty</h2>
                <p class="text-3xl font-bold mt-2">--</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-gray-500 text-sm">Departments</h2>
                <p class="text-3xl font-bold mt-2">--</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-gray-500 text-sm">Programs</h2>
                <p class="text-3xl font-bold mt-2">--</p>
            </div>

        </div>

        <div class="bg-white rounded-xl shadow mt-8 p-6">

            <h2 class="text-xl font-semibold mb-4">
                System Overview
            </h2>

            <p class="text-gray-600">
                Welcome Administrator. You have full access to the Classly Management System.
            </p>

        </div>

    </DashboardLayout>
</template>