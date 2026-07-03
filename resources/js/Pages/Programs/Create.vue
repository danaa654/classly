<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    departments: Array,
})

const form = useForm({
    department_id: '',
    code: '',
    name: '',
    years: 4,
    active: true,
})

function submit() {
    form.post(route('programs.store'))
}
</script>

<template>
    <DashboardLayout>

        <div class="mx-auto max-w-2xl">

            <h1 class="text-3xl font-bold mb-6 text-[var(--text-primary)]">
                Add Program
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-6 space-y-5 transition-colors duration-300"
            >

                <!-- College -->

                <div>
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        College
                    </label>

                    <select
                        v-model="form.department_id"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                    >
                        <option value="">
                            Select College
                        </option>

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
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Program Code
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        placeholder="Example: BSIT"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Program Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Bachelor of Science in Information Technology"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                    />

                    <p
                        v-if="form.errors.name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Number of Years -->

                <div>
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Program Duration (Years)
                    </label>

                    <select
                        v-model="form.years"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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
                        class="btn-save"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Program' }}
                    </button>

                    <a
                        :href="route('programs.index')"
                        class="btn-neutral"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </DashboardLayout>
</template>