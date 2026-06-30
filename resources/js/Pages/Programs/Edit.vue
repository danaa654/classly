<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    program: Object,
    departments: Array,
})

const form = useForm({
    department_id: props.program.department_id,
    code: props.program.code,
    name: props.program.name,
    years: props.program.years,
    active: props.program.active,
})

function submit() {
    form.put(route('programs.update', props.program.id))
}
</script>

<template>
    <DashboardLayout>

        <div class="max-w-2xl">

            <h1 class="text-3xl font-bold mb-6">
                Edit Program
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-lg shadow p-6 space-y-5"
            >

                <!-- College -->
                <div>

                    <label class="block mb-2 font-medium">
                        College
                    </label>

                    <select
                        v-model="form.department_id"
                        class="w-full border rounded p-2"
                    >

                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="department.id"
                        >
                            {{ department.abbreviation }} - {{ department.name }}
                        </option>

                    </select>

                    <p
                        v-if="form.errors.department_id"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.department_id }}
                    </p>

                </div>

                <!-- Program Code -->
                <div>

                    <label class="block mb-2 font-medium">
                        Program Code
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        class="w-full border rounded p-2"
                    />

                    <p
                        v-if="form.errors.code"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.code }}
                    </p>

                </div>

                <!-- Program Name -->
                <div>

                    <label class="block mb-2 font-medium">
                        Program Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border rounded p-2"
                    />

                    <p
                        v-if="form.errors.name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </p>

                </div>

                <!-- Years -->
                <div>

                    <label class="block mb-2 font-medium">
                        Program Duration (Years)
                    </label>

                    <select
                        v-model="form.years"
                        class="w-full border rounded p-2"
                    >
                        <option :value="1">1 Year</option>
                        <option :value="2">2 Years</option>
                        <option :value="3">3 Years</option>
                        <option :value="4">4 Years</option>
                        <option :value="5">5 Years</option>
                        <option :value="6">6 Years</option>
                    </select>

                    <p
                        v-if="form.errors.years"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.years }}
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

                <!-- Buttons -->
                <div class="flex gap-3">

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Program' }}
                    </button>

                    <a
                        :href="route('programs.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>