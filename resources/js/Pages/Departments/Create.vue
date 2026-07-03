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

        <div class="mx-auto max-w-2xl">
            <h1 class="text-3xl font-bold mb-6 text-[var(--text-primary)]">
                Add College
            </h1>

            <form
                @submit.prevent="submit"
                class="bg-[var(--card-bg)] border border-[var(--card-border)] rounded-2xl shadow p-6 space-y-5 transition-colors duration-300"
            >

                <!-- College Name -->
                <div>
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        College Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Abbreviation
                    </label>

                    <input
                        v-model="form.abbreviation"
                        type="text"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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
                    <label class="block mb-2 font-medium text-sm text-[var(--text-secondary)]">
                        Description
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="4"
                        class="w-full rounded-xl border border-[var(--card-border)] bg-[var(--page-bg)] px-3 py-2.5 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] transition-all duration-200 focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
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

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-save"
                >
                    {{ form.processing ? 'Saving...' : 'Save College' }}
                </button>

            </form>

        </div>

    </DashboardLayout>
</template>