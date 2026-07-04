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

function surnameOf(faculty) {
    const parts = faculty.full_name.trim().split(/\s+/);
    return parts[parts.length - 1] ?? '';
}

const filteredFaculties = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.faculties
        .filter((faculty) => {
            if (departmentFilter.value && String(faculty.department_id) !== departmentFilter.value) return false;
            if (scopeFilter.value && faculty.faculty_scope !== scopeFilter.value) return false;
            if (employmentFilter.value && faculty.employment_type !== employmentFilter.value) return false;

            if (!term) return true;

            const haystack = [faculty.full_name, employeeId(faculty), faculty.department?.name]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return haystack.includes(term);
        })
        .sort((a, b) => surnameOf(a).localeCompare(surnameOf(b), undefined, { sensitivity: 'base' }));
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
| ever handed down from the controller.
*/

function assignmentsFor(facultyId) {
    return props.teachingAssignments.filter((a) => a.faculty_id === facultyId);
}

function activeAssignmentsFor(facultyId) {
    return assignmentsFor(facultyId).filter((a) => a.active);
}

function unitsOf(assignment) {
    return assignment.subject_offering?.subject?.units ?? 0;
}

function isMajorAssignment(assignment) {
    return !!assignment.subject_offering?.subject?.is_major;
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
| Faculty Scope Eligibility (mirrors TeachingAssignmentController's
| assertFacultyScopeAllowsSubject / assertWithinMaxUnits / assertFacultyIsActive
| on the server — this is only a client-side preview so the scheduler
| sees friendly reasons before submitting; the server remains the
| authoritative check).
|--------------------------------------------------------------------------
*/

function checkEligibility(faculty, offering) {
    if (!faculty.status) {
        return { ok: false, reason: 'Inactive faculty' };
    }

    const subject = offering.subject;
    const isMajor = !!subject?.is_major;
    const subjectDepartmentId = offering.section?.curriculum?.program?.department_id ?? null;

    if (faculty.faculty_scope === 'general' && isMajor) {
        return { ok: false, reason: 'General Ed: Minor only' };
    }

    if (faculty.faculty_scope === 'departmental' && !isMajor) {
        return { ok: false, reason: 'Departmental: Major only' };
    }

    if (faculty.faculty_scope === 'departmental' && subjectDepartmentId !== faculty.department_id) {
        return { ok: false, reason: 'Outside faculty department' };
    }

    if (faculty.faculty_scope === 'cross_department' && isMajor && subjectDepartmentId !== faculty.department_id) {
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

const assignedOfferingIds = computed(
    () => new Set(props.teachingAssignments.map((a) => a.subject_offering_id))
);

const unassignedOfferings = computed(() =>
    props.subjectOfferings.filter((offering) => !assignedOfferingIds.value.has(offering.id))
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
            onError: (errors) => {
                assignError.value = Object.values(errors)[0] ?? 'Something went wrong while assigning this subject.';
            },
        }
    );
}

function removeAssignment(assignment) {
    const label = assignment.subject_offering?.subject?.descriptive_title ?? 'this subject';

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
            <aside class="flex w-80 flex-shrink-0 flex-col border-r border-[var(--card-border)] bg-[var(--card-bg)]">
                <div class="border-b border-[var(--card-border)] px-4 py-4">
                    <h1 class="text-lg font-bold text-[var(--text-primary)]">Faculty Loading</h1>
                    <p class="mt-0.5 text-xs text-[var(--text-muted)]">
                        <template v-if="activeTerm">{{ activeTerm.display_name }}</template>
                        <template v-else>No active academic term set</template>
                    </p>

                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search faculty..."
                        class="mt-3 w-full rounded-lg border border-[var(--card-border)] bg-[var(--card-bg)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                    />

                    <div class="mt-2 grid grid-cols-1 gap-2">
                        <select
                            v-model="departmentFilter"
                            class="w-full rounded-lg border border-[var(--card-border)] bg-[var(--card-bg)] px-2.5 py-1.5 text-xs text-[var(--text-primary)] focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                        >
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
                                {{ dept.name }}
                            </option>
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <select
                                v-model="scopeFilter"
                                class="w-full rounded-lg border border-[var(--card-border)] bg-[var(--card-bg)] px-2.5 py-1.5 text-xs text-[var(--text-primary)] focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                            >
                                <option value="">All Scopes</option>
                                <option v-for="(label, value) in scopeLabels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>

                            <select
                                v-model="employmentFilter"
                                class="w-full rounded-lg border border-[var(--card-border)] bg-[var(--card-bg)] px-2.5 py-1.5 text-xs text-[var(--text-primary)] focus:border-[#D4A62A] focus:outline-none focus:ring-2 focus:ring-[#D4A62A]/30"
                            >
                                <option value="">All Types</option>
                                <option value="Full-Time">Full-Time</option>
                                <option value="Part-Time">Part-Time</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2 custom-scrollbar-theme">
                    <p v-if="filteredFaculties.length === 0" class="px-2 py-6 text-center text-sm text-[var(--text-muted)]">
                        No faculty match your filters.
                    </p>

                    <button
                        v-for="faculty in filteredFaculties"
                        :key="faculty.id"
                        type="button"
                        class="mb-1.5 flex w-full items-center gap-3 rounded-xl border p-3 text-left transition"
                        :class="
                            selectedFacultyId === faculty.id
                                ? 'border-blue-400/50 bg-blue-500/10'
                                : 'border-transparent hover:bg-[var(--page-bg)]'
                        "
                        @click="selectFaculty(faculty)"
                    >
                        <CircularLoadIndicator :percent="loadPercent(faculty)" :size="40" :stroke-width="4" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-[var(--text-primary)]">
                                {{ faculty.full_name }}
                                <span v-if="!faculty.status" class="ml-1 text-xs font-normal text-red-500">(Inactive)</span>
                            </p>
                            <p class="truncate text-xs text-[var(--text-muted)]">{{ employeeId(faculty) }}</p>
                            <p class="truncate text-xs text-[var(--text-muted)]">
                                {{ faculty.department?.name ?? 'General Education' }} · {{ faculty.employment_type }}
                            </p>
                            <p class="truncate text-xs text-[var(--text-muted)]">{{ scopeLabels[faculty.faculty_scope] }}</p>
                            <p class="mt-0.5 text-xs font-medium text-[var(--text-secondary)]">
                                {{ totalLoad(faculty.id) }} / {{ faculty.max_units }} units
                            </p>
                        </div>
                    </button>
                </div>
            </aside>

            <!-- ==================== RIGHT PANEL: FACULTY WORKSPACE ==================== -->
            <main class="flex-1 overflow-y-auto bg-[var(--page-bg)] px-8 py-8 custom-scrollbar-theme">
                <div v-if="!selectedFaculty" class="flex h-full items-center justify-center text-center">
                    <div>
                        <div class="text-4xl">🧑‍🏫</div>
                        <p class="mt-3 text-sm text-[var(--text-muted)]">
                            Select a faculty member from the roster to view and manage their load.
                        </p>
                    </div>
                </div>

                <template v-else>
                    <!-- Faculty Info Header -->
                    <div class="mb-6 rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold text-[var(--text-primary)]">{{ selectedFaculty.full_name }}</h2>
                                <p class="text-sm text-[var(--text-muted)]">{{ employeeId(selectedFaculty) }}</p>
                                <p class="mt-1 text-sm text-[var(--text-secondary)]">
                                    {{ selectedFaculty.department?.name ?? 'General Education (no department)' }}
                                </p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-indigo-500/10 px-2.5 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ scopeLabels[selectedFaculty.faculty_scope] }}
                                    </span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            selectedFaculty.employment_type === 'Full-Time'
                                                ? 'bg-green-500/10 text-green-600 dark:text-green-400'
                                                : 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                        "
                                    >
                                        {{ selectedFaculty.employment_type }}
                                    </span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="selectedFaculty.status ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-[var(--page-bg)] text-[var(--text-muted)]'"
                                    >
                                        {{ selectedFaculty.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <CircularLoadIndicator :percent="loadPercent(selectedFaculty)" :size="72" :stroke-width="7" />
                                <div class="text-sm">
                                    <p class="text-[var(--text-muted)]">Max Units <span class="font-semibold text-[var(--text-primary)]">{{ selectedFaculty.max_units }}</span></p>
                                    <p class="text-[var(--text-muted)]">Current <span class="font-semibold text-[var(--text-primary)]">{{ totalLoad(selectedFaculty.id) }}</span></p>
                                    <p class="text-[var(--text-muted)]">Remaining <span class="font-semibold text-[var(--text-primary)]">{{ Math.max(selectedFaculty.max_units - totalLoad(selectedFaculty.id), 0) }}</span></p>
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
                                ? 'border-red-500/30 bg-red-500/10 text-red-600 dark:text-red-400'
                                : 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400'
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
                        <div class="rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">Total Load</p>
                            <p class="mt-1 text-xl font-bold text-[var(--text-primary)]">
                                {{ totalLoad(selectedFaculty.id) }} / {{ selectedFaculty.max_units }}
                            </p>
                            <p class="text-xs text-[var(--text-muted)]">Units</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">Major Load</p>
                            <p class="mt-1 text-xl font-bold text-[var(--text-primary)]">{{ majorLoad(selectedFaculty.id) }}</p>
                            <p class="text-xs text-[var(--text-muted)]">Units</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">Minor Load</p>
                            <p class="mt-1 text-xl font-bold text-[var(--text-primary)]">{{ minorLoad(selectedFaculty.id) }}</p>
                            <p class="text-xs text-[var(--text-muted)]">Units</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">Remaining Capacity</p>
                            <p class="mt-1 text-xl font-bold text-[var(--text-primary)]">
                                {{ Math.max(selectedFaculty.max_units - totalLoad(selectedFaculty.id), 0) }}
                            </p>
                            <p class="text-xs text-[var(--text-muted)]">Units</p>
                        </div>
                    </div>

                    <!-- Load Progress Bar -->
                    <div class="mb-8 rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] p-4 shadow-sm">
                        <div class="mb-1.5 flex items-center justify-between text-xs font-medium text-[var(--text-muted)]">
                            <span>Load Progress</span>
                            <span>{{ Math.round(loadPercent(selectedFaculty)) }}%</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-[var(--page-bg)]">
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
                    <div class="rounded-2xl border border-[var(--card-border)] bg-[var(--card-bg)] shadow-sm">
                        <div class="flex items-center justify-between border-b border-[var(--card-border)] px-6 py-4">
                            <h3 class="text-base font-bold text-[var(--text-primary)]">Assigned Subjects</h3>
                            <button
                                type="button"
                                :disabled="!activeTerm || loadPercent(selectedFaculty) >= 100 || !selectedFaculty.status"
                                class="btn-save"
                                @click="openAssignModal"
                            >
                                + Manage Load
                            </button>
                        </div>

                        <p v-if="!activeTerm" class="px-6 py-6 text-sm text-[var(--text-muted)]">
                            No active academic term is set — activate a term before assigning subjects.
                        </p>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[var(--card-border)]">
                                <thead class="bg-[var(--page-bg)]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Offering</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Program</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Year</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Sem</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Units</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Room</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Schedule</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[var(--text-secondary)]">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--card-border)]">
                                    <tr v-if="selectedAssignments.length === 0">
                                        <td colspan="11" class="px-4 py-10 text-center text-sm text-[var(--text-muted)]">
                                            No subjects assigned yet. Use "Assign Subject" to build this faculty member's load.
                                        </td>
                                    </tr>
                                    <tr v-for="assignment in selectedAssignments" :key="assignment.id" class="hover:bg-[var(--page-bg)]">
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ assignment.subject_offering?.edp_code ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ assignment.subject_offering?.subject?.descriptive_title ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ assignment.subject_offering?.section?.section_code }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ assignment.subject_offering?.section?.curriculum?.program?.code ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ assignment.subject_offering?.year_level ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ assignment.subject_offering?.semester ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-primary)]">
                                            {{ unitsOf(assignment) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="isMajorAssignment(assignment) ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400' : 'bg-sky-500/10 text-sky-600 dark:text-sky-400'"
                                            >
                                                {{ isMajorAssignment(assignment) ? 'Major' : 'Minor' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-muted)]">—</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--text-muted)]">Not yet scheduled</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <button
                                                type="button"
                                                class="btn-delete"
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