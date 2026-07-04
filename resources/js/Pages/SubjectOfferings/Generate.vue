<script setup>
import { computed, watch } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    academicTerms: Array,
    activeAcademicTermId: [Number, null],
    curriculums: Array,
})

const form = useForm({
    academic_term_id: props.activeAcademicTermId ?? '',
    curriculum_id: '',
    section_ids: [],
})

const selectedCurriculum = computed(() =>
    props.curriculums.find(c => c.id === Number(form.curriculum_id)) ?? null
)

// Sections grouped by Year Level for the checkbox layout.
const sectionsByYearLevel = computed(() => {
    if (!selectedCurriculum.value) return {}

    return selectedCurriculum.value.sections.reduce((groups, section) => {
        (groups[section.year_level] ??= []).push(section)
        return groups
    }, {})
})

// Changing Curriculum invalidates whatever Sections were checked —
// they belonged to the previous Curriculum's list.
watch(() => form.curriculum_id, () => {
    form.section_ids = []
})

function toggleSection(sectionId) {
    const index = form.section_ids.indexOf(sectionId)

    if (index === -1) {
        form.section_ids.push(sectionId)
    } else {
        form.section_ids.splice(index, 1)
    }
}

function yearLabel(yearLevel) {
    return { 1: 'First Year', 2: 'Second Year', 3: 'Third Year', 4: 'Fourth Year' }[yearLevel] ?? `Year ${yearLevel}`
}

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
                    Imports this Academic Term's semester of the selected Curriculum into
                    the Sections you check below. One Subject Offering is created for
                    every Subject in that semester, for every checked Section. Offerings
                    that already exist are never touched or duplicated.
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
                            {{ term.display_name }}{{ term.active ? ' (Active)' : '' }}
                        </option>
                    </select>
                    <p v-if="form.errors.academic_term_id" class="mt-1 text-sm text-rose-600">
                        {{ form.errors.academic_term_id }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold" style="color: var(--text-primary)">
                        Curriculum
                    </label>
                    <select
                        v-model="form.curriculum_id"
                        required
                        class="w-full rounded-lg border px-3 py-2 text-sm"
                        style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                    >
                        <option value="" disabled>Select a Curriculum&hellip;</option>
                        <option
                            v-for="curriculum in curriculums"
                            :key="curriculum.id"
                            :value="curriculum.id"
                            :disabled="!curriculum.has_items"
                        >
                            {{ curriculum.display_name }}{{ !curriculum.has_items ? ' (no Curriculum Items yet)' : '' }}
                        </option>
                    </select>
                    <p v-if="form.errors.curriculum_id" class="mt-1 text-sm text-rose-600">
                        {{ form.errors.curriculum_id }}
                    </p>
                </div>

                <div v-if="selectedCurriculum">
                    <label class="mb-2 block text-sm font-semibold" style="color: var(--text-primary)">
                        Sections to Open
                    </label>

                    <p v-if="selectedCurriculum.sections.length === 0" class="text-sm" style="color: var(--text-muted)">
                        This Curriculum has no active Sections yet.
                    </p>

                    <div v-else class="flex flex-col gap-4">
                        <div v-for="(sections, yearLevel) in sectionsByYearLevel" :key="yearLevel">
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                                {{ yearLabel(Number(yearLevel)) }}
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <label
                                    v-for="section in sections"
                                    :key="section.id"
                                    class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm"
                                    style="border-color: var(--card-border)"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.section_ids.includes(section.id)"
                                        @change="toggleSection(section.id)"
                                    />
                                    {{ section.section_code }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.section_ids" class="mt-2 text-sm text-rose-600">
                        {{ form.errors.section_ids }}
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
                        :disabled="form.processing || form.section_ids.length === 0"
                    >
                        {{ form.processing ? 'Generating…' : 'Generate Subject Offerings' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>