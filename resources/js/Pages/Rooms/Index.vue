<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Toast from '@/Components/Toast.vue'
import ManageSubjectsModal from '@/Components/Rooms/ManageSubjectsModal.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive, ref, watch } from 'vue'
import axios from 'axios'
import { useFlashToast } from '@/Composables/useFlashToast'

defineOptions({
    layout: DashboardLayout,
})

// Server-driven flashes (create/update/delete still redirect back here
// via Inertia, so those keep working as before) PLUS manual client-side
// flashes for the Manage Subjects modal below, which never redirects —
// show() is exactly what useFlashToast's docblock describes it for.
const { toast, show } = useFlashToast()

const props = defineProps({
    rooms: Array,
    roomGroupOptions: {
        type: Array,
        default: () => ['General', 'BSIT', 'BSED', 'BSHM', 'BSTM', 'BSCRIM'],
    },
    floorOptions: Array,
    filters: Object,
    weeklyCapacityHours: {
        type: Number,
        default: 60,
    },
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

/*
|--------------------------------------------------------------------------
| Preferred Load
|--------------------------------------------------------------------------
|
| preferred_hours comes from RoomController::index()'s withSum — the
| total hours of this room's Preferred Subject Offerings for the ACTIVE
| Academic Term only (see Rooms/ManageSubjects.vue). null means either
| there's no active term or nothing has been preferred yet, so it's
| treated as 0 here.
*/

function preferredHours(room) {
    return room.preferred_hours ?? 0
}

function preferredCount(room) {
    return room.preferred_count ?? 0
}

// scheduled_hours/scheduled_count come from RoomController::index() too,
// but are sourced from the real `schedules` table (Master Grid's actual
// generated/saved classes) — NOT the preference pivot above. Kept as
// separate fields so "preferred" and "actually scheduled" never get
// blended into one misleading number. See RoomController::index() and
// MasterGridDataService::presentRoom() for the same distinction there.
function scheduledHours(room) {
    return room.scheduled_hours ?? 0
}

function scheduledCount(room) {
    return room.scheduled_count ?? 0
}

// The bar/percent should always match what Master Grid's Room Sidebar
// shows for the SAME room (see MasterGridDataService::presentRoom()) —
// real scheduled hours take priority once any exist. Preferred Hours is
// only a pre-scheduling wishlist and stays purely informational (the
// small line beneath) once real classes exist; it's the primary number
// ONLY for rooms nothing has been scheduled to yet.
function primaryHours(room) {
    return scheduledCount(room) > 0 ? scheduledHours(room) : preferredHours(room)
}

function utilizationPercent(room) {
    if (!props.weeklyCapacityHours) return 0
    return Math.min(100, Math.round((primaryHours(room) / props.weeklyCapacityHours) * 100))
}

function isOverCapacity(room) {
    return primaryHours(room) > props.weeklyCapacityHours
}

function remainingHours(room) {
    return Math.max(0, props.weeklyCapacityHours - primaryHours(room))
}

/*
|--------------------------------------------------------------------------
| Manage Subjects Modal
|--------------------------------------------------------------------------
|
| Opening/saving/closing this modal is entirely client-side — no
| Inertia visit happens at any point in this flow, so Index.vue itself
| never re-renders. That's what keeps filters, scroll position, and
| everything else on this page exactly as the user left it.
*/

const modalData = ref(null)   // set once the fetch below resolves; null = closed
const modalLoading = ref(false)

function openManageSubjects(room) {
    modalLoading.value = true

    axios.get(route('rooms.manage-subjects', room.id))
        .then(response => {
            modalData.value = response.data
        })
        .catch(() => {
            show('Could not load Manage Subjects for this room. Please try again.', 'error')
        })
        .finally(() => {
            modalLoading.value = false
        })
}

function closeManageSubjects() {
    modalData.value = null
}

// The modal emits the server's fresh preferred_hours/preferred_count
// for its one room — find that room in the local list and patch just
// those two fields in place. This is a deliberate, narrow mutation of a
// prop's nested object (not a reassignment of the `rooms` prop itself),
// which is how this page gets its "update only the affected row, no
// reload" behavior — Vue does not warn about this, only about
// reassigning the prop reference.
function onSubjectsSaved(payload) {
    const room = props.rooms.find(r => r.id === payload.room_id)

    if (room) {
        room.preferred_hours = payload.preferred_hours
        room.preferred_count = payload.preferred_count
    }

    show(payload.message, 'success')
    closeManageSubjects()
}
</script>

<template>

<Head title="Rooms" />

<Toast :toast="toast" />

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
                        Room Load
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

                    <td class="px-4 py-3">

                        <div class="flex flex-col items-center gap-1">

                            <!--
                                Same wording/format as the Master Grid
                                Room Sidebar: a dot + "X hrs scheduled ·
                                Y classes" once anything real exists.
                                This — not Preferred Hours — is what
                                drives the big number and bar below.
                            -->
                            <span
                                v-if="scheduledCount(room) > 0"
                                class="inline-flex items-center gap-1 text-[10px] font-semibold text-green-600 dark:text-green-400 whitespace-nowrap"
                            >
                                ● {{ scheduledHours(room) }} hrs scheduled · {{ scheduledCount(room) }} class{{ scheduledCount(room) === 1 ? '' : 'es' }}
                            </span>

                            <span
                                class="text-xs font-semibold whitespace-nowrap"
                                :class="isOverCapacity(room) ? 'text-red-500' : 'text-[var(--text-primary)]'"
                            >
                                {{ primaryHours(room) }} / {{ weeklyCapacityHours }} hrs · {{ utilizationPercent(room) }}%
                            </span>

                            <span class="text-[10px] text-[var(--text-muted)] whitespace-nowrap">
                                Remaining: {{ remainingHours(room) }} hrs
                            </span>

                            <span
                                v-if="preferredCount(room) > 0"
                                class="text-[10px] text-[var(--text-muted)] whitespace-nowrap"
                            >
                                {{ preferredHours(room) }} hrs / {{ preferredCount(room) }} subject{{ preferredCount(room) === 1 ? '' : 's' }} preferred
                            </span>

                            <div class="w-20 h-1.5 rounded-full bg-[var(--page-bg)] overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="isOverCapacity(room) ? 'bg-red-500' : (scheduledCount(room) > 0 ? 'bg-green-500' : 'bg-[#D4A62A]')"
                                    :style="{ width: utilizationPercent(room) + '%' }"
                                />
                            </div>

                        </div>

                    </td>

                    <td class="px-4 py-3 text-center whitespace-nowrap">

                        <div class="flex justify-center gap-2">

                            <button
                                @click="openManageSubjects(room)"
                                type="button"
                                :disabled="modalLoading"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-[#D4A62A]/10 text-[#D4A62A] hover:bg-[#D4A62A]/20 transition-colors duration-150 whitespace-nowrap disabled:opacity-50"
                            >
                                Manage Subjects
                            </button>

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
                        colspan="9"
                        class="text-center py-8 text-[var(--text-muted)]"
                    >
                        No rooms found.
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <ManageSubjectsModal
        v-if="modalData"
        :initial-data="modalData"
        @close="closeManageSubjects"
        @saved="onSubjectsSaved"
    />

</div>

</template>