<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    rooms: Array,
})

function destroy(id) {
    if (confirm('Are you sure you want to delete this room?')) {
        router.delete(route('rooms.destroy', id))
    }
}
</script>

<template>
    <DashboardLayout>

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                Rooms
            </h1>

            <Link
                :href="route('rooms.create')"
                class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
            >
                Add Room
            </Link>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <table class="w-full">

                <thead class="bg-gray-100">

                    <tr>
                        <th class="p-4 text-left w-12">#</th>
                        <th class="p-4 text-left">Room Code</th>
                        <th class="p-4 text-left">Room Name</th>
                        <th class="p-4 text-left">Room Type</th>
                        <th class="p-4 text-left">Building</th>
                        <th class="p-4 text-left">Floor</th>
                        <th class="p-4 text-left">Capacity</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-center whitespace-nowrap">
                            Actions
                        </th>
                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="(room, index) in rooms"
                        :key="room.id"
                        class="border-t hover:bg-gray-50"
                    >

                        <td class="p-4">
                            {{ index + 1 }}
                        </td>

                        <td class="p-4 font-medium">
                            {{ room.room_code }}
                        </td>

                        <td class="p-4">
                            {{ room.room_name }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="room.room_type === 'Lecture'"
                                class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm"
                            >
                                Lecture
                            </span>

                            <span
                                v-else-if="room.room_type === 'Computer Laboratory'"
                                class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm"
                            >
                                Computer Laboratory
                            </span>

                            <span
                                v-else-if="room.room_type === 'Science Laboratory'"
                                class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm"
                            >
                                Science Laboratory
                            </span>

                            <span
                                v-else-if="room.room_type === 'Speech Laboratory'"
                                class="bg-pink-100 text-pink-700 px-3 py-1 rounded-full text-sm"
                            >
                                Speech Laboratory
                            </span>

                            <span
                                v-else-if="room.room_type === 'PE Area'"
                                class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm"
                            >
                                PE Area
                            </span>

                            <span
                                v-else
                                class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm"
                            >
                                Any
                            </span>

                        </td>

                        <td class="p-4">
                            {{ room.building }}
                        </td>

                        <td class="p-4">
                            {{ room.floor || '-' }}
                        </td>

                        <td class="p-4">
                            {{ room.capacity }}
                        </td>

                        <td class="p-4">

                            <span
                                v-if="room.active"
                                class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm"
                            >
                                Active
                            </span>

                            <span
                                v-else
                                class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm"
                            >
                                Inactive
                            </span>

                        </td>

                        <td class="p-4 whitespace-nowrap">

                            <div class="flex justify-center gap-2">

                                <Link
                                    :href="route('rooms.edit', room.id)"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="destroy(room.id)"
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded"
                                >
                                    Delete
                                </button>

                            </div>

                        </td>

                    </tr>

                    <tr v-if="rooms.length === 0">

                        <td
                            colspan="9"
                            class="text-center p-8 text-gray-500"
                        >
                            No rooms found.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </DashboardLayout>
</template>