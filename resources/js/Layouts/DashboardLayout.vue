<script setup>
import Sidebar from '@/Components/Sidebar.vue'
import Topbar from '@/Components/Topbar.vue'
import AppFooter from '@/Components/AppFooter.vue'
</script>

<template>

<div
    class="flex h-screen transition-colors duration-300"
    style="background: var(--page-bg)"
>

    <Sidebar />

    <div class="flex-1 flex flex-col">

        <Topbar />

        <main class="flex-1 overflow-y-auto p-6">
            <slot />
        </main>

        <AppFooter />

    </div>

</div>

</template>