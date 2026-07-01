<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    room: Object,
})

const form = useForm({
    room_code: props.room.room_code,
    room_name: props.room.room_name,

    room_type: props.room.room_type,

    building: props.room.building,
    floor: props.room.floor ?? '',

    capacity: props.room.capacity,

    active: props.room.active,
})

function submit() {
    form.put(route('rooms.update', props.room.id))
}
</script>

<template>
    <DashboardLayout>

        <div class="max-w-5xl">

            <h1 class="text-3xl font-bold mb-6">
                Edit Room
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-lg shadow p-6 space-y-6"
            >

                <!-- Room Identity -->

                <div>

                    <h2 class="text-lg font-semibold mb-4">
                        Room Identity
                    </h2>

                    <div class="grid grid-cols-2 gap-4">

                        <div>

                            <label class="block mb-2">
                                Room Code
                            </label>

                            <input
                                v-model="form.room_code"
                                type="text"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.room_code"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.room_code }}
                            </p>

                        </div>

                        <div>

                            <label class="block mb-2">
                                Room Name
                            </label>

                            <input
                                v-model="form.room_name"
                                type="text"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.room_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.room_name }}
                            </p>

                        </div>

                    </div>

                </div>

                <!-- Room Type -->

                <div>

                    <label class="block mb-2">
                        Room Type
                    </label>

                    <select
                        v-model="form.room_type"
                        class="w-full border rounded p-2"
                    >
                        <option value="Lecture">Lecture</option>
                        <option value="Computer Laboratory">Computer Laboratory</option>
                        <option value="Science Laboratory">Science Laboratory</option>
                        <option value="Speech Laboratory">Speech Laboratory</option>
                        <option value="PE Area">PE Area</option>
                        <option value="Any">Any</option>
                    </select>

                    <p
                        v-if="form.errors.room_type"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.room_type }}
                    </p>

                </div>

                <!-- Location -->

                <div>

                    <h2 class="text-lg font-semibold mb-4">
                        Location
                    </h2>

                    <div class="grid grid-cols-2 gap-4">

                        <div>

                            <label class="block mb-2">
                                Building
                            </label>

                            <input
                                v-model="form.building"
                                type="text"
                                class="w-full border rounded p-2"
                            >

                            <p
                                v-if="form.errors.building"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.building }}
                            </p>

                        </div>

                        <div>

                            <label class="block mb-2">
                                Floor
                            </label>

                            <input
                                v-model="form.floor"
                                type="text"
                                class="w-full border rounded p-2"
                                placeholder="e.g. 2nd Floor"
                            >

                            <p
                                v-if="form.errors.floor"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.floor }}
                            </p>

                        </div>

                    </div>

                </div>

                <!-- Capacity -->

                <div>

                    <label class="block mb-2">
                        Capacity
                    </label>

                    <input
                        v-model="form.capacity"
                        type="number"
                        min="1"
                        class="w-full border rounded p-2"
                    >

                    <p
                        v-if="form.errors.capacity"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.capacity }}
                    </p>

                </div>

                <!-- Status -->

                <div>

                    <label class="block mb-2">
                        Status
                    </label>

                    <select
                        v-model="form.active"
                        class="w-full border rounded p-2"
                    >
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </select>

                </div>

                <!-- Buttons -->

                <div class="flex gap-3">

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Room' }}
                    </button>

                    <Link
                        :href="route('rooms.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded"
                    >
                        Cancel
                    </Link>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>