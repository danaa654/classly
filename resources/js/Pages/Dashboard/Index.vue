<script setup>

import DashboardLayout from '@/Layouts/DashboardLayout.vue'

</script>

<template>

<DashboardLayout>

    <h1 class="text-3xl font-bold mb-6">
        Dashboard
    </h1>

    <div class="grid grid-cols-4 gap-5">

        <div class="bg-white shadow rounded-lg p-6">
            Users
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            Departments
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            Faculty
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            Schedules
        </div>

    </div>

</DashboardLayout>

</template>