<script setup>
import { ref } from 'vue'
import Sidebar from '@/Components/Sidebar.vue'
import Topbar from '@/Components/Topbar.vue'

const sidebarOpen = ref(true)
</script>

<template>
    <div class="flex h-screen bg-gray-100">

        <Sidebar :open="sidebarOpen" />

        <div class="flex-1 flex flex-col">

            <Topbar @toggle="sidebarOpen = !sidebarOpen" />

            <main class="p-6 overflow-y-auto flex-1">
                <slot />
            </main>

        </div>

    </div>
</template>