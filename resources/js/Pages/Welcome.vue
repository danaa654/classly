<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    UserGroupIcon,
    BookOpenIcon,
    AcademicCapIcon,
    BuildingOffice2Icon,
    CalendarDaysIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const features = [
    { name: 'Faculty', icon: UserGroupIcon },
    { name: 'Subjects', icon: BookOpenIcon },
    { name: 'Curriculum', icon: AcademicCapIcon },
    { name: 'Rooms', icon: BuildingOffice2Icon },
    { name: 'Schedules', icon: CalendarDaysIcon },
    { name: 'Conflict-Free', icon: ShieldCheckIcon },
];

const stats = [
    { label: 'Faculty', value: '120+' },
    { label: 'Subjects', value: '300+' },
    { label: 'Rooms', value: '40+' },
    { label: 'Programs', value: '25+' },
];
</script>

<template>
    <Head title="Welcome" />

    <div
        class="relative flex h-screen w-screen flex-col overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 text-white antialiased"
    >
        <!-- ===================== BACKGROUND GLOW BLOBS ===================== -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-indigo-500/30 blur-3xl"></div>
            <div class="absolute right-0 top-1/4 h-[28rem] w-[28rem] rounded-full bg-sky-400/20 blur-3xl"></div>
            <div class="absolute -bottom-32 left-1/4 h-96 w-96 rounded-full bg-purple-500/20 blur-3xl"></div>
            <div class="absolute bottom-1/4 right-1/4 h-72 w-72 rounded-full bg-cyan-400/10 blur-3xl"></div>
        </div>

        <!-- ===================== MAIN (fills full viewport, centered) ===================== -->
        <main class="relative z-10 flex flex-1 flex-col items-center justify-center gap-8 overflow-hidden px-6 py-6">
            <!-- Logo -->
            <div
                class="flex h-20 w-20 animate-[float_4s_ease-in-out_infinite] items-center justify-center rounded-full border border-white/20 bg-white/10 p-3 shadow-xl shadow-indigo-900/40 backdrop-blur-xl sm:h-24 sm:w-24"
            >
                <img src="/logo.png" alt="PAP logo" class="h-full w-full rounded-full object-contain" />
            </div>

            <!-- Hero -->
            <div class="animate-[fadein_0.6s_ease-out] text-center">
                <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    CLASSLY
                </h1>
                <p class="mt-2 text-base font-medium text-indigo-200 sm:text-lg">
                    Your Friendly Class Scheduler
                </p>
                <p class="text-xs text-white/50 sm:text-sm">
                    Professional Academy of the Philippines &ndash; Naga Cebu
                </p>
                <p class="mx-auto mt-4 max-w-xl text-xs leading-relaxed text-white/60 sm:text-sm">
                    Generate conflict-free schedules for faculty, classrooms, and academic
                    programs with an intelligent scheduling system designed for efficiency
                    and accuracy.
                </p>
            </div>

            <!-- Login (centered) -->
            <nav v-if="canLogin" class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-full bg-indigo-500 px-10 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition-all duration-300 hover:scale-105 hover:bg-indigo-400"
                >
                    Go to Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-full bg-indigo-500 px-10 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/30 transition-all duration-300 hover:scale-105 hover:bg-indigo-400"
                    >
                        Login
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-full border border-white/20 bg-white/10 px-10 py-3 text-sm font-semibold text-white backdrop-blur-xl transition-all duration-300 hover:scale-105 hover:bg-white/20"
                    >
                        Register
                    </Link>
                </template>
            </nav>

            <!-- Feature cards -->
            <div class="grid w-full max-w-4xl grid-cols-3 gap-3 sm:grid-cols-6 sm:gap-4">
                <div
                    v-for="feature in features"
                    :key="feature.name"
                    class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-3 py-4 text-center shadow-lg backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:bg-white/10"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-indigo-500/20 text-indigo-300">
                        <component :is="feature.icon" class="h-5 w-5" />
                    </div>
                    <span class="text-[11px] font-medium text-white/70 sm:text-xs">{{ feature.name }}</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid w-full max-w-2xl grid-cols-4 gap-3 sm:gap-5">
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="rounded-2xl border border-white/10 bg-white/5 px-3 py-4 text-center shadow-lg backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:bg-white/10 sm:py-5"
                >
                    <p class="text-xl font-bold text-white sm:text-2xl">{{ stat.value }}</p>
                    <p class="text-[10px] uppercase tracking-wider text-white/40 sm:text-xs">
                        {{ stat.label }}
                    </p>
                </div>
            </div>
        </main>

        <!-- ===================== FOOTER ===================== -->
        <footer class="relative z-10 border-t border-white/10 px-6 py-3 text-center">
            <p class="text-[10px] text-white/40 sm:text-xs">
                &copy; 2025 Professional Academy of the Philippines &ndash; Naga Cebu &mdash;
                CLASSLY. All Rights Reserved.
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
</style>