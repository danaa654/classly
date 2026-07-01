<script setup>
defineProps({
    label: { type: String, required: true },
    first: { type: Boolean, default: false }, // less top padding for the very first section
})
</script>

<template>
    <div
        class="px-5 text-[11px] font-semibold uppercase tracking-wider text-slate-500"
        :class="first ? 'pt-4 pb-2' : 'pt-5 pb-2'"
    >
        {{ label }}
    </div>
</template>