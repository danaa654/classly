<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
</script>

<template>

<DashboardLayout>

<h1 class="text-3xl font-bold mb-8">
Dean Dashboard
</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

<div class="bg-white rounded-xl shadow p-6">
<h2 class="text-gray-500">Faculty</h2>
<p class="text-3xl font-bold mt-2">--</p>
</div>

<div class="bg-white rounded-xl shadow p-6">
<h2 class="text-gray-500">Programs</h2>
<p class="text-3xl font-bold mt-2">--</p>
</div>

<div class="bg-white rounded-xl shadow p-6">
<h2 class="text-gray-500">Teaching Load</h2>
<p class="text-3xl font-bold mt-2">--</p>
</div>

</div>

<div class="bg-white rounded-xl shadow mt-8 p-6">

<h2 class="text-xl font-semibold mb-3">
College Dashboard
</h2>

<p class="text-gray-600">
Manage faculty, curriculum, teaching assignments, and department activities.
</p>

</div>

</DashboardLayout>

</template>