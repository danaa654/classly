<script setup>
const emit = defineEmits(['toggle'])
</script>

<template>

<div class="bg-white shadow px-5 py-4 flex justify-between items-center">

    <button
        class="text-xl"
        @click="emit('toggle')"
    >
        ☰
    </button>

    <div>

        Welcome Admin

    </div>

</div>

</template>