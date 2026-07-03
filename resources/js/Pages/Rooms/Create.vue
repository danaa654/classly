<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

defineOptions({
    layout: DashboardLayout,
})

const props = defineProps({
    roomGroupOptions: {
        type: Array,
        default: () => ['General', 'BSIT', 'BSED', 'BSHM', 'BSTM', 'BSCRIM'],
    },
})

const form = useForm({
    room_code: '',
    room_type: 'Lecture',
    // One or more programs this room is available to. General (every
    // department), Shared (several departments), or Exclusive (one).
    room_groups: [],
    building: '',
    floor: '',
    capacity: 30,
    active: true,
})

// "General" is a Lecture-only program — it's hidden from the checklist
// whenever Room Type is Laboratory (enforced server-side too, this is
// just UX). Mirrors the equivalent rule on the Subject Create/Edit forms
// for required_room_type / room_groups.
const roomGroupChoices = computed(() => {
    if (form.room_type === 'Laboratory') {
        return props.roomGroupOptions.filter(option => option !== 'General')
    }

    return props.roomGroupOptions
})

function toggleRoomGroup(option) {
    const index = form.room_groups.indexOf(option)

    if (index === -1) {
        // General can't be combined with anything else, so selecting it
        // clears any other selection; selecting a department drops
        // General from whatever was already picked.
        if (option === 'General') {
            form.room_groups = ['General']
        } else {
            form.room_groups = form.room_groups.filter(o => o !== 'General')
            form.room_groups.push(option)
        }
    } else {
        form.room_groups.splice(index, 1)
    }
}

// Keeps the program selection in sync with Room Type, the same way the
// Subject form keeps room_groups in sync with required_room_type. The
// backend enforces this too, but mirroring it here keeps the form from
// ever showing/submitting a selection that doesn't make sense for the
// selected room type — "General" isn't valid for Laboratory, so it's
// dropped from whatever was already selected.
watch(() => form.room_type, (newType) => {
    if (newType === 'Laboratory') {
        form.room_groups = form.room_groups.filter(option => option !== 'General')
    }
})

function submit() {
    form.post(route('rooms.store'))
}
</script>

<template>

<Head title="Add Room" />

<div>

    <!-- Header -->

    <div class="flex justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-bold text-[var(--text-primary)]">
                Add Room
            </h1>

            <p class="text-[var(--text-muted)] mt-1">
                Create a new room in the master list.
            </p>

        </div>

        <Link
            :href="route('rooms.index')"
            class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:underline transition-colors duration-150"
        >
            &larr; Back to Rooms
        </Link>

    </div>

    <!-- Form -->

    <form
        @submit.prevent="submit"
        class="mx-auto max-w-2xl bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-6 space-y-6"
    >

        <!-- Room Code / Room Type -->

        <div class="grid grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">
                    Room Code
                </label>

                <input
                    v-model="form.room_code"
                    type="text"
                    placeholder="e.g. Room 304 (ICT Workshop)"
                    class="w-full border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                />

                <p v-if="form.errors.room_code" class="text-red-600 text-sm mt-1">
                    {{ form.errors.room_code }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">
                    Room Type
                </label>

                <select
                    v-model="form.room_type"
                    class="w-full border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                >
                    <option value="Lecture">Lecture</option>
                    <option value="Laboratory">Laboratory</option>
                </select>

                <p v-if="form.errors.room_type" class="text-red-600 text-sm mt-1">
                    {{ form.errors.room_type }}
                </p>

            </div>

        </div>

        <!-- Available Programs (Room Groups) -->
        <!--
            Multi-select: a room can be General (every department), Shared
            by several departments (e.g. BSHM + BSTM laboratory), or
            Exclusive to one. General can't be combined with anything else.
        -->

        <div>

            <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">
                Available Programs
            </label>

            <p class="text-xs text-[var(--text-muted)] mb-2">
                Select General for a room usable by every department, or one
                or more specific programs for a Shared or Exclusive room.
            </p>

            <div class="flex flex-wrap gap-2 rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] p-3">

                <label
                    v-for="option in roomGroupChoices"
                    :key="option"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-full border text-sm cursor-pointer select-none transition-colors duration-150"
                    :class="form.room_groups.includes(option)
                        ? 'bg-blue-500/10 border-blue-400 text-blue-600 dark:text-blue-400'
                        : 'bg-[var(--card-bg)] border-[var(--card-border)] text-[var(--text-secondary)] hover:border-[#D4A62A]/40'"
                >
                    <input
                        type="checkbox"
                        class="rounded accent-[#D4A62A]"
                        :checked="form.room_groups.includes(option)"
                        @change="toggleRoomGroup(option)"
                    />
                    {{ option }}
                </label>

                <p v-if="form.room_type === 'Laboratory'" class="text-xs text-[var(--text-muted)] w-full">
                    Laboratory rooms must select one or more specific programs.
                </p>

            </div>

            <p v-if="form.errors.room_groups" class="text-red-600 text-sm mt-1">
                {{ form.errors.room_groups }}
            </p>

        </div>

        <!-- Location -->

        <div class="grid grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">
                    Building
                </label>

                <input
                    v-model="form.building"
                    type="text"
                    class="w-full border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                />

                <p v-if="form.errors.building" class="text-red-600 text-sm mt-1">
                    {{ form.errors.building }}
                </p>

            </div>

            <div>

                <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">
                    Floor
                </label>

                <input
                    v-model="form.floor"
                    type="text"
                    placeholder="e.g. 2nd Floor"
                    class="w-full border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                />

                <p v-if="form.errors.floor" class="text-red-600 text-sm mt-1">
                    {{ form.errors.floor }}
                </p>

            </div>

        </div>

        <!-- Capacity -->

        <div>

            <label class="block text-sm font-medium text-[var(--text-primary)] mb-1">
                Capacity
            </label>

            <input
                v-model.number="form.capacity"
                type="number"
                min="20"
                max="45"
                class="w-full border-[var(--card-border)] bg-[var(--page-bg)] text-[var(--text-primary)] rounded-lg transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
            />

            <p class="text-xs text-[var(--text-muted)] mt-1">
                Must be between 20 and 45.
            </p>

            <p v-if="form.errors.capacity" class="text-red-600 text-sm mt-1">
                {{ form.errors.capacity }}
            </p>

        </div>

        <!-- Status -->

        <div>

            <label class="flex items-center gap-2">
                <input type="checkbox" v-model="form.active" class="rounded accent-[#D4A62A]" />
                <span class="text-sm text-[var(--text-primary)]">Active</span>
            </label>

        </div>

        <!-- Actions -->

        <div class="flex justify-end gap-3 pt-4 border-t border-[var(--card-border)]">

            <Link
                :href="route('rooms.index')"
                class="btn-neutral"
            >
                Cancel
            </Link>

            <button
                type="submit"
                :disabled="form.processing"
                class="btn-save"
            >
                {{ form.processing ? 'Saving...' : 'Save Room' }}
            </button>

        </div>

    </form>

</div>

</template>