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
const activeAcademicTerm = computed(() => page.props.activeAcademicTerm)

// TODO: wire this up to a real notifications count once that feature
// exists (e.g. page.props.unreadNotificationsCount). Left as a static
// false for now so the bell renders without a badge.
const unreadNotifications = computed(() => page.props.unreadNotificationsCount > 0)
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

            <!-- Notifications -->
            <button
                type="button"
                class="relative flex h-9 w-9 items-center justify-center rounded-full text-white/80 transition-colors duration-150 hover:text-white hover:bg-white/10"
                aria-label="Notifications"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                <span
                    v-if="unreadNotifications"
                    class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-500 ring-2"
                    style="--tw-ring-color: var(--sidebar-bg)"
                ></span>
            </button>
        </div>
    </header>
</template>