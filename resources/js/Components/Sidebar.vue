<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <div class="w-64 bg-slate-900 text-white min-h-screen">

        <div class="p-5 text-2xl font-bold border-b border-slate-700">
            CLASSLY
        </div>

        <nav class="mt-5">

            <Link
                href="/"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Dashboard
            </Link>

            <Link
                href="/users"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Users
            </Link>

            <Link
                href="/departments"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Colleges
            </Link>

            <Link
                href="/programs"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Programs
            </Link>

            <Link
                href="/faculty"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Faculty
            </Link>

            <Link
                href="/subjects"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Subjects
            </Link>

            <Link
                href="/rooms"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Rooms
            </Link>

            <Link
                href="/schedule"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Schedule
            </Link>

        </nav>

    </div>
</template>