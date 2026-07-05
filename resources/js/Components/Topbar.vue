<script setup>
import { computed, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useAppShell } from '@/Composables/useAppShell'
import ThemeToggle from '@/Components/ThemeToggle.vue'

// mobileOpen is the same reactive flag Sidebar.vue reads to show/hide
// itself on small screens — flipping it here is what makes the
// hamburger button actually open the sidebar on mobile.
const { mobileOpen } = useAppShell()

const page = usePage()
const activeAcademicTerm = computed(() => page.props.activeAcademicTerm)

// The scheduling workspace's term — independent of activeAcademicTerm
// above. Shared to every authenticated user by HandleInertiaRequests,
// but the switcher UI (below) only ever renders for Admin/Registrar
// because academicTermsForSwitcher is only populated for them.
const workingAcademicTerm = computed(() => page.props.workingAcademicTerm)
const switcherOptions = computed(() => page.props.academicTermsForSwitcher ?? [])
const canSwitchWorkingTerm = computed(() => switcherOptions.value.length > 0)

const menuOpen = ref(false)

function toggleMenu() {
    if (!canSwitchWorkingTerm.value) return
    menuOpen.value = !menuOpen.value
}

function selectTerm(term) {
    menuOpen.value = false

    if (term.id === workingAcademicTerm.value?.id) return

    router.put(route('working-term.update'), { academic_term_id: term.id }, {
        preserveScroll: true,
        preserveState: true,
    })
}

function closeMenu() {
    menuOpen.value = false
}

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

            <!-- Active Academic Term (unchanged — Enrollment, Grades,
                 Attendance, Reports, dashboard stats, and the Student
                 Portal all key off this one, never the Working Term). -->
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
                        {{ activeAcademicTerm ? 'Active Term' : 'No Active Academic Term' }}
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

            <!-- Working Academic Term — the scheduling workspace switcher.
                 Visible to every authenticated user (Faculty need to know
                 which term's schedules they're looking at), but the dropdown
                 itself only opens for Admin/Registrar, since
                 switcherOptions is empty for everyone else. -->
            <div v-if="workingAcademicTerm || canSwitchWorkingTerm" class="relative brand-font">
                <button
                    type="button"
                    class="flex items-center gap-1.5 sm:gap-2 rounded-full border px-2.5 sm:px-3 py-1 sm:py-1.5 transition-colors"
                    :class="canSwitchWorkingTerm ? 'cursor-pointer hover:bg-indigo-500/10' : 'cursor-default'"
                    style="background: rgba(99, 102, 241, 0.10); border-color: rgba(99, 102, 241, 0.3)"
                    @click="toggleMenu"
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full shrink-0 bg-indigo-400"
                        style="box-shadow: 0 0 6px rgba(129, 140, 248, 0.8)"
                    ></span>

                    <div class="flex flex-col leading-tight text-left">
                        <span class="hidden sm:block text-[9px] font-bold uppercase tracking-widest text-indigo-300/80">
                            Working Term
                        </span>
                        <span
                            v-if="workingAcademicTerm"
                            class="text-[11px] sm:text-[12px] font-semibold text-white truncate max-w-[110px] sm:max-w-none"
                        >
                            {{ workingAcademicTerm.semester_label }} &bull; SY {{ workingAcademicTerm.academic_year }}
                        </span>
                        <span v-else class="text-[11px] font-semibold text-slate-300">
                            No Working Term
                        </span>
                    </div>

                    <span
                        v-if="workingAcademicTerm"
                        class="hidden sm:inline text-[9px] font-bold uppercase tracking-wide text-indigo-200/80 border border-indigo-400/30 rounded px-1.5 py-0.5 whitespace-nowrap"
                    >
                        {{ workingAcademicTerm.scheduling_status }}
                    </span>

                    <svg
                        v-if="canSwitchWorkingTerm"
                        class="w-3.5 h-3.5 text-indigo-200/70 transition-transform"
                        :class="menuOpen ? 'rotate-180' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Backdrop to close the menu on outside click -->
                <div v-if="menuOpen" class="fixed inset-0 z-40" @click="closeMenu"></div>

                <div
                    v-if="menuOpen"
                    class="absolute right-0 mt-2 w-64 rounded-lg border border-white/10 shadow-xl z-50 overflow-hidden"
                    style="background: var(--sidebar-bg)"
                >
                    <div class="px-3 py-2 text-[9px] font-bold uppercase tracking-widest text-white/40 border-b border-white/10">
                        Switch Working Term
                    </div>
                    <button
                        v-for="term in switcherOptions"
                        :key="term.id"
                        type="button"
                        class="w-full flex items-center justify-between gap-2 px-3 py-2 text-left text-[12px] hover:bg-white/10 transition-colors"
                        :class="term.id === workingAcademicTerm?.id ? 'text-indigo-300 font-semibold bg-indigo-500/10' : 'text-white/80'"
                        @click="selectTerm(term)"
                    >
                        <span class="truncate">{{ term.semester_label }} &bull; SY {{ term.academic_year }}</span>
                        <span class="text-[9px] uppercase text-white/40 shrink-0">{{ term.scheduling_status }}</span>
                    </button>
                </div>
            </div>

            <!-- Theme toggle -->
            <ThemeToggle />

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