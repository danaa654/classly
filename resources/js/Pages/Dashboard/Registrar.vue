<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
</script>

<template>

<DashboardLayout>

<h1 class="text-3xl font-bold mb-8 text-[var(--text-primary)]">
Registrar Dashboard
</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

<div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
<h2 class="text-[var(--text-secondary)]">Curriculums</h2>
<p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
</div>

<div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
<h2 class="text-[var(--text-secondary)]">Programs</h2>
<p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
</div>

<div class="bg-[var(--card-bg)] rounded-xl shadow p-6 border border-[var(--card-border)] transition-colors duration-300">
<h2 class="text-[var(--text-secondary)]">Faculty</h2>
<p class="text-3xl font-bold mt-2 text-[var(--text-primary)]">--</p>
</div>

</div>

<div class="bg-[var(--card-bg)] rounded-xl shadow mt-8 p-6 border border-[var(--card-border)] transition-colors duration-300">

<h2 class="text-xl font-semibold mb-3 text-[var(--text-primary)]">
Registrar Panel
</h2>

<p class="text-[var(--text-secondary)]">
Manage curriculum structures, faculty information, subjects, and scheduling.
</p>

</div>

</DashboardLayout>

</template>