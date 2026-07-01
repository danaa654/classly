<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const user = computed(() => page.props.auth?.user)

const role = computed(() => {
    return user.value?.roles?.[0] ?? ''
})

function hasRole(...roles) {
    return roles.includes(role.value)
}
</script>

<template>
    <div class="w-64 bg-slate-900 text-white min-h-screen flex flex-col">

        <!-- Logo -->
        <div class="p-5 border-b border-slate-700">
            <h1 class="text-3xl font-bold">
                CLASSLY
            </h1>

            <p class="text-sm text-slate-400 mt-2">
                {{ role }}
            </p>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-3">

            <!-- Dashboard -->
            <Link
                :href="route('dashboard')"
                class="block px-5 py-3 hover:bg-slate-700"
            >
                Dashboard
            </Link>

            <!-- USERS (ADMIN ONLY) -->
            <template v-if="hasRole('Admin')">

                <Link
                    :href="route('users.index')"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Users
                </Link>

            </template>

            <!-- ADMIN + REGISTRAR -->

            <template v-if="hasRole('Admin', 'Registrar')">

                <Link
                    :href="route('departments.index')"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Colleges
                </Link>

                <Link
                    :href="route('programs.index')"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Programs
                </Link>

                <Link
                    :href="route('specializations.index')"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Specializations
                </Link>

                <Link
                    :href="route('curriculums.index')"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Curriculum
                </Link>

            </template>

            <!-- ADMIN + REGISTRAR + DEAN + ASSISTANT DEAN + OIC -->

            <template v-if="hasRole('Admin', 'Registrar', 'Dean', 'Assistant Dean', 'OIC')">

                <Link
                    :href="route('faculty.index')"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Faculty
                </Link>

                <Link
                    href="#"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Subjects
                </Link>

                <Link
                    href="#"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Rooms
                </Link>

                <Link
                    href="#"
                    class="block px-5 py-3 hover:bg-slate-700"
                >
                    Schedule
                </Link>

            </template>

        </nav>

    </div>
</template>