<script setup>
defineProps({
    toast: {
        type: Object,
        default: null,
    },
})

const TYPE_CLASSES = {
    success: 'bg-green-500',
    warning: 'bg-amber-500',
    error: 'bg-red-500',
}
</script>

<template>
    <Transition name="toast-fade">
        <div
            v-if="toast"
            class="fixed top-6 right-6 z-50 max-w-sm rounded-lg shadow-lg px-4 py-3 text-sm font-medium text-white"
            :class="TYPE_CLASSES[toast.type] ?? TYPE_CLASSES.success"
        >
            {{ toast.message }}
        </div>
    </Transition>
</template>

<style scoped>
.toast-fade-enter-active,
.toast-fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.toast-fade-enter-from,
.toast-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>