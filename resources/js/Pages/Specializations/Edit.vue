<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    specialization: Object,
    programs: Array,
})

const form = useForm({
    program_id: props.specialization.program_id,
    code: props.specialization.code,
    name: props.specialization.name,
    active: props.specialization.active,
})

function submit() {
    form.put(route('specializations.update', props.specialization.id))
}
</script>

<template>
    <DashboardLayout>

        <div class="max-w-2xl">

            <h1 class="text-3xl font-bold mb-6">
                Edit Specialization
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-white rounded-lg shadow p-6 space-y-5"
            >

                <!-- Program -->

                <div>

                    <label class="block mb-2 font-medium">
                        Program
                    </label>

                    <select
                        v-model="form.program_id"
                        class="w-full border rounded p-2"
                    >

                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.id"
                        >
                            {{ program.department.short_name }} - {{ program.code }} - {{ program.name }}
                        </option>

                    </select>

                    <p
                        v-if="form.errors.program_id"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.program_id }}
                    </p>

                </div>

                <!-- Code -->

                <div>

                    <label class="block mb-2 font-medium">
                        Specialization Code
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

                <!-- Name -->

                <div>

                    <label class="block mb-2 font-medium">
                        Specialization Name
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

                </div>

                <div class="flex gap-3">

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Specialization' }}
                    </button>

                    <a
                        :href="route('specializations.index')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>