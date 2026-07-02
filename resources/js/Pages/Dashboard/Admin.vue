<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
</script>

<template>
    <DashboardLayout>

        <h1 class="text-3xl font-bold mb-8 text-[var(--text-primary)]">
            Admin Dashboard
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
                <h2 class="text-[var(--text-secondary)] text-sm">Users</h2>
                <p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
            </div>

            <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
                <h2 class="text-[var(--text-secondary)] text-sm">Faculty</h2>
                <p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
            </div>

            <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
                <h2 class="text-[var(--text-secondary)] text-sm">Departments</h2>
                <p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
            </div>

            <div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
                <h2 class="text-[var(--text-secondary)] text-sm">Programs</h2>
                <p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
            </div>

        </div>

        <div class="bg-[var(--card-bg)] rounded-xl shadow mt-8 p-6 border border-[var(--card-border)] transition-colors duration-300">

            <h2 class="text-xl font-semibold mb-4 text-[var(--text-primary)]">
                System Overview
            </h2>

            <p class="text-[var(--text-secondary)]">
                Welcome Administrator. You have full access to the Classly Management System.
            </p>

        </div>

    </DashboardLayout>
</template>