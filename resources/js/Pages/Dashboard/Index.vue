<script setup>

import DashboardLayout from '@/Layouts/DashboardLayout.vue'

</script>

<template>

<DashboardLayout>

    <h1 class="text-3xl font-bold mb-6 text-[var(--text-primary)]">
        Dashboard
    </h1>

    <div class="grid grid-cols-4 gap-5">

        <div class="bg-[var(--card-bg)] shadow rounded-lg p-6 border border-[var(--card-border)] transition-colors duration-300 text-[var(--text-primary)]">
            Users
        </div>

        <div class="bg-[var(--card-bg)] shadow rounded-lg p-6 border border-[var(--card-border)] transition-colors duration-300 text-[var(--text-primary)]">
            Departments
        </div>

        <div class="bg-[var(--card-bg)] shadow rounded-lg p-6 border border-[var(--card-border)] transition-colors duration-300 text-[var(--text-primary)]">
            Faculty
        </div>

        <div class="bg-[var(--card-bg)] shadow rounded-lg p-6 border border-[var(--card-border)] transition-colors duration-300 text-[var(--text-primary)]">
            Schedules
        </div>

    </div>

</DashboardLayout>

</template>