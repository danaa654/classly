<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useAppShell } from '@/Composables/useAppShell';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import {
    UserGroupIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    ArrowPathIcon,
    ShieldCheckIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const { darkMode } = useAppShell();

const capabilities = [
    {
        name: 'Faculty Assignment',
        description: 'Match faculty to subjects by expertise, load, and availability.',
        icon: UserGroupIcon,
    },
    {
        name: 'Room Allocation',
        description: 'Assign lecture and lab rooms with capacity and type checks built in.',
        icon: BuildingOffice2Icon,
    },
    {
        name: 'Block Scheduling',
        description: 'Build section schedules and catch overlaps before they happen.',
        icon: CalendarDaysIcon,
    },
    {
        name: 'Semester Lifecycle',
        description: 'Roll over, archive, and finalize terms without losing history.',
        icon: ArrowPathIcon,
    },
    {
        name: 'Conflict-Free Checks',
        description: 'Cross-check faculty, room, and section overlaps automatically.',
        icon: ShieldCheckIcon,
    },
    {
        name: 'Role Dashboards',
        description: 'Give Admins, Registrars, Deans, and OICs the view built for their job.',
        icon: Squares2X2Icon,
    },
];

// Fixed positions/timings so the drift feels organic without being random on every render
const fireflies = [
    { top: '12%', left: '8%', size: '4px', duration: '9s', delay: '0s', variant: 'a' },
    { top: '22%', left: '18%', size: '3px', duration: '7.5s', delay: '1.2s', variant: 'b' },
    { top: '68%', left: '6%', size: '5px', duration: '10.5s', delay: '2.4s', variant: 'c' },
    { top: '78%', left: '15%', size: '3px', duration: '8s', delay: '0.6s', variant: 'a' },
    { top: '15%', left: '85%', size: '4px', duration: '9.5s', delay: '1.8s', variant: 'b' },
    { top: '30%', left: '92%', size: '3px', duration: '7s', delay: '3s', variant: 'c' },
    { top: '72%', left: '88%', size: '5px', duration: '11s', delay: '0.9s', variant: 'a' },
    { top: '85%', left: '78%', size: '3px', duration: '8.5s', delay: '2.1s', variant: 'b' },
    { top: '8%', left: '45%', size: '3px', duration: '9s', delay: '2.7s', variant: 'c' },
    { top: '92%', left: '48%', size: '4px', duration: '10s', delay: '1.5s', variant: 'a' },
    { top: '45%', left: '4%', size: '3px', duration: '7.8s', delay: '0.3s', variant: 'b' },
    { top: '50%', left: '95%', size: '4px', duration: '8.2s', delay: '3.3s', variant: 'c' },
    { top: '38%', left: '60%', size: '3px', duration: '9.8s', delay: '1s', variant: 'a' },
    { top: '60%', left: '35%', size: '3px', duration: '8.8s', delay: '2.6s', variant: 'b' },
];

// Light-mode counterpart to the fireflies — soft rising bubbles.
// Starting points sit low/mid screen since the animation carries them upward.
const bubbles = [
    { left: '6%', size: '14px', duration: '12s', delay: '0s', variant: 'a' },
    { left: '14%', size: '9px', duration: '9.5s', delay: '1.5s', variant: 'b' },
    { left: '22%', size: '18px', duration: '14s', delay: '3s', variant: 'c' },
    { left: '30%', size: '10px', duration: '10.5s', delay: '0.8s', variant: 'a' },
    { left: '40%', size: '13px', duration: '11.5s', delay: '2.2s', variant: 'b' },
    { left: '48%', size: '8px', duration: '9s', delay: '4s', variant: 'c' },
    { left: '58%', size: '16px', duration: '13s', delay: '1.1s', variant: 'a' },
    { left: '66%', size: '11px', duration: '10s', delay: '2.8s', variant: 'b' },
    { left: '74%', size: '9px', duration: '9.8s', delay: '0.4s', variant: 'c' },
    { left: '82%', size: '15px', duration: '12.5s', delay: '3.4s', variant: 'a' },
    { left: '90%', size: '10px', duration: '10.8s', delay: '1.7s', variant: 'b' },
    { left: '95%', size: '12px', duration: '11s', delay: '2.5s', variant: 'c' },
    { left: '52%', size: '20px', duration: '15s', delay: '0.6s', variant: 'a' },
    { left: '18%', size: '11px', duration: '10.2s', delay: '3.8s', variant: 'b' },
];
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="relative flex h-screen w-screen flex-col overflow-hidden bg-[#EAF6FF] text-[#16213E] antialiased transition-colors duration-500 dark:bg-[#0B1220] dark:text-[#F3EFE6] [font-family:'IBM_Plex_Sans',sans-serif]"
    >
        <!-- Theme toggle -->
        <div class="absolute right-5 top-5 z-20">
            <ThemeToggle />
        </div>

        <!-- ===================== TIMETABLE GRID TEXTURE ===================== -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <!-- Light mode grid -->
            <div
                class="absolute inset-0 opacity-[0.07] dark:hidden"
                style="
                    background-image:
                        linear-gradient(#1e3a5f 1px, transparent 1px),
                        linear-gradient(90deg, #1e3a5f 1px, transparent 1px);
                    background-size: 56px 56px;
                "
            ></div>
            <!-- Dark mode grid -->
            <div
                class="absolute inset-0 hidden opacity-[0.05] dark:block"
                style="
                    background-image:
                        linear-gradient(#f3efe6 1px, transparent 1px),
                        linear-gradient(90deg, #f3efe6 1px, transparent 1px);
                    background-size: 56px 56px;
                "
            ></div>
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-[#D4A62A]/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/4 h-[28rem] w-[28rem] rounded-full bg-[#7A2E3B]/10 blur-3xl dark:bg-[#7A2E3B]/20"></div>
            <div class="absolute -bottom-32 left-1/4 h-96 w-96 rounded-full bg-[#D4A62A]/5 blur-3xl"></div>

            <!-- Fireflies — night-sky detail, dark mode only -->
            <span
                v-if="darkMode"
                v-for="(f, i) in fireflies"
                :key="`f-${i}`"
                class="firefly"
                :class="`firefly-${f.variant}`"
                :style="{
                    top: f.top,
                    left: f.left,
                    width: f.size,
                    height: f.size,
                    animationDuration: f.duration,
                    animationDelay: f.delay,
                }"
            ></span>

            <!-- Bubbles — day-sky detail, light mode only -->
            <span
                v-if="!darkMode"
                v-for="(b, i) in bubbles"
                :key="`b-${i}`"
                class="bubble"
                :class="`bubble-${b.variant}`"
                :style="{
                    left: b.left,
                    width: b.size,
                    height: b.size,
                    animationDuration: b.duration,
                    animationDelay: b.delay,
                }"
            ></span>
        </div>

        <!-- ===================== MAIN ===================== -->
        <main class="relative z-10 flex flex-1 flex-col items-center justify-center gap-6 overflow-hidden px-6">
            <!-- Emblem -->
            <div
                class="flex h-16 w-16 animate-[float_4s_ease-in-out_infinite] items-center justify-center rounded-full border border-[#D4A62A]/40 bg-white p-2.5 shadow-xl shadow-black/10 transition-transform duration-300 hover:scale-110 dark:bg-[#151B2E] dark:shadow-black/40 sm:h-20 sm:w-20"
            >
                <img src="/logo.png" alt="PAP logo" class="h-full w-full rounded-full object-contain" />
            </div>

            <!-- Hero -->
            <div class="animate-[fadein_0.6s_ease-out] text-center">
                <h1
                    class="text-4xl font-semibold tracking-tight text-[#16213E] [font-family:'Fraunces',serif] dark:text-[#F3EFE6] sm:text-5xl lg:text-6xl"
                >
                    CLASSLY
                </h1>
                <p class="mt-2 text-base font-medium text-[#A8790E] dark:text-[#E8C766] sm:text-lg">
                    Your Friendly Class Scheduler
                </p>
            </div>

            <!-- Login -->
            <nav v-if="canLogin" class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="firefly-btn rounded-full bg-[#D4A62A] px-10 py-2.5 text-sm font-semibold text-[#0B1220] shadow-lg shadow-[#D4A62A]/20 transition-all duration-300 hover:scale-105 hover:bg-[#E8C766] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#D4A62A]"
                >
                    Go to Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="firefly-btn rounded-full bg-[#D4A62A] px-10 py-2.5 text-sm font-semibold text-[#0B1220] shadow-lg shadow-[#D4A62A]/20 transition-all duration-300 hover:scale-105 hover:bg-[#E8C766] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#D4A62A]"
                    >
                        Login
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-full border border-[#16213E]/15 bg-[#16213E]/5 px-10 py-2.5 text-sm font-semibold text-[#16213E] transition-all duration-300 hover:scale-105 hover:border-[#D4A62A]/40 hover:bg-[#16213E]/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#16213E]/40 dark:border-[#F3EFE6]/20 dark:bg-[#F3EFE6]/5 dark:text-[#F3EFE6] dark:hover:bg-[#F3EFE6]/10 dark:focus-visible:outline-[#F3EFE6]/40"
                    >
                        Register
                    </Link>
                </template>
            </nav>

            <!-- What Classly handles -->
            <div class="w-full max-w-5xl">
                <p
                    class="mb-3 text-center text-[10px] uppercase tracking-[0.3em] text-slate-500 [font-family:'IBM_Plex_Mono',monospace] dark:text-[#8B93A7] sm:text-xs"
                >
                    What Classly Handles
                </p>
                <div class="grid w-full grid-cols-2 gap-3 sm:grid-cols-3">
                    <div
                        v-for="capability in capabilities"
                        :key="capability.name"
                        class="group flex flex-col gap-2 rounded-2xl border border-[#16213E]/10 bg-white/60 px-4 py-3.5 text-left shadow-lg backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-[#D4A62A]/40 hover:bg-white hover:shadow-[0_10px_30px_-10px_rgba(212,166,42,0.25)] dark:border-[#F3EFE6]/10 dark:bg-[#151B2E]/60 dark:hover:bg-[#151B2E]"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#D4A62A]/20 bg-[#D4A62A]/10 text-[#D4A62A] transition-all duration-300 group-hover:scale-110 group-hover:bg-[#D4A62A]/20"
                        >
                            <component :is="capability.icon" class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold text-[#16213E] transition-colors duration-300 group-hover:text-[#A8790E] dark:text-[#F3EFE6] dark:group-hover:text-[#E8C766] sm:text-sm"
                            >
                                {{ capability.name }}
                            </p>
                            <p class="mt-1 hidden text-xs leading-relaxed text-slate-500 dark:text-[#8B93A7] sm:block">
                                {{ capability.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ===================== FOOTER ===================== -->
        <footer class="relative z-10 border-t border-[#16213E]/10 px-6 py-2.5 text-center dark:border-[#F3EFE6]/10">
            <p class="text-[10px] text-slate-500 dark:text-[#8B93A7] sm:text-xs">
                &copy; 2025 Professional Academy of the Philippines &ndash; Naga Cebu &mdash;
                CLASSLY. All Rights Reserved.
            </p>
            <p class="mt-0.5 text-[9px] text-slate-500/70 dark:text-[#8B93A7]/70 sm:text-[10px]">
                Developed by &ndash; DJS
            </p>
        </footer>
    </div>
</template>

<style scoped>
@keyframes fadein {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

/* ===================== FIREFLIES ===================== */
.firefly {
    position: absolute;
    border-radius: 9999px;
    background: #e8c766;
    box-shadow:
        0 0 6px 2px rgba(212, 166, 42, 0.7),
        0 0 12px 4px rgba(212, 166, 42, 0.3);
    opacity: 0;
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
    will-change: transform, opacity;
}

.firefly-a {
    animation-name: firefly-a;
}
.firefly-b {
    animation-name: firefly-b;
}
.firefly-c {
    animation-name: firefly-c;
}

@keyframes firefly-a {
    0% {
        opacity: 0;
        transform: translate(0, 0);
    }
    15% {
        opacity: 0.9;
    }
    50% {
        opacity: 1;
        transform: translate(18px, -26px);
    }
    85% {
        opacity: 0.5;
    }
    100% {
        opacity: 0;
        transform: translate(30px, -44px);
    }
}

@keyframes firefly-b {
    0% {
        opacity: 0;
        transform: translate(0, 0);
    }
    15% {
        opacity: 0.8;
    }
    50% {
        opacity: 1;
        transform: translate(-22px, -18px);
    }
    85% {
        opacity: 0.4;
    }
    100% {
        opacity: 0;
        transform: translate(-36px, -32px);
    }
}

@keyframes firefly-c {
    0% {
        opacity: 0;
        transform: translate(0, 0);
    }
    15% {
        opacity: 0.9;
    }
    50% {
        opacity: 1;
        transform: translate(12px, 24px);
    }
    85% {
        opacity: 0.5;
    }
    100% {
        opacity: 0;
        transform: translate(20px, 40px);
    }
}

/* Subtle glow pulse behind the login button on hover */
.firefly-btn {
    position: relative;
}
.firefly-btn:hover {
    box-shadow:
        0 0 0 4px rgba(212, 166, 42, 0.15),
        0 10px 30px -8px rgba(212, 166, 42, 0.4);
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
    .firefly,
    .bubble {
        display: none;
    }
}

/* ===================== BUBBLES (light mode) ===================== */
.bubble {
    position: absolute;
    bottom: -40px;
    border-radius: 9999px;
    background: radial-gradient(
        circle at 30% 25%,
        #ffffff 0%,
        rgba(255, 255, 255, 0.9) 12%,
        rgba(219, 234, 254, 0.6) 40%,
        rgba(96, 165, 250, 0.35) 72%,
        rgba(37, 99, 235, 0.12) 100%
    );
    border: 1.5px solid rgba(255, 255, 255, 0.95);
    box-shadow:
        0 0 10px 2px rgba(37, 99, 235, 0.4),
        inset 0 0 6px rgba(255, 255, 255, 0.7);
    opacity: 0;
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
    will-change: transform, opacity;
}

.bubble-a {
    animation-name: bubble-a;
}
.bubble-b {
    animation-name: bubble-b;
}
.bubble-c {
    animation-name: bubble-c;
}

@keyframes bubble-a {
    0% {
        opacity: 0;
        transform: translate(0, 0) scale(0.8);
    }
    12% {
        opacity: 1;
    }
    50% {
        transform: translate(14px, -55vh) scale(1);
    }
    88% {
        opacity: 0.6;
    }
    100% {
        opacity: 0;
        transform: translate(24px, -100vh) scale(1.05);
    }
}

@keyframes bubble-b {
    0% {
        opacity: 0;
        transform: translate(0, 0) scale(0.85);
    }
    12% {
        opacity: 0.95;
    }
    50% {
        transform: translate(-16px, -55vh) scale(1);
    }
    88% {
        opacity: 0.55;
    }
    100% {
        opacity: 0;
        transform: translate(-26px, -100vh) scale(1.05);
    }
}

@keyframes bubble-c {
    0% {
        opacity: 0;
        transform: translate(0, 0) scale(0.75);
    }
    12% {
        opacity: 1;
    }
    50% {
        transform: translate(10px, -55vh) scale(1);
    }
    88% {
        opacity: 0.6;
    }
    100% {
        opacity: 0;
        transform: translate(18px, -100vh) scale(1.05);
    }
}
</style>