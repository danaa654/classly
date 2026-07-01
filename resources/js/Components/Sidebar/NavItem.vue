<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    label: { type: String, required: true },
    routeName: { type: String, default: null }, // Ziggy route name, e.g. 'dashboard'
    href: { type: String, default: '#' },        // used when there's no routeName yet (placeholders)
    disabled: { type: Boolean, default: false },  // e.g. "Settings" before it's implemented
})

const page = usePage()

const isActive = computed(() => {
    // reference page.url so this recomputes on every Inertia navigation
    void page.url
    return props.routeName ? route().current(props.routeName) : false
})

const resolvedHref = computed(() => (props.routeName ? route(props.routeName) : props.href))
</script>

<template>
    <Link
        :href="disabled ? '#' : resolvedHref"
        @click="disabled && $event.preventDefault()"
        class="group relative flex items-center gap-3 mx-2 rounded-md px-3 py-2.5 text-sm font-medium transition-all duration-200 ease-out"
        :class="[
            disabled
                ? 'text-slate-500 cursor-not-allowed'
                : isActive
                    ? 'bg-indigo-500/15 text-indigo-400'
                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white hover:translate-x-0.5',
        ]"
    >
        <!-- Active indicator bar, Mailtrap-style -->
        <span
            v-if="isActive && !disabled"
            class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-[3px] rounded-r-full bg-indigo-400"
        />

        <span
            v-if="$slots.icon"
            class="shrink-0 flex items-center justify-center h-7 w-7 rounded-md transition-all duration-200 ease-out"
            :class="isActive && !disabled
                ? 'text-indigo-400'
                : 'text-slate-400 group-hover:text-white group-hover:bg-slate-700/60 group-hover:scale-105'"
        >
            <slot name="icon" />
        </span>

        <span class="flex-1">{{ label }}</span>

        <span
            v-if="disabled"
            class="text-[10px] uppercase tracking-wide text-slate-600 border border-slate-700 rounded px-1.5 py-0.5"
        >
            Soon
        </span>
    </Link>
</template>