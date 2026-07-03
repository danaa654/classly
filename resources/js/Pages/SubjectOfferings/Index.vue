<script setup>
import { computed, reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    offerings: Object,
    academicTerms: Array,
    programs: Array,
    sections: Array,
    specializations: Array,
    filters: Object,
})

const STATUSES = ['Pending', 'Confirmed', 'Cancelled']

const form = reactive({
    academic_term_id: props.filters.academic_term_id ?? '',
    program_id: props.filters.program_id ?? '',
    specialization_id: props.filters.specialization_id ?? '',
    section_id: props.filters.section_id ?? '',
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
})

let searchTimeout = null

function applyFilters() {
    router.get(route('subject-offerings.index'), { ...form }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

watch(() => form.search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 350)
})

// Changing Program invalidates whatever Specialization/Section was
// selected before (they belonged to the previous Program's list), so
// clear both — the combined watch below then reloads with the fresh
// (empty) values alongside the new program_id.
watch(() => form.program_id, () => {
    form.specialization_id = ''
    form.section_id = ''
})

// Changing Specialization invalidates the selected Section the same way.
watch(() => form.specialization_id, () => {
    form.section_id = ''
})

watch(
    () => [form.academic_term_id, form.program_id, form.specialization_id, form.section_id, form.status],
    applyFilters
)

// The Specialization filter only makes sense for BSCRIM today (the one
// Program with active Specializations that actually split its Sections
// up further). Every other Program hides it completely.
const selectedProgram = computed(() =>
    props.programs.find(p => p.id === form.program_id) ?? null
)

const showSpecializationFilter = computed(() =>
    selectedProgram.value?.code === 'BSCRIM'
)

const activeTermLabel = computed(() => {
    const term = props.academicTerms.find(t => t.id === form.academic_term_id)
    return term ? term.display_name : null
})

function statusBadgeClass(status) {
    return {
        Pending: 'bg-amber-100 text-amber-800 border-amber-200',
        Confirmed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        Cancelled: 'bg-rose-100 text-rose-800 border-rose-200',
    }[status] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}
</script>

<template>
    <Head title="Subject Offerings" />

    <AppLayout>
        <div class="flex flex-col gap-6">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold" style="color: var(--text-primary)">
                        Subject Offerings
                    </h1>
                    <p class="text-sm" style="color: var(--text-secondary)">
                        Classes generated from Sections + Curriculum for a selected Academic Term.
                        <span v-if="activeTermLabel"> Showing: <strong>{{ activeTermLabel }}</strong></span>
                    </p>
                </div>

                <Link
                    :href="route('subject-offerings.create')"
                    class="btn-info inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white"
                >
                    ⚙️ Generate Subject Offerings
                </Link>
            </div>

            <!-- Filters -->
            <div
                class="rounded-xl border p-4"
                style="background: var(--card-bg); border-color: var(--card-border)"
            >
                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                    :class="showSpecializationFilter ? 'lg:grid-cols-6' : 'lg:grid-cols-5'"
                >
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Academic Term
                        </label>
                        <select
                            v-model="form.academic_term_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All Terms</option>
                            <option v-for="term in academicTerms" :key="term.id" :value="term.id">
                                {{ term.display_name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Program
                        </label>
                        <select
                            v-model="form.program_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All Programs</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.code }}
                            </option>
                        </select>
                    </div>

                    <div v-if="showSpecializationFilter">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Specialization
                        </label>
                        <select
                            v-model="form.specialization_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All</option>
                            <option v-for="specialization in specializations" :key="specialization.id" :value="specialization.id">
                                {{ specialization.code }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Section
                        </label>
                        <select
                            v-model="form.section_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All Sections</option>
                            <option v-for="section in sections" :key="section.id" :value="section.id">
                                {{ section.section_code }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Status
                        </label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in STATUSES" :key="status" :value="status">
                                {{ status }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Search
                        </label>
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="EDP Code, Section, Subject..."
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-xl border"
                style="background: var(--card-bg); border-color: var(--card-border)"
            >
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b" style="border-color: var(--card-border)">
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">EDP Code</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Section</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Subject Code</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Subject Title</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Units</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Faculty</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="offering in offerings.data"
                            :key="offering.id"
                            class="border-b last:border-0"
                            style="border-color: var(--card-border)"
                        >
                            <td class="px-4 py-3 font-mono font-medium" style="color: var(--text-primary)">
                                {{ offering.edp_code }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.section?.section_code }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.subject?.subject_code }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.subject?.descriptive_title }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.subject?.units }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-secondary)">
                                {{ offering.faculty?.full_name ?? '— Unassigned —' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    :class="statusBadgeClass(offering.status)"
                                >
                                    {{ offering.status }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="offerings.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center" style="color: var(--text-muted)">
                                No Subject Offerings found. Try adjusting your filters, or
                                <Link :href="route('subject-offerings.create')" class="underline">generate offerings</Link>
                                for an Academic Term.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="offerings.links?.length > 3" class="flex flex-wrap items-center justify-center gap-1">
                <Link
                    v-for="(link, index) in offerings.links"
                    :key="index"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    class="rounded-md px-3 py-1.5 text-sm"
                    :class="link.active
                        ? 'btn-info text-white'
                        : link.url
                            ? 'btn-neutral'
                            : 'pointer-events-none opacity-40'"
                    preserve-scroll
                    preserve-state
                />
            </div>
        </div>
    </AppLayout>
</template>