<script setup>
import { computed, reactive, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    offerings: Object,
    academicTerms: Array,
    programs: Array,
    specializations: Array,
    sections: Array,
    statuses: Array,
    filters: Object,
    can: Object,
})

const form = reactive({
    academic_term_id: props.filters.academic_term_id ?? '',
    program_id: props.filters.program_id ?? '',
    specialization_id: props.filters.specialization_id ?? '',
    year_level: props.filters.year_level ?? '',
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

watch(
    () => [form.academic_term_id, form.program_id, form.specialization_id, form.year_level, form.section_id, form.status],
    applyFilters
)

// Specializations that belong to the selected Program (e.g. BSCRIM's
// FB/LD/QD/FI). Empty for single-track programs like BSIT, which is
// what hides the Specialization filter for them below.
const specializationsForProgram = computed(() => {
    if (! form.program_id) return []

    return props.specializations.filter(s => s.program_id === form.program_id)
})

// Sections narrowed to the selected Program and (if applicable)
// Specialization — this is what actually fixes "picked BSIT, still
// see every Section." Each Section carries program_id/specialization_id
// denormalized off its Curriculum (see SubjectOfferingController::index()).
const filteredSections = computed(() => {
    return props.sections.filter(section => {
        if (form.program_id && section.program_id !== form.program_id) return false

        if (form.specialization_id && section.specialization_id !== form.specialization_id) return false

        if (form.year_level && section.year_level !== form.year_level) return false

        return true
    })
})

// Changing Program invalidates any previously chosen Specialization/
// Section that no longer applies — otherwise a stale section_id from
// e.g. BSIT could keep silently filtering a BSCRIM query into an empty
// table with no visible reason why.
watch(() => form.program_id, () => {
    form.specialization_id = ''
    form.section_id = ''
})

watch(() => form.specialization_id, () => {
    form.section_id = ''
})

const activeTermLabel = computed(() => {
    const term = props.academicTerms.find(t => t.id === form.academic_term_id)
    return term ? term.display_name : null
})

function statusBadgeClass(status) {
    return {
        Draft: 'bg-gray-100 text-gray-700 border-gray-200',
        Generated: 'bg-sky-100 text-sky-800 border-sky-200',
        'Faculty Assigned': 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'Room Assigned': 'bg-cyan-100 text-cyan-800 border-cyan-200',
        'Ready for Scheduling': 'bg-violet-100 text-violet-800 border-violet-200',
        Scheduled: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        Completed: 'bg-teal-100 text-teal-800 border-teal-200',
        Archived: 'bg-rose-100 text-rose-800 border-rose-200',
    }[status] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}

// Overall Status is fully derived — this tooltip is just a reminder of
// *why*, since there's no dropdown here to click through anymore.
function statusHint(status) {
    return {
        Generated: 'No Faculty or Room assigned yet.',
        'Faculty Assigned': 'Faculty is assigned; Room is not.',
        'Room Assigned': 'Room is assigned; Faculty is not.',
        'Ready for Scheduling': 'Faculty and Room are both assigned.',
        Scheduled: 'A day/time has been assigned by the Scheduler.',
        Completed: "The Academic Term's class end date has passed.",
        Archived: 'The Academic Term has been Archived.',
    }[status] ?? ''
}

function assignmentBadgeClass(value) {
    return value === 'Assigned'
        ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
        : 'bg-amber-100 text-amber-800 border-amber-200'
}

function destroy(offering) {
    if (! confirm(`Delete Subject Offering ${offering.edp_code}?`)) return

    router.delete(route('subject-offerings.destroy', offering.id), { preserveScroll: true })
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
                        Classes imported from a Curriculum into an Academic Term. No
                        Faculty, Room, or schedule is assigned here.
                        <span v-if="activeTermLabel"> Showing: <strong>{{ activeTermLabel }}</strong></span>
                    </p>
                </div>

                <Link
                    v-if="can.generate"
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
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
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

                    <!-- Only shows once a Program with multiple tracks (e.g.
                         BSCRIM's FB/LD/QD/FI) is selected — single-track
                         Programs like BSIT never trigger this. -->
                    <div v-if="specializationsForProgram.length > 0">
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Specialization
                        </label>
                        <select
                            v-model="form.specialization_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All Specializations</option>
                            <option v-for="spec in specializationsForProgram" :key="spec.id" :value="spec.id">
                                {{ spec.code ?? spec.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide" style="color: var(--text-muted)">
                            Year Level
                        </label>
                        <select
                            v-model="form.year_level"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            style="border-color: var(--card-border); background: var(--page-bg); color: var(--text-primary)"
                        >
                            <option value="">All Years</option>
                            <option v-for="y in [1, 2, 3, 4]" :key="y" :value="y">Year {{ y }}</option>
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
                            <option v-for="section in filteredSections" :key="section.id" :value="section.id">
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
                            <option v-for="status in statuses" :key="status" :value="status">
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
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Program</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Year</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Section</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Subject</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Units</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Hours</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Classification</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Faculty</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Room</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)">Overall Status</th>
                            <th class="px-4 py-3 font-semibold" style="color: var(--text-secondary)"></th>
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
                                {{ offering.program?.code }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.year_level }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.section?.section_code }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                <div class="font-medium">{{ offering.subject?.subject_code }}</div>
                                <div class="text-xs" style="color: var(--text-muted)">{{ offering.subject?.descriptive_title }}</div>
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.units ?? '—' }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.hours ?? '—' }}
                            </td>
                            <td class="px-4 py-3" style="color: var(--text-primary)">
                                {{ offering.classification ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    :class="assignmentBadgeClass(offering.faculty_status)"
                                >
                                    {{ offering.faculty_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    :class="assignmentBadgeClass(offering.room_status)"
                                >
                                    {{ offering.room_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                    :class="statusBadgeClass(offering.overall_status)"
                                    :title="statusHint(offering.overall_status)"
                                >
                                    {{ offering.overall_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="can.delete"
                                    @click="destroy(offering)"
                                    class="text-xs font-semibold underline"
                                    style="color: var(--text-muted)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <tr v-if="offerings.data.length === 0">
                            <td colspan="12" class="px-4 py-10 text-center" style="color: var(--text-muted)">
                                No Subject Offerings found. Try adjusting your filters<template v-if="can.generate">, or
                                <Link :href="route('subject-offerings.create')" class="underline">generate offerings</Link>
                                for a Curriculum</template>.
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