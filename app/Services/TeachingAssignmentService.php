<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\SubjectOffering;
use App\Models\TeachingAssignment;
use Illuminate\Validation\ValidationException;

/**
 * Faculty Loading business rules.
 *
 * Eligibility to teach a Subject Offering is decided ONLY by:
 *   - Faculty Scope (general / departmental / cross_department)
 *   - Department match between the faculty and the offering's program
 *   - Subject Category (Major vs Minor)
 *   - The faculty's remaining unit capacity
 *
 * The old Faculty Subject "qualification" whitelist has been removed
 * entirely — there is no per-faculty subject list to check anymore.
 *
 * Each rule throws a ValidationException on failure instead of
 * abort_if()/abort_unless(). That distinction matters with Inertia: a
 * plain abort() produces a raw HTTP exception response, which Inertia
 * can't render as an inline form error — the user gets dumped onto
 * Laravel's full error page instead of a friendly message.
 * ValidationException is what Laravel's exception handler (and
 * Inertia's client-side handling) expects for a 422, so it comes back
 * as a normal validation error the page can display.
 */
class TeachingAssignmentService
{
    /**
     * Run every cross-model business rule for a Faculty Loading
     * assignment. $teachingAssignment is accepted for reuse/testability
     * even though the current UI only ever creates assignments (there
     * is no "edit an existing assignment" flow — you remove and
     * reassign instead).
     */
    public function assertBusinessRules(array $validated, ?TeachingAssignment $teachingAssignment = null): void
    {
        $offering = SubjectOffering::with(['subject', 'curriculum.program', 'academicTerm'])
            ->findOrFail($validated['subject_offering_id']);

        $faculty = Faculty::findOrFail($validated['faculty_id']);

        $this->assertAcademicTermIsActive($offering);
        $this->assertFacultyIsActive($faculty);
        $this->assertFacultyScopeAllowsSubject($faculty, $offering);
        $this->assertWithinMaxUnits($validated, $faculty, $offering, $teachingAssignment);
    }

    /**
     * A faculty member can't be assigned to an Offering that belongs to
     * an academic term which is no longer the active one — Faculty
     * Loading only ever operates on the currently active term.
     */
    private function assertAcademicTermIsActive(SubjectOffering $offering): void
    {
        if (! $offering->academicTerm || ! $offering->academicTerm->active) {
            throw ValidationException::withMessages([
                'subject_offering_id' => 'This Subject Offering belongs to an academic term that is not currently active.',
            ]);
        }
    }

    /**
     * Inactive faculty can never receive a new load — they've been
     * marked unavailable (on leave, off-boarded, etc.) and the
     * scheduler should not be able to hand them a subject.
     */
    private function assertFacultyIsActive(Faculty $faculty): void
    {
        if (! $faculty->status) {
            throw ValidationException::withMessages([
                'faculty_id' => 'This faculty member is inactive and cannot be assigned a load.',
            ]);
        }
    }

    /**
     * Faculty Scope Rules
     * --------------------------------------------------------------
     * Departmental       -> Major subjects only, and only within the
     *                       faculty's own department. Minor subjects
     *                       are never assigned to Departmental faculty,
     *                       even ones in their own department.
     * Cross Department   -> Major subjects must belong to the
     *                       faculty's own department; Minor subjects
     *                       are allowed from any department.
     * General Education  -> Minor subjects only, from any department
     *                       (General Education faculty carry no
     *                       department of their own).
     *
     * "Own department" is resolved through the offering's curriculum's
     * program, since a Subject itself is program-agnostic.
     */
    private function assertFacultyScopeAllowsSubject(Faculty $faculty, SubjectOffering $offering): void
    {
        $subject = $offering->subject;

        if (! $subject) {
            return;
        }

        $isMajor = (bool) $subject->is_major;
        $offeringDepartmentId = $offering->curriculum?->program?->department_id;

        $message = match ($faculty->faculty_scope) {
            'general' => $isMajor
                ? 'General Education faculty can only be assigned Minor subjects.'
                : null,
            'departmental' => (! $isMajor || $offeringDepartmentId !== $faculty->department_id)
                ? 'Departmental faculty can only be assigned Major subjects within their own department.'
                : null,
            'cross_department' => ($isMajor && $offeringDepartmentId !== $faculty->department_id)
                ? 'Cross Department faculty can only be assigned Major subjects within their own department.'
                : null,
            default => null,
        };

        if ($message !== null) {
            throw ValidationException::withMessages([
                'faculty_id' => $message,
            ]);
        }
    }

    /**
     * A faculty member's total active load for the offering's academic
     * term (sum of units across all their active assignments) may
     * never exceed their max_units. The unit being added is the
     * offering's subject's units. Assignments being created/kept
     * inactive (active = false) don't count toward load.
     */
    private function assertWithinMaxUnits(array $validated, Faculty $faculty, SubjectOffering $offering, ?TeachingAssignment $teachingAssignment): void
    {
        $isActive = $validated['active'] ?? true;

        if (! $isActive) {
            return;
        }

        if (! $offering->subject) {
            return;
        }

        $currentLoad = TeachingAssignment::with('subjectOffering.subject')
            ->where('faculty_id', $faculty->id)
            ->where('active', true)
            ->whereHas('subjectOffering', fn ($query) => $query->where('academic_term_id', $offering->academic_term_id))
            ->when($teachingAssignment, fn ($query) => $query->where('id', '!=', $teachingAssignment->id))
            ->get()
            ->sum(fn ($assignment) => $assignment->subjectOffering?->subject?->units ?? 0);

        $incomingUnits = $offering->subject->units ?? 0;
        $projectedLoad = $currentLoad + $incomingUnits;

        if ($projectedLoad > $faculty->max_units) {
            throw ValidationException::withMessages([
                'faculty_id' => "This assignment would push {$faculty->full_name}'s load to {$projectedLoad} units, exceeding their maximum of {$faculty->max_units} units.",
            ]);
        }
    }
}