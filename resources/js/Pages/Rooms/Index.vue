<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    rooms: Array,
    roomGroupOptions: {
        type: Array,
        default: () => ['General', 'BSIT', 'BSED', 'BSHM', 'BSTM', 'BSCRIM'],
    },
    floorOptions: Array,
    filters: Object,
})

/*
|--------------------------------------------------------------------------
| Filter State
|--------------------------------------------------------------------------
|
| Seeded from the `filters` prop the controller echoes back, so a page
| refresh (or a bookmarked/shared URL) restores the exact same filtered
| view instead of resetting to "all rooms".
|
| room_group here filters against a single program at a time — it matches
| only rooms whose Available Programs includes that exact program, via
| the forRoomGroup scope server-side (General stays under General, not
| folded into every other filter too).
|
*/

const form = reactive({
    search: props.filters.search ?? '',
    room_type: props.filters.room_type ?? '',
    floor: props.filters.floor ?? '',
    room_group: props.filters.room_group ?? '',
})

function applyFilters() {
    const query = {}

    for (const key in form) {
        if (form[key] !== '' && form[key] !== null) {
            query[key] = form[key]
        }
    }

    router.get(route('rooms.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['rooms', 'filters'],
    })
}

let searchTimeout = null

watch(() => form.search, () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        applyFilters()
    }, 350)
})

function resetFilters() {
    form.search = ''
    form.room_type = ''
    form.floor = ''
    form.room_group = ''

    applyFilters()
}

function destroyRoom(room) {
    if (!confirm(`Delete ${room.room_code}? This cannot be undone.`)) {
        return
    }

    router.delete(route('rooms.destroy', room.id), {
        preserveScroll: true,
    })
}
</script>

<template>

<Head title="Rooms" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Rooms
            </h1>

            <p class="text-[var(--text-muted)] mt-1">
                Manage the master list of rooms.
            </p>

        </div>

        <Link
            :href="route('rooms.create')"
            class="btn-save"
        >
            + New Room
        </Link>

    </div>

    <!-- Search & Filters -->

    <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-4 mb-6">

        <div class="flex flex-col lg:flex-row lg:items-center gap-3">

            <!-- Search -->

            <input
                v-model="form.search"
                type="text"
                placeholder="Search by room code or building..."
                class="w-full lg:flex-1 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            />

            <!-- Room Type -->

            <select
                v-model="form.room_type"
                @change="applyFilters()"
                class="w-full lg:w-44 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            >
                <option value="">All Room Types</option>
                <option value="Lecture">Lecture</option>
                <option value="Laboratory">Laboratory</option>
            </select>

            <!-- Floor -->

            <select
                v-model="form.floor"
                @change="applyFilters()"
                class="w-full lg:w-40 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            >
                <option value="">All Floors</option>
                <option v-for="floor in floorOptions" :key="floor" :value="floor">
                    {{ floor }}
                </option>
            </select>

            <!-- Available Programs -->

            <select
                v-model="form.room_group"
                @change="applyFilters()"
                class="w-full lg:w-40 border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg text-sm focus:border-[#D4A62A] focus:ring-[#D4A62A]/30"
            >
                <option value="">All Programs</option>
                <option v-for="option in roomGroupOptions" :key="option" :value="option">
                    {{ option }}
                </option>
            </select>

            <!-- Reset -->

            <button
                @click="resetFilters"
                type="button"
                class="w-full lg:w-auto px-4 py-2 text-sm rounded-lg border border-[var(--card-border)] text-[var(--text-secondary)] hover:bg-[var(--page-bg)] hover:text-[var(--text-primary)] transition-colors duration-150 whitespace-nowrap"
            >
                Reset Filters
            </button>

        </div>

    </div>

    <!-- Table -->

    <div class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-[var(--page-bg)] border-b border-[var(--card-border)]">

                <tr>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Room Code
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Room Type
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Available Programs
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Building
                    </th>

                    <th class="px-4 py-3 text-left text-[var(--text-secondary)]">
                        Floor
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Capacity
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Status
                    </th>

                    <th class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

                <tr
                    v-for="room in rooms"
                    :key="room.id"
                    class="border-t border-[var(--card-border)] transition-colors duration-150 hover:bg-[var(--page-bg)]"
                >

                    <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">
                        {{ room.room_code }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        {{ room.room_type }}
                    </td>

                    <!--
                        A room can carry several programs (e.g. a
                        laboratory Shared by BSHM + BSTM), so this renders
                        one badge per assigned program instead of a single
                        value.
                    -->
                    <td class="px-4 py-3">

                        <div
                            v-if="room.room_group_codes && room.room_group_codes.length"
                            class="flex flex-wrap gap-1"
                        >
                            <span
                                v-for="group in room.room_group_codes"
                                :key="group"
                                class="inline-flex px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 text-xs font-medium whitespace-nowrap"
                            >
                                {{ group }}
                            </span>
                        </div>

                        <span v-else class="text-[var(--text-muted)] text-sm">
                            —
                        </span>

                    </td>

                    <td class="px-4 py-3 text-[var(--text-secondary)]">
                        {{ room.building }}
                    </td>

                    <td class="px-4 py-3 text-[var(--text-secondary)]">
                        {{ room.floor ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-center text-[var(--text-secondary)]">
                        {{ room.capacity }}
                    </td>

                    <td class="px-4 py-3 text-center">

                        <span
                            v-if="room.active"
                            class="inline-flex px-2 py-1 rounded-full bg-green-500/10 text-green-600 dark:text-green-400 text-xs font-medium"
                        >
                            Active
                        </span>

                        <span
                            v-else
                            class="inline-flex px-2 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400 text-xs font-medium"
                        >
                            Inactive
                        </span>

                    </td>

                    <td class="px-4 py-3 text-center whitespace-nowrap">

                        <div class="flex justify-center gap-2">

                            <Link
                                :href="route('rooms.edit', room.id)"
                                class="btn-edit"
                            >
                                Edit
                            </Link>

                            <button
                                @click="destroyRoom(room)"
                                class="btn-delete"
                            >
                                Delete
                            </button>

                        </div>

                    </td>

                </tr>

                <tr v-if="rooms.length === 0">

                    <td
                        colspan="8"
                        class="text-center py-8 text-[var(--text-muted)]"
                    >
                        No rooms found.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>

</template>