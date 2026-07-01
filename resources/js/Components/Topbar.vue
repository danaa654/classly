<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useAppShell } from '@/Composables/useAppShell'

const emit = defineEmits(['toggle'])

const { openMobile } = useAppShell()

const page = usePage()
const user = computed(() => page.props.auth?.user)

const role = computed(() => {
    return user.value?.roles?.length ? user.value.roles[0] : 'User'
})

function initials(name) {
    if (!name) return ''
    return name.trim().split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase()
}

function onToggle() {
    // Keep firing the old event for any parent still listening to it,
    // and also open the shared sidebar state directly so it works with
    // the new hover/pin sidebar out of the box.
    emit('toggle')
    openMobile()
}
</script>

<template>
    <header
        class="relative h-16 px-5 flex items-center justify-between shrink-0 border-b border-black/10"
        style="background: var(--sidebar-bg)"
    >
        <!-- Subtle bottom glow so the bar reads as elevated, not flat -->
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>

        <!-- Hamburger -->
        <button
            class="relative flex items-center justify-center w-10 h-10 rounded-xl text-white/80 hover:text-white hover:bg-white/10 active:scale-95 transition-all duration-150"
            aria-label="Toggle sidebar"
            @click="onToggle"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Right controls -->
        <div class="flex items-center gap-4">
            <!-- Welcome + role -->
            <div class="hidden sm:flex items-center gap-3">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] font-black border border-white/15 shadow-sm"
                    style="background: linear-gradient(135deg, #2563eb, #4f46e5)"
                >
                    {{ initials(user?.name) }}
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="text-[13px] font-semibold text-white">
                        Welcome, {{ user?.name ?? role }}
                    </span>
                    <span class="text-[9px] font-bold text-blue-200/70 uppercase tracking-widest">
                        {{ role }}
                    </span>
                </div>
            </div>

            <!-- Logout -->
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="group relative flex items-center gap-2 pl-4 pr-4 py-2.5 rounded-xl text-[13px] font-bold text-white
                       bg-gradient-to-b from-rose-500 to-rose-600 shadow-md shadow-rose-950/30
                       hover:from-rose-400 hover:to-rose-500 hover:shadow-lg hover:shadow-rose-900/40
                       active:scale-95 transition-all duration-150"
            >
                <svg class="w-4 h-4 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="hidden sm:inline">Logout</span>
            </Link>
        </div>
    </header>
</template>