<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';
</script>

<template>
    <div id="layout-shell">
        <Sidebar />

        <div id="layout-content">
            <Topbar />

            <!-- Light gray scrollable content area — everything the page
                 passes into the default slot renders here, below the dark
                 sidebar/topbar shell. -->
            <main class="flex-1 overflow-y-auto custom-scrollbar bg-gray-50">
                <slot />
            </main>
        </div>
    </div>
</template>