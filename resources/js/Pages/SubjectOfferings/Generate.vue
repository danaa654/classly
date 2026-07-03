<script setup>
import { computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    academicTerms: Array,
})

const form = useForm({
    academic_term_id: '',
})

const selectedTerm = computed(() =>
    props.academicTerms.find(t => t.id === Number(form.academic_term_id)) ?? null
)

const hasExistingOfferings = computed(() =>
    (selectedTerm.value?.subject_offerings_count ?? 0) > 0
)

function submit() {
    form.post(route('subject-offerings.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Generate Subject Offerings" />

    <AppLayout>
        <div class="mx-auto flex max-w-2xl flex-col gap-6">

            <div>
                <Link
                    :href="route('subject-offerings.index')"
                    class="text-sm underline"
                    style="color: var(--text-secondary)"
                >
                    &larr; Back to Subject Offerings
                </Link>
                <h1 class="mt-2 text-2xl font-semibold" style="color: var(--text-primary)">
                    Generate Subject Offerings
                </h1>
                <p class="mt-1 text-sm" style="color: var(--text-secondary)">
                    Select an Academic Term. Every active Section's Curriculum will be
                    scanned for Curriculum Items matching that term's semester, and one
                    Subject Offering (status Pending, EDP Code auto-assigned) will be
                    created for each match that doesn't already exist. Offerings that
                    already exist — including any faculty/room assignment, status, or
                    schedule on them — are never touched or recreated.
                </p>
            </div>

            <form
                @submit.prevent="submit"
                class="flex flex-col gap-5 rounded-xl border p-6"
                style="background: var(--card-bg); border-color: var(--card-border)"
            >
                <div>
                    <label class="mb-1 block text-sm font-semibold" style="color: var(--text-primary)">
                        Academic Term
                    </label>
                    <select
                        v-model="form.academic_term_id"
                        required
                        class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                    >
                        <option value="" disabled>Select an Academic Term&hellip;</option>
                        <option v-for="term in academicTerms" :key="term.id" :value="term.id">
                            {{ term.display_name }}
                            {{ term.subject_offerings_count > 0 ? `(${term.subject_offerings_count} offerings already generated)` : '' }}
                        </option>
                    </select>
                    <p v-if="form.errors.academic_term_id" class="mt-1 text-sm text-rose-600">
                        {{ form.errors.academic_term_id }}
                    </p>
                </div>

                <!-- Informational note — non-blocking, since Generate is additive -->
                <div
                    v-if="hasExistingOfferings"
                    class="rounded-lg border border-sky-300 bg-sky-50 p-4"
                >
                    <p class="text-sm font-semibold text-sky-900">
                        {{ selectedTerm.subject_offerings_count }} Subject Offering(s) already
                        exist for {{ selectedTerm.display_name }}.
                    </p>
                    <p class="mt-1 text-sm text-sky-800">
                        Generating again will NOT duplicate, overwrite, or delete them —
                        only Subject Offerings that don't exist yet (e.g. for a newly
                        added Section) will be created.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('subject-offerings.index')"
                        class="btn-neutral rounded-lg px-4 py-2 text-sm font-semibold"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="btn-info rounded-lg px-5 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="form.processing || !form.academic_term_id"
                    >
                        {{ form.processing ? 'Generating…' : 'Generate Subject Offerings' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>