<script setup>
import { Head, Link } from '@inertiajs/vue3';
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
        class="relative flex h-screen w-screen flex-col overflow-hidden bg-[#0B1220] text-[#F3EFE6] antialiased [font-family:'IBM_Plex_Sans',sans-serif]"
    >
        <!-- ===================== TIMETABLE GRID TEXTURE ===================== -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute inset-0 opacity-[0.05]"
                style="
                    background-image:
                        linear-gradient(#f3efe6 1px, transparent 1px),
                        linear-gradient(90deg, #f3efe6 1px, transparent 1px);
                    background-size: 56px 56px;
                "
            ></div>
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-[#D4A62A]/10 blur-3xl"></div>
            <div class="absolute right-0 top-1/4 h-[28rem] w-[28rem] rounded-full bg-[#7A2E3B]/20 blur-3xl"></div>
            <div class="absolute -bottom-32 left-1/4 h-96 w-96 rounded-full bg-[#D4A62A]/5 blur-3xl"></div>

            <!-- Fireflies -->
            <span
                v-for="(f, i) in fireflies"
                :key="i"
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
        </div>

        <!-- ===================== MAIN ===================== -->
        <main class="relative z-10 flex flex-1 flex-col items-center justify-center gap-6 overflow-hidden px-6">
            <!-- Emblem -->
            <div
                class="flex h-16 w-16 animate-[float_4s_ease-in-out_infinite] items-center justify-center rounded-full border border-[#D4A62A]/40 bg-[#151B2E] p-2.5 shadow-xl shadow-black/40 transition-transform duration-300 hover:scale-110 sm:h-20 sm:w-20"
            >
                <img src="/logo.png" alt="PAP logo" class="h-full w-full rounded-full object-contain" />
            </div>

            <!-- Hero -->
            <div class="animate-[fadein_0.6s_ease-out] text-center">
                <h1
                    class="text-4xl font-semibold tracking-tight text-[#F3EFE6] [font-family:'Fraunces',serif] sm:text-5xl lg:text-6xl"
                >
                    CLASSLY
                </h1>
                <p class="mt-2 text-base font-medium text-[#E8C766] sm:text-lg">
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
                        class="rounded-full border border-[#F3EFE6]/20 bg-[#F3EFE6]/5 px-10 py-2.5 text-sm font-semibold text-[#F3EFE6] transition-all duration-300 hover:scale-105 hover:border-[#D4A62A]/40 hover:bg-[#F3EFE6]/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#F3EFE6]/40"
                    >
                        Register
                    </Link>
                </template>
            </nav>

            <!-- What Classly handles -->
            <div class="w-full max-w-5xl">
                <p
                    class="mb-3 text-center text-[10px] uppercase tracking-[0.3em] text-[#8B93A7] [font-family:'IBM_Plex_Mono',monospace] sm:text-xs"
                >
                    What Classly Handles
                </p>
                <div class="grid w-full grid-cols-2 gap-3 sm:grid-cols-3">
                    <div
                        v-for="capability in capabilities"
                        :key="capability.name"
                        class="group flex flex-col gap-2 rounded-2xl border border-[#F3EFE6]/10 bg-[#151B2E]/60 px-4 py-3.5 text-left shadow-lg backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-[#D4A62A]/40 hover:bg-[#151B2E] hover:shadow-[0_10px_30px_-10px_rgba(212,166,42,0.25)]"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#D4A62A]/20 bg-[#D4A62A]/10 text-[#D4A62A] transition-all duration-300 group-hover:scale-110 group-hover:bg-[#D4A62A]/20"
                        >
                            <component :is="capability.icon" class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold text-[#F3EFE6] transition-colors duration-300 group-hover:text-[#E8C766] sm:text-sm"
                            >
                                {{ capability.name }}
                            </p>
                            <p class="mt-1 hidden text-xs leading-relaxed text-[#8B93A7] sm:block">
                                {{ capability.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ===================== FOOTER ===================== -->
        <footer class="relative z-10 border-t border-[#F3EFE6]/10 px-6 py-2.5 text-center">
            <p class="text-[10px] text-[#8B93A7] sm:text-xs">
                &copy; 2025 Professional Academy of the Philippines &ndash; Naga Cebu &mdash;
                CLASSLY. All Rights Reserved.
            </p>
            <p class="mt-0.5 text-[9px] text-[#8B93A7]/70 sm:text-[10px]">
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
    .firefly {
        display: none;
    }
}
</style>