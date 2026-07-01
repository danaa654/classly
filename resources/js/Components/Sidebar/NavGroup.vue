<script setup>
import { ref, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    label: { type: String, required: true },
    // [{ label: 'Programs', routeName: 'programs.index' }, ...]
    children: { type: Array, required: true },
    // Optional: if the parent label itself should also navigate somewhere
    // (in addition to expanding/collapsing), e.g. Colleges -> departments.index
    parentRoute: { type: String, default: null },
})

const page = usePage()

const isChildActive = (routeName) => {
    void page.url
    return route().current(routeName)
}

const isGroupActive = computed(() => {
    void page.url
    return (
        props.children.some((c) => route().current(c.routeName)) ||
        (props.parentRoute && route().current(props.parentRoute))
    )
})

// Auto-expand when the active page is inside this group
const open = ref(isGroupActive.value)

watch(isGroupActive, (active) => {
    if (active) open.value = true
})

function toggle() {
    open.value = !open.value
}
</script>

<template>
    <div>
        <div
            class="group relative flex items-center mx-2 rounded-md transition-all duration-200 ease-out"
            :class="isGroupActive ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'"
        >
            <!-- Active indicator bar, matches NavItem -->
            <span
                v-if="isGroupActive"
                class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-[3px] rounded-r-full bg-indigo-400"
            />

            <component
                :is="parentRoute ? Link : 'button'"
                :href="parentRoute ? route(parentRoute) : undefined"
                type="button"
                class="flex flex-1 items-center gap-3 px-3 py-2.5 text-sm font-medium text-left transition-transform duration-200 ease-out hover:translate-x-0.5"
                @click="!parentRoute && toggle()"
            >
                <span
                    v-if="$slots.icon"
                    class="shrink-0 flex items-center justify-center h-7 w-7 rounded-md transition-all duration-200 ease-out"
                    :class="isGroupActive
                        ? 'text-indigo-400'
                        : 'text-slate-400 group-hover:text-white group-hover:bg-slate-700/60 group-hover:scale-105'"
                >
                    <slot name="icon" />
                </span>
                <span class="flex-1">{{ label }}</span>
            </component>

            <button
                type="button"
                class="px-3 py-2.5 rounded-md transition-colors duration-200 hover:bg-slate-700/60"
                :aria-expanded="open"
                :aria-label="`Toggle ${label} section`"
                @click.stop="toggle"
            >
                <svg
                    class="w-4 h-4 shrink-0 transition-transform duration-200 ease-in-out"
                    :class="open ? 'rotate-90' : 'rotate-0'"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>

        <!-- CSS-grid based collapse: animates height without measuring pixels -->
        <div
            class="grid transition-[grid-template-rows] duration-300 ease-in-out"
            :style="{ gridTemplateRows: open ? '1fr' : '0fr' }"
        >
            <div class="overflow-hidden">
                <div class="ml-5 mt-1 mb-1 space-y-0.5 border-l border-slate-700 pl-3">
                    <Link
                        v-for="child in children"
                        :key="child.routeName"
                        :href="route(child.routeName)"
                        class="block rounded-md px-3 py-2 text-sm transition-all duration-200 ease-out"
                        :class="isChildActive(child.routeName)
                            ? 'bg-indigo-500/15 text-indigo-400 font-medium'
                            : 'text-slate-400 hover:bg-slate-800/80 hover:text-white hover:translate-x-0.5'"
                    >
                        {{ child.label }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>