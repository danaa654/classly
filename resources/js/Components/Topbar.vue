<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useAppShell } from '@/Composables/useAppShell'
import ThemeToggle from '@/Components/ThemeToggle.vue'

// mobileOpen is the same reactive flag Sidebar.vue reads to show/hide
// itself on small screens — flipping it here is what makes the
// hamburger button actually open the sidebar on mobile.
const { mobileOpen } = useAppShell()

const page = usePage()
const user = computed(() => page.props.auth?.user)
const activeAcademicTerm = computed(() => page.props.activeAcademicTerm)

const role = computed(() => {
    return user.value?.roles?.length ? user.value.roles[0] : 'User'
})

function initials(name) {
    if (!name) return ''
    return name.trim().split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<template>
    <header
        class="relative h-16 px-5 flex items-center justify-between shrink-0 border-b border-black/10"
        style="background: var(--sidebar-bg)"
    >
        <!-- Subtle bottom glow so the bar reads as elevated, not flat -->
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>

        <!-- School / system title -->
        <div class="flex items-center gap-3 min-w-0 brand-font">
            <button
                type="button"
                class="md:hidden shrink-0 text-white/80 hover:text-white"
                @click="mobileOpen = true"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="flex flex-col leading-none min-w-0">
                <span class="text-white font-extrabold text-[14px] sm:text-[15px] tracking-wide uppercase truncate">
                    Professional Academy of the Philippines
                </span>
                <span class="text-blue-400 font-bold text-[9px] sm:text-[10px] tracking-[0.15em] uppercase mt-1 truncate">
                    Classly &middot; Class Scheduling Management System
                </span>
            </div>
        </div>

        <!-- Right controls -->
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Active Academic Term -->
            <div
                class="flex items-center gap-1.5 sm:gap-2 rounded-full border px-2.5 sm:px-3 py-1 sm:py-1.5 brand-font"
                :style="activeAcademicTerm
                    ? 'background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25)'
                    : 'background: rgba(255, 255, 255, 0.04); border-color: rgba(255, 255, 255, 0.1)'"
            >
                <span
                    class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="activeAcademicTerm ? 'bg-emerald-400' : 'bg-slate-500'"
                    :style="activeAcademicTerm ? 'box-shadow: 0 0 6px rgba(52, 211, 153, 0.8)' : ''"
                ></span>

                <div class="flex flex-col leading-tight">
                    <span
                        class="hidden sm:block text-[9px] font-bold uppercase tracking-widest"
                        :class="activeAcademicTerm ? 'text-emerald-300/80' : 'text-slate-400'"
                    >
                        {{ activeAcademicTerm ? 'Active Academic Term' : 'No Active Academic Term' }}
                    </span>
                    <span
                        v-if="activeAcademicTerm"
                        class="text-[11px] sm:text-[12px] font-semibold text-white truncate max-w-[110px] sm:max-w-none"
                    >
                        {{ activeAcademicTerm.semester_label }} &bull; SY {{ activeAcademicTerm.academic_year }}
                    </span>
                    <span v-else class="sm:hidden text-[11px] font-semibold text-slate-300">
                        No Active Term
                    </span>
                </div>
            </div>

            <!-- Theme toggle -->
            <ThemeToggle class="hidden sm:block" />

            <!-- Welcome + role -->
            <div class="hidden sm:flex items-center gap-3 brand-font">
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
        </div>
    </header>
</template>