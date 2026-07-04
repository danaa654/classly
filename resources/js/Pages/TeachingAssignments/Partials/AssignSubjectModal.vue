<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    faculty: { type: Object, required: true },
    offerings: { type: Array, required: true }, // already filtered to "unassigned, active term"
    currentLoad: { type: Number, required: true },
    checkEligibility: { type: Function, required: true }, // (faculty, offering) => { ok, reason }
});

const emit = defineEmits(['close', 'assign']);

const search = ref('');
const programFilter = ref('');

const programs = computed(() => {
    const set = new Set(
        props.offerings
            .map((o) => o.section?.curriculum?.program?.code)
            .filter(Boolean)
    );
    return Array.from(set).sort();
});

const filteredOfferings = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.offerings.filter((o) => {
        const matchesProgram = !programFilter.value || o.section?.curriculum?.program?.code === programFilter.value;

        if (!matchesProgram) return false;
        if (!term) return true;

        const haystack = [
            o.edp_code,
            o.subject?.subject_code,
            o.subject?.descriptive_title,
            o.section?.section_code,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(term);
    });
});

const rows = computed(() =>
    filteredOfferings.value.map((offering) => ({
        offering,
        eligibility: props.checkEligibility(props.faculty, offering),
    }))
);
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
        <div class="flex max-h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Assign Subject</h2>
                    <p class="text-sm text-gray-500">
                        Unassigned offerings for {{ faculty.full_name }} —
                        current load {{ currentLoad }} / {{ faculty.max_units }} units
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 border-b border-gray-100 px-6 py-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search code, subject, section..."
                    class="min-w-[220px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <select
                    v-model="programFilter"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Programs</option>
                    <option v-for="code in programs" :key="code" :value="code">{{ code }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="flex-1 overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Code</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Subject</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Program</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Year</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Sem</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Units</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Type</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Section</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400">
                                No unassigned offerings match your filters.
                            </td>
                        </tr>
                        <tr v-for="row in rows" :key="row.offering.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm font-medium text-gray-700">
                                {{ row.offering.edp_code }}
                            </td>
                            <td class="px-4 py-2.5 text-sm text-gray-700">
                                <span class="text-gray-400">{{ row.offering.subject?.subject_code }}</span>
                                {{ row.offering.subject?.descriptive_title }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-700">
                                {{ row.offering.section?.curriculum?.program?.code ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-700">{{ row.offering.year_level }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-700">{{ row.offering.semester }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-700">{{ row.offering.subject?.units ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="row.offering.subject?.is_major ? 'bg-purple-50 text-purple-700' : 'bg-sky-50 text-sky-700'"
                                >
                                    {{ row.offering.subject?.is_major ? 'Major' : 'Minor' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-700">{{ row.offering.section?.section_code }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                <button
                                    v-if="row.eligibility.ok"
                                    type="button"
                                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-500"
                                    @click="emit('assign', row.offering)"
                                >
                                    Assign
                                </button>
                                <span
                                    v-else
                                    class="inline-block max-w-[180px] text-right text-xs font-medium text-red-500"
                                    :title="row.eligibility.reason"
                                >
                                    {{ row.eligibility.reason }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>