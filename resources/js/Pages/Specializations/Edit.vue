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

        <div class="mx-auto max-w-2xl">

            <h1 class="text-3xl font-bold mb-6 text-[var(--text-primary)]">
                Edit Specialization
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-6 space-y-5 transition-colors duration-300"
            >

                <!-- Program -->

                <div>

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Program
                    </label>

                    <select
                        v-model="form.program_id"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                    >

                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.id"
                        >
                            {{ program.department.abbreviation }} - {{ program.code }} - {{ program.name }}
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

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Specialization Code
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Specialization Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Status
                    </label>

                    <select
                        v-model="form.active"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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
                        class="btn-save"
                    >
                        {{ form.processing ? 'Updating...' : 'Update Specialization' }}
                    </button>

                    <a
                        :href="route('specializations.index')"
                        class="btn-neutral"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>