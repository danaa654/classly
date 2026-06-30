<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    abbreviation: '',
    description: '',
    active: true,
})

function submit() {
    form.post('/departments')
}
</script>

<template>
    <DashboardLayout>

        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold mb-6">
                Add College
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-lg shadow p-6 space-y-5"
            >

                <!-- College Name -->
                <div>
                    <label class="block mb-2 font-medium">
                        College Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border rounded p-2"
                        placeholder="College of Computer Studies"
                    />

                    <p
                        v-if="form.errors.name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Abbreviation -->
                <div>
                    <label class="block mb-2 font-medium">
                        Abbreviation
                    </label>

                    <input
                        v-model="form.abbreviation"
                        type="text"
                        class="w-full border rounded p-2"
                        placeholder="CCS"
                    />

                    <p
                        v-if="form.errors.abbreviation"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.abbreviation }}
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block mb-2 font-medium">
                        Description
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full border rounded p-2"
                        placeholder="Handles BSIT, BSCS, BSIS and other computer-related programs."
                    ></textarea>

                    <p
                        v-if="form.errors.description"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block mb-2 font-medium">
                        Status
                    </label>

                    <select
                        v-model="form.active"
                        class="w-full border rounded p-2"
                    >
                        <option :value="true">
                            Active
                        </option>

                        <option :value="false">
                            Inactive
                        </option>
                    </select>

                    <p
                        v-if="form.errors.active"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.active }}
                    </p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded"
                >
                    {{ form.processing ? 'Saving...' : 'Save College' }}
                </button>

            </form>

        </div>

    </DashboardLayout>
</template>