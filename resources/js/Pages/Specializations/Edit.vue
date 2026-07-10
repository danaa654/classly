<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { SparklesIcon } from '@heroicons/vue/24/outline'

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

        <div class="relative">

            <!-- Subtle brand texture: faint grid + one soft gold glow, static (no animation) -->
            <div class="pointer-events-none absolute -inset-x-6 -inset-y-6 -z-10 overflow-hidden">
                <div
                    class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]"
                    style="background-image: linear-gradient(#1e3a5f 1px, transparent 1px), linear-gradient(90deg, #1e3a5f 1px, transparent 1px); background-size: 42px 42px;"
                ></div>
                <div class="absolute -top-16 right-0 h-64 w-64 rounded-full bg-[#D4A62A]/10 blur-3xl"></div>
            </div>

            <div class="mx-auto max-w-2xl">

                <div class="flex items-center gap-3 mb-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#D4A62A]/30 bg-[#D4A62A]/10 text-[#D4A62A]">
                        <SparklesIcon class="h-5.5 w-5.5" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold [font-family:'Fraunces',serif] text-[var(--text-primary)]">
                            Edit Specialization
                        </h1>
                        <p class="text-sm text-[var(--text-muted)]">
                            Update the details for this specialization
                        </p>
                    </div>
                </div>

                <form
                    @submit.prevent="submit"
                    class="relative overflow-hidden rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] shadow-lg p-6 space-y-5 transition-colors duration-300"
                >

                    <div class="pointer-events-none absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-transparent via-[#D4A62A] to-transparent"></div>

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

        </div>

    </DashboardLayout>
</template>