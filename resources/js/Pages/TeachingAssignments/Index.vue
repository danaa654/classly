<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import AssignSubjectModal from './Partials/AssignSubjectModal.vue';
import CircularLoadIndicator from './Partials/CircularLoadIndicator.vue';

const props = defineProps({
    activeTerm: { type: Object, default: null },
    faculties: { type: Array, required: true },
    departments: { type: Array, required: true },
    teachingAssignments: { type: Array, required: true },
    subjectOfferings: { type: Array, required: true },
});

/*
|--------------------------------------------------------------------------
| Roster filters
|--------------------------------------------------------------------------
*/

const search = ref('');
const departmentFilter = ref('');
const scopeFilter = ref('');
const employmentFilter = ref('');

const scopeLabels = {
    general: 'General Education',
    departmental: 'Departmental',
    cross_department: 'Cross Department',
};

const filteredFaculties = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.faculties.filter((faculty) => {
        if (departmentFilter.value && String(faculty.department_id) !== departmentFilter.value) return false;
        if (scopeFilter.value && faculty.faculty_scope !== scopeFilter.value) return false;
        if (employmentFilter.value && faculty.employment_type !== employmentFilter.value) return false;

        if (!term) return true;

        const haystack = [faculty.full_name, employeeId(faculty), faculty.department?.name]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(term);
    });
});

function employeeId(faculty) {
    return `FAC-${String(faculty.id).padStart(4, '0')}`;
}

/*
|--------------------------------------------------------------------------
| Load calculations
|--------------------------------------------------------------------------
|
| All computed from teachingAssignments (the single source of truth for
| this module) scoped to the active academic term, which is all that's
| ever handed down from the controller. Every fact about what's being
| taught comes from the assignment's subjectOffering — Teaching
| Assignments no longer carry section/curriculum item data of their own.
*/

function assignmentsFor(facultyId) {
    return props.teachingAssignments.filter((a) => a.faculty_id === facultyId);
}

function activeAssignmentsFor(facultyId) {
    return assignmentsFor(facultyId).filter((a) => a.active);
}

function unitsOf(assignment) {
    return assignment.subjectOffering?.subject?.units ?? 0;
}

function isMajorAssignment(assignment) {
    return !!assignment.subjectOffering?.subject?.is_major;
}

function totalLoad(facultyId) {
    return activeAssignmentsFor(facultyId).reduce((sum, a) => sum + unitsOf(a), 0);
}

function majorLoad(facultyId) {
    return activeAssignmentsFor(facultyId)
        .filter(isMajorAssignment)
        .reduce((sum, a) => sum + unitsOf(a), 0);
}

function minorLoad(facultyId) {
    return activeAssignmentsFor(facultyId)
        .filter((a) => !isMajorAssignment(a))
        .reduce((sum, a) => sum + unitsOf(a), 0);
}

function loadPercent(faculty) {
    if (!faculty.max_units) return 0;
    return (totalLoad(faculty.id) / faculty.max_units) * 100;
}

/*
|--------------------------------------------------------------------------
| Selected faculty
|--------------------------------------------------------------------------
*/

const selectedFacultyId = ref(null);

const selectedFaculty = computed(
    () => props.faculties.find((f) => f.id === selectedFacultyId.value) ?? null
);

const selectedAssignments = computed(() =>
    selectedFaculty.value ? assignmentsFor(selectedFaculty.value.id) : []
);

function selectFaculty(faculty) {
    selectedFacultyId.value = faculty.id;
}

/*
|--------------------------------------------------------------------------
| Faculty Scope Eligibility (mirrors TeachingAssignmentService on the
| server — this is only a client-side preview so the scheduler sees
| friendly reasons before submitting; the server remains the
| authoritative check).
|
| Eligibility is decided ONLY by: faculty active status, Faculty Scope,
| Department, Subject Category (Major/Minor), and remaining unit
| capacity. There is no Faculty Subject "qualification" check anymore —
| that system has been removed entirely.
|--------------------------------------------------------------------------
*/

function checkEligibility(faculty, offering) {
    if (!faculty.status) {
        return { ok: false, reason: 'Inactive faculty' };
    }

    const subject = offering.subject;
    const isMajor = !!subject?.is_major;
    const offeringDepartmentId = offering.section?.curriculum?.program?.department_id ?? null;

    if (faculty.faculty_scope === 'general' && isMajor) {
        return { ok: false, reason: 'General Ed: Minor only' };
    }

    if (faculty.faculty_scope === 'departmental' && offeringDepartmentId !== faculty.department_id) {
        return { ok: false, reason: 'Outside faculty department' };
    }

    if (faculty.faculty_scope === 'cross_department' && isMajor && offeringDepartmentId !== faculty.department_id) {
        return { ok: false, reason: 'Major outside department' };
    }

    const incomingUnits = subject?.units ?? 0;
    const projectedLoad = totalLoad(faculty.id) + incomingUnits;

    if (projectedLoad > faculty.max_units) {
        return { ok: false, reason: `Exceeds max units (${projectedLoad}/${faculty.max_units})` };
    }

    return { ok: true, reason: null };
}

/*
|--------------------------------------------------------------------------
| Assign Subject modal
|--------------------------------------------------------------------------
*/

const showAssignModal = ref(false);
const assignError = ref(null);

const unassignedOfferings = computed(() =>
    props.subjectOfferings.filter(
        (offering) => !props.teachingAssignments.some((a) => a.subject_offering_id === offering.id)
    )
);

function openAssignModal() {
    assignError.value = null;
    showAssignModal.value = true;
}

function closeAssignModal() {
    showAssignModal.value = false;
    assignError.value = null;
}

function handleAssign(offering) {
    if (!selectedFaculty.value || !props.activeTerm) return;

    assignError.value = null;

    router.post(
        route('teaching-assignments.store'),
        {
            subject_offering_id: offering.id,
            faculty_id: selectedFaculty.value.id,
            remarks: '',
            active: true,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showAssignModal.value = false;
            },
            // Keep the modal open and show the reason inline (e.g. a
            // rule that only the server could catch) instead of losing
            // the user's place in the offering list.
            onError: (errors) => {
                assignError.value = Object.values(errors)[0] ?? 'Could not assign this subject.';
            },
        }
    );
}

function removeAssignment(assignment) {
    const label = assignment.subjectOffering?.subject?.descriptive_title ?? 'this subject';

    if (!confirm(`Remove ${label} from ${selectedFaculty.value?.full_name}'s load?`)) {
        return;
    }

    router.delete(route('teaching-assignments.destroy', assignment.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Faculty Loading" />

        <div class="flex h-[calc(100vh-4rem)] overflow-hidden">
            <!-- ==================== LEFT PANEL: FACULTY ROSTER ==================== -->
            <aside class="flex w-80 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-4">
                    <h1 class="text-lg font-bold text-gray-900">Faculty Loading</h1>
                    <p class="mt-0.5 text-xs text-gray-500">
                        <template v-if="activeTerm">{{ activeTerm.display_name }}</template>
                        <template v-else>No active academic term set</template>
                    </p>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search faculty..."
                        class="mt-3 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />

                    <div class="mt-2 grid grid-cols-1 gap-2">
                        <select
                            v-model="departmentFilter"
                            class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
                                {{ dept.name }}
                            </option>
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <select
                                v-model="scopeFilter"
                                class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Scopes</option>
                                <option v-for="(label, value) in scopeLabels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>

                            <select
                                v-model="employmentFilter"
                                class="w-full rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Types</option>
                                <option value="Full-Time">Full-Time</option>
                                <option value="Part-Time">Part-Time</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    <p v-if="filteredFaculties.length === 0" class="px-2 py-6 text-center text-sm text-gray-400">
                        No faculty match your filters.
                    </p>

                    <button
                        v-for="faculty in filteredFaculties"
                        :key="faculty.id"
                        type="button"
                        class="mb-1.5 flex w-full items-center gap-3 rounded-xl border p-3 text-left transition"
                        :class="
                            selectedFacultyId === faculty.id
                                ? 'border-indigo-300 bg-indigo-50'
                                : 'border-transparent hover:bg-gray-50'
                        "
                        @click="selectFaculty(faculty)"
                    >
                        <CircularLoadIndicator :percent="loadPercent(faculty)" :size="40" :stroke-width="4" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">
                                {{ faculty.full_name }}
                                <span v-if="!faculty.status" class="ml-1 text-xs font-normal text-red-500">(Inactive)</span>
                            </p>
                            <p class="truncate text-xs text-gray-500">{{ employeeId(faculty) }}</p>
                            <p class="truncate text-xs text-gray-500">
                                {{ faculty.department?.name ?? 'General Education' }} · {{ faculty.employment_type }}
                            </p>
                            <p class="truncate text-xs text-gray-400">{{ scopeLabels[faculty.faculty_scope] }}</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-600">
                                {{ totalLoad(faculty.id) }} / {{ faculty.max_units }} units
                            </p>
                        </div>
                    </button>
                </div>
            </aside>

            <!-- ==================== RIGHT PANEL: FACULTY WORKSPACE ==================== -->
            <main class="flex-1 overflow-y-auto bg-gray-50 px-8 py-8">
                <div v-if="!selectedFaculty" class="flex h-full items-center justify-center text-center">
                    <div>
                        <div class="text-4xl">🧑‍🏫</div>
                        <p class="mt-3 text-sm text-gray-500">
                            Select a faculty member from the roster to view and manage their load.
                        </p>
                    </div>
                </div>

                <template v-else>
                    <!-- Faculty Info Header -->
                    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">{{ selectedFaculty.full_name }}</h2>
                                <p class="text-sm text-gray-500">{{ employeeId(selectedFaculty) }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ selectedFaculty.department?.name ?? 'General Education (no department)' }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                        {{ scopeLabels[selectedFaculty.faculty_scope] }}
                                    </span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            selectedFaculty.employment_type === 'Full-Time'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-amber-50 text-amber-700'
                                        "
                                    >
                                        {{ selectedFaculty.employment_type }}
                                    </span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="selectedFaculty.status ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                    >
                                        {{ selectedFaculty.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <CircularLoadIndicator :percent="loadPercent(selectedFaculty)" :size="72" :stroke-width="7" />
                                <div class="text-sm">
                                    <p class="text-gray-500">Max Units <span class="font-semibold text-gray-900">{{ selectedFaculty.max_units }}</span></p>
                                    <p class="text-gray-500">Current <span class="font-semibold text-gray-900">{{ totalLoad(selectedFaculty.id) }}</span></p>
                                    <p class="text-gray-500">Remaining <span class="font-semibold text-gray-900">{{ Math.max(selectedFaculty.max_units - totalLoad(selectedFaculty.id), 0) }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Load Warning -->
                    <div
                        v-if="loadPercent(selectedFaculty) >= 80"
                        class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium"
                        :class="
                            loadPercent(selectedFaculty) >= 100
                                ? 'border-red-200 bg-red-50 text-red-700'
                                : 'border-amber-200 bg-amber-50 text-amber-700'
                        "
                    >
                        <template v-if="loadPercent(selectedFaculty) >= 100">
                            ⚠ This faculty member has reached or exceeded their maximum load. No further subjects can be assigned.
                        </template>
                        <template v-else>
                            ⚠ This faculty member is at {{ Math.round(loadPercent(selectedFaculty)) }}% of their maximum load.
                        </template>
                    </div>

                    <!-- Load Summary Cards -->
                    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Load</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">
                                {{ totalLoad(selectedFaculty.id) }} / {{ selectedFaculty.max_units }}
                            </p>
                            <p class="text-xs text-gray-400">Units</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Major Load</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ majorLoad(selectedFaculty.id) }}</p>
                            <p class="text-xs text-gray-400">Units</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Minor Load</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ minorLoad(selectedFaculty.id) }}</p>
                            <p class="text-xs text-gray-400">Units</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Remaining Capacity</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">
                                {{ Math.max(selectedFaculty.max_units - totalLoad(selectedFaculty.id), 0) }}
                            </p>
                            <p class="text-xs text-gray-400">Units</p>
                        </div>
                    </div>

                    <!-- Load Progress Bar -->
                    <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="mb-1.5 flex items-center justify-between text-xs font-medium text-gray-500">
                            <span>Load Progress</span>
                            <span>{{ Math.round(loadPercent(selectedFaculty)) }}%</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
                            <div
                                class="h-full rounded-full transition-all duration-300"
                                :class="
                                    loadPercent(selectedFaculty) >= 100
                                        ? 'bg-red-500'
                                        : loadPercent(selectedFaculty) >= 80
                                        ? 'bg-amber-500'
                                        : 'bg-emerald-500'
                                "
                                :style="{ width: `${Math.min(loadPercent(selectedFaculty), 100)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Assigned Subjects -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3 class="text-base font-bold text-gray-900">Assigned Subjects</h3>
                            <button
                                type="button"
                                :disabled="!activeTerm || loadPercent(selectedFaculty) >= 100 || !selectedFaculty.status"
                                class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="openAssignModal"
                            >
                                + Assign Subject
                            </button>
                        </div>

                        <p v-if="!activeTerm" class="px-6 py-6 text-sm text-gray-500">
                            No active academic term is set — activate a term before assigning subjects.
                        </p>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Section</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Program</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Sem</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Units</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Room</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Schedule</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-if="selectedAssignments.length === 0">
                                        <td colspan="11" class="px-4 py-10 text-center text-sm text-gray-400">
                                            No subjects assigned yet. Use "Assign Subject" to build this faculty member's load.
                                        </td>
                                    </tr>
                                    <tr v-for="assignment in selectedAssignments" :key="assignment.id" class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ assignment.subjectOffering?.edp_code ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <span class="text-gray-400">{{ assignment.subjectOffering?.subject?.subject_code }}</span>
                                            {{ assignment.subjectOffering?.subject?.descriptive_title }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ assignment.subjectOffering?.section?.section_code }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ assignment.subjectOffering?.section?.curriculum?.program?.code ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ assignment.subjectOffering?.year_level ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ assignment.subjectOffering?.semester ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                            {{ unitsOf(assignment) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="isMajorAssignment(assignment) ? 'bg-purple-50 text-purple-700' : 'bg-sky-50 text-sky-700'"
                                            >
                                                {{ isMajorAssignment(assignment) ? 'Major' : 'Minor' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">—</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">Not yet scheduled</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <button
                                                type="button"
                                                class="inline-block rounded-lg bg-red-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-400"
                                                @click="removeAssignment(assignment)"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </main>
        </div>

        <!-- ==================== ASSIGN SUBJECT MODAL ==================== -->
        <AssignSubjectModal
            v-if="showAssignModal && selectedFaculty"
            :faculty="selectedFaculty"
            :offerings="unassignedOfferings"
            :current-load="totalLoad(selectedFaculty.id)"
            :check-eligibility="checkEligibility"
            :error="assignError"
            @close="closeAssignModal"
            @assign="handleAssign"
        />
    </AppLayout>
</template>