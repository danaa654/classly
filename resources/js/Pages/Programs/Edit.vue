<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { PencilSquareIcon } from '@heroicons/vue/24/outline'

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

        <div class="relative mx-auto max-w-2xl">

            <!-- Subtle brand texture: faint grid + one soft gold glow, static (no animation) -->
            <div class="pointer-events-none absolute -inset-x-6 -inset-y-6 -z-10 overflow-hidden">
                <div
                    class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]"
                    style="background-image: linear-gradient(#1e3a5f 1px, transparent 1px), linear-gradient(90deg, #1e3a5f 1px, transparent 1px); background-size: 42px 42px;"
                ></div>
                <div class="absolute -top-16 right-0 h-64 w-64 rounded-full bg-[#D4A62A]/10 blur-3xl"></div>
            </div>

            <div class="flex items-center gap-3 mb-6">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#D4A62A]/30 bg-[#D4A62A]/10 text-[#D4A62A]">
                    <PencilSquareIcon class="h-5.5 w-5.5" />
                </div>
                <h1 class="text-3xl font-bold [font-family:'Fraunces',serif] text-[var(--text-primary)]">
                    Edit Program
                </h1>
            </div>

            <form
                @submit.prevent="submit"
                class="relative overflow-hidden rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] shadow-lg p-6 space-y-5 transition-colors duration-300"
            >

                <div class="pointer-events-none absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-[#D4A62A] to-transparent"></div>

                <!-- College -->
                <div>

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        College
                    </label>

                    <select
                        v-model="form.department_id"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Program Code
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

                <!-- Program Name -->
                <div>

                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Program Name
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

                <!-- Years -->
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
                        {{ form.processing ? 'Updating...' : 'Update Program' }}
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