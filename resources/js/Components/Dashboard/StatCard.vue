<script setup>
import { computed } from 'vue'
import { collegeClasses } from '@/Utils/collegeColors'

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: null },
    suffix: { type: String, default: '' },
    accent: { type: String, default: '' }, // e.g. 'text-emerald-500' for a highlighted number
    college: { type: String, default: null }, // e.g. 'CCS', 'CRIM', 'CTE', 'SHTM' — tints the card by department
})

// When no college is passed, the card falls back to the original
// neutral indigo hover treatment — Admin/Registrar's cross-department
// cards look exactly as before, while Dean/Assistant Dean cards can
// pick up their own department's color automatically.
const palette = computed(() => (props.college ? collegeClasses(props.college) : null))
</script>

<template>
    <div
        class="group relative bg-[var(--card-bg)] rounded-xl shadow p-4 border border-[var(--card-border)] overflow-hidden
               transition-all duration-300 ease-out
               hover:-translate-y-1 hover:shadow-lg"
        :class="palette ? palette.hoverBorder : 'hover:border-indigo-300 dark:hover:border-indigo-500'"
    >
        <!-- College accent bar, brightens on hover -->
        <div
            v-if="palette"
            class="absolute top-0 left-0 right-0 h-1 opacity-70 group-hover:opacity-100 transition-opacity duration-300"
            :class="palette.dot"
        ></div>

        <div class="flex items-center justify-between">
            <h2 class="text-[var(--text-secondary)] text-xs">{{ label }}</h2>
            <span
                v-if="palette"
                class="w-1.5 h-1.5 rounded-full transition-transform duration-300 group-hover:scale-125"
                :class="palette.dot"
            ></span>
        </div>

        <p
            class="text-xl font-bold mt-1 leading-tight break-words transition-transform duration-300 group-hover:scale-[1.03] origin-left"
            :class="accent || 'text-[var(--text-primary)]'"
        >
            {{ value ?? '—' }}<span v-if="value !== null && suffix" class="text-sm">{{ suffix }}</span>
        </p>
        <p v-if="$slots.default" class="text-xs text-[var(--text-secondary)] mt-1">
            <slot />
        </p>
    </div>
</template>