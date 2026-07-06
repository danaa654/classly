<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SubjectOffering;
use App\Services\RoomCapacityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only data aggregator for the Master Grid Scheduling Workspace.
 *
 * IMPORTANT: This service does NOT generate, save, or mutate any
 * schedule. It only assembles what already exists (Academic Term,
 * Rooms, Subject Offerings, Faculty Loading, Programs) into the shape
 * the Vue workspace needs to render itself. The Greedy Scheduling
 * Algorithm itself lives in GreedyScheduleService — see
 * MasterGridController::generate() for the preview endpoint that
 * calls it.
 */
class MasterGridDataService
{
    public function __construct(
        private readonly RoomCapacityService $capacity
    ) {
    }

    /**
     * Everything the Master Grid page needs, keyed for direct use as
     * Inertia props.
     *
     * $term is whatever MasterGridController resolved via
     * SchedulingWorkspaceService::getTermForUser() — the Planning
     * Academic Term for Admin/Registrar, or the Active Academic Term
     * for Dean/Assistant Dean/OIC. This method itself has no opinion
     * on which term that should be; it only assembles data for
     * whichever term it's handed. The 'activeTerm' prop key is kept
     * as-is (rather than renamed) so the existing Vue workspace
     * doesn't need to change — it's simply "the term this workspace
     * is currently showing," not necessarily the literal Active term.
     *
     * $departmentId scopes Subject Offerings (and their Scheduled/
     * Completed/Archived counterparts) to a single department — null
     * for Admin/Registrar/Assistant Dean, who see every department;
     * the Dean/OIC's own department_id otherwise. Mirrors the exact
     * same "own department + General Education" rule
     * TeachingAssignmentController::index() already applies to
     * Faculty Loading — Master Grid had no such scoping at all before
     * this, so a CCS OIC could see every other college's Subject
     * Offerings in the sidebar. Rooms are intentionally left
     * unscoped: a Dean/OIC still needs to see every physical room on
     * campus (including shared/General ones) to actually schedule
     * into, the same way the Room Sidebar's "Allowed" column already
     * shows non-CCS programs on shared rooms without that being a
     * data leak — it's the room's own allowance list, not another
     * college's private data.
     */
    public function build(?AcademicTerm $term, ?int $departmentId = null): array
    {
        $activeTerm = $term;

        if (! $activeTerm) {
            return [
                'activeTerm' => null,
                'subjectOfferings' => [],
                'scheduledOfferings' => [],
                'rooms' => [],
                'departments' => [],
                'programs' => [],
                'specializations' => [],
                'faculties' => [],
                'savedSchedules' => [],
                'collegeColors' => $this->collegeColorMap(),
            ];
        }

        $programDepartmentMap = $this->programDepartmentMap();
        $preferredRoomByOffering = $this->preferredRoomByOffering($activeTerm->id);
        $preferredFacultyByOffering = $this->preferredFacultyByOffering($activeTerm->id);

        return [

            'activeTerm' => $activeTerm,

            // Unscheduled Subject Offerings for the active term only —
            // "unscheduled" here means overall_status hasn't reached
            // Scheduled/Completed/Archived yet (see
            // SubjectOffering::getOverallStatusAttribute()). Once the
            // Greedy Scheduler's future Save step writes real schedule
            // rows, those offerings naturally drop out of this list on
            // their own, no extra flag needed.
            'subjectOfferings' => $this->unscheduledOfferings($activeTerm->id, $departmentId)
                ->map(fn (SubjectOffering $offering) => $this->presentOffering(
                    $offering,
                    $programDepartmentMap,
                    $preferredRoomByOffering,
                    $preferredFacultyByOffering
                ))
                ->values(),

            // The flip side of subjectOfferings — every offering that
            // WAS excluded above for being Scheduled/Completed/Archived.
            // Not shown by default: the Subject Sidebar is a "drag-in"
            // tray of work still to be placed, and a Scheduled offering
            // has nothing left to drag. This exists purely so the
            // sidebar's optional "Show scheduled too" toggle has
            // something to reveal — e.g. after placing BSIT-4A, a
            // Registrar can flip the toggle to confirm it really is
            // done rather than having to jump over to Faculty Loading
            // to check. Shares presentOffering() so the card shape
            // (and therefore faculty_assigned, preferred_room_code,
            // etc.) is identical either way.
            'scheduledOfferings' => $this->scheduledOfferings($activeTerm->id, $departmentId)
                ->map(fn (SubjectOffering $offering) => $this->presentOffering(
                    $offering,
                    $programDepartmentMap,
                    $preferredRoomByOffering,
                    $preferredFacultyByOffering
                ))
                ->values(),

            'rooms' => Room::schedulable()
                ->with('roomGroups')
                ->orderBy('room_code')
                ->get()
                ->map(fn (Room $room) => $this->presentRoom($room, $activeTerm, $programDepartmentMap))
                ->values(),

            'departments' => Department::where('active', true)->orderBy('name')->get(['id', 'name', 'abbreviation']),

            'programs' => Program::with('department')
                ->where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Program $program) => [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'department_id' => $program->department_id,
                ])
                ->values(),

            'specializations' => Schema::hasTable('specializations')
                ? DB::table('specializations')
                    ->where('active', true)
                    ->orderBy('name')
                    ->get(['id', 'program_id', 'code', 'name'])
                : [],

            // For the Edit Schedule modal's Faculty picker (Phase 2 —
            // Interactive Schedule Review). Kept intentionally light —
            // just what the modal and the recommendation service need
            // to display/sort candidates; TeachingAssignmentService and
            // ScheduleRecommendationService remain the source of truth
            // for actual eligibility rules.
            'faculties' => Faculty::where('status', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'department_id', 'faculty_scope', 'max_units']),

            // Already-saved Schedule rows for the active term, if any —
            // lets the Master Grid render committed schedules (from a
            // previous Save Schedule run) even before a new Generate
            // is run in this session.
            'savedSchedules' => Schema::hasTable('schedules')
                ? Schedule::with(['subjectOffering.subject', 'subjectOffering.section', 'subjectOffering.program', 'faculty', 'room'])
                    ->forTerm($activeTerm->id)
                    ->get()
                    ->map(fn (Schedule $s) => [
                        'subject_offering_id' => $s->subject_offering_id,
                        'subject_code' => $s->subjectOffering?->subject?->subject_code,
                        'descriptive_title' => $s->subjectOffering?->subject?->descriptive_title,
                        'section_code' => $s->subjectOffering?->section?->section_code,
                        'section_id' => $s->subjectOffering?->section_id,
                        'year_level' => $s->subjectOffering?->year_level,
                        'program_code' => $s->subjectOffering?->program?->code,
                        'units' => $s->subjectOffering?->units,
                        'room_type' => $s->subjectOffering?->room_type,
                        'classification' => $s->subjectOffering?->classification,
                        'faculty_id' => $s->faculty_id,
                        'faculty_name' => $s->faculty?->full_name,
                        'room_id' => $s->room_id,
                        'room_code' => $s->room?->room_code,
                        'day' => $s->day,
                        'start_minutes' => $s->start_minutes,
                        'end_minutes' => $s->end_minutes,
                        'college_code' => $programDepartmentMap[$s->subjectOffering?->program?->code] ?? 'General',
                        'status' => 'saved',
                    ])
                    ->values()
                : [],

            // Centralized color-mapping source of truth — the frontend
            // (SubjectSidebar, RoomSidebar, Timetable events) all read
            // from this single object rather than each picking their
            // own colors. See resources/js/Utils/collegeColors.js for
            // the client-side mirror of this same map.
            'collegeColors' => $this->collegeColorMap(),

        ];
    }

    /**
     * Subject Offerings for the active term, excluding anything already
     * Scheduled/Completed/Archived. Eager-loads everything the card and
     * the college color-mapping need in one shot to avoid N+1 queries.
     *
     * $departmentId scopes to a single department (plus General
     * Education, whose Subject Offerings carry a program with no
     * department at all) when given — see build()'s doc comment for
     * why this exists and why Rooms don't get the same treatment.
     */
    private function unscheduledOfferings(int $academicTermId, ?int $departmentId = null)
    {
        $excluded = [
            SubjectOffering::STATUS_SCHEDULED,
            SubjectOffering::STATUS_COMPLETED,
            SubjectOffering::STATUS_ARCHIVED,
        ];

        return SubjectOffering::with([
                'subject',
                'section',
                'program.department',
                'academicTerm',
                'teachingAssignment.faculty',
            ])
            ->forTerm($academicTermId)
            ->when($departmentId, fn ($query) => $query->whereHas(
                'program',
                fn ($inner) => $inner->whereNull('department_id')->orWhere('department_id', $departmentId)
            ))
            ->get()
            ->reject(fn (SubjectOffering $offering) => in_array($offering->overall_status, $excluded, true));
    }

    /**
     * The mirror image of unscheduledOfferings() — every offering for
     * this term whose overall_status IS Scheduled/Completed/Archived.
     * Same eager-loads and same department scope, since presentOffering()
     * needs the same fields either way.
     */
    private function scheduledOfferings(int $academicTermId, ?int $departmentId = null)
    {
        $included = [
            SubjectOffering::STATUS_SCHEDULED,
            SubjectOffering::STATUS_COMPLETED,
            SubjectOffering::STATUS_ARCHIVED,
        ];

        return SubjectOffering::with([
                'subject',
                'section',
                'program.department',
                'academicTerm',
                'teachingAssignment.faculty',
            ])
            ->forTerm($academicTermId)
            ->when($departmentId, fn ($query) => $query->whereHas(
                'program',
                fn ($inner) => $inner->whereNull('department_id')->orWhere('department_id', $departmentId)
            ))
            ->get()
            ->filter(fn (SubjectOffering $offering) => in_array($offering->overall_status, $included, true));
    }

    /**
     * Shapes a single Subject Offering into exactly the fields the
     * Subject Card needs (per the Master Grid spec): code, title,
     * program, year, section, hours, faculty assigned, preferred room,
     * preferred faculty, classification, room type.
     *
     * program_id / department_id / section_id are included alongside
     * their display codes (program_code, section_code) so the Generate
     * Schedule modal can filter its Department -> Program -> Year ->
     * Section cascade client-side from this same array, and so the
     * payload it POSTs to /master-grid/generate carries real IDs
     * instead of re-deriving them from display strings.
     */
    private function presentOffering(
        SubjectOffering $offering,
        array $programDepartmentMap,
        array $preferredRoomByOffering,
        array $preferredFacultyByOffering
    ): array {
        $collegeCode = $offering->program?->department?->abbreviation
            ?? $programDepartmentMap[$offering->program?->code] ?? null
            ?? 'General';

        return [
            'id' => $offering->id,
            'edp_code' => $offering->edp_code,
            'subject_code' => $offering->subject?->subject_code,
            'descriptive_title' => $offering->subject?->descriptive_title,
            'program_id' => $offering->program_id,
            'program_code' => $offering->program?->code,
            'department_id' => $offering->program?->department_id,
            'year_level' => $offering->year_level,
            'section_id' => $offering->section_id,
            'section_code' => $offering->section?->section_code,
            'hours' => $offering->hours,
            'units' => $offering->units,
            'classification' => $offering->classification,
            'room_type' => $offering->room_type,
            'faculty_assigned' => $offering->teachingAssignment?->faculty?->full_name,
            'preferred_room_code' => $preferredRoomByOffering[$offering->id] ?? null,
            'preferred_faculty_name' => $preferredFacultyByOffering[$offering->id] ?? null,
            'overall_status' => $offering->overall_status,
            'is_scheduled' => in_array($offering->overall_status, [
                SubjectOffering::STATUS_SCHEDULED,
                SubjectOffering::STATUS_COMPLETED,
                SubjectOffering::STATUS_ARCHIVED,
            ], true),
            'college_code' => $collegeCode,
        ];
    }

    /**
     * Shapes a single Room into exactly the fields the Room Card needs:
     * name, building, floor, capacity, room type, programs allowed,
     * how many classes are ACTUALLY scheduled there, hours used, hours
     * remaining.
     *
     * IMPORTANT: Hours Used/Remaining/Scheduled Count are read from the
     * real `schedules` table (room_id + academic_term_id), i.e. what
     * the Greedy Scheduler + Save Schedule has actually committed — NOT
     * from Room::preferredSubjectOfferings(), which is only a
     * pre-scheduling preference (Manage Subjects) with no day/time at
     * all. Those are two different concepts: a preference says "this
     * room WOULD like to host this offering"; a schedule row says
     * "this offering IS meeting in this room, on this day, at this
     * time." The Master Grid workspace — including this Room
     * Sidebar — is about the latter, so it must always read the
     * latter. Falls back to 0 when the schedules table doesn't exist
     * yet (fresh install) rather than erroring.
     */
    private function presentRoom(Room $room, AcademicTerm $academicTerm, array $programDepartmentMap): array
    {
        $scheduledRows = Schema::hasTable('schedules')
            ? Schedule::where('room_id', $room->id)
                ->where('academic_term_id', $academicTerm->id)
                ->get(['start_minutes', 'end_minutes'])
            : collect();

        $scheduledMinutes = $scheduledRows->sum(
            fn ($row) => max(0, (int) $row->end_minutes - (int) $row->start_minutes)
        );

        $hoursUsed = (int) round($scheduledMinutes / 60);

        // Was a flat Room::WEEKLY_CAPACITY_HOURS (always 60) regardless
        // of which term was actually being viewed — Rooms/Index and the
        // Manage Subjects modal had already moved to the real,
        // term-derived number (School Hours minus Lunch Break, times
        // Working Days — see AcademicTerm::getWeeklyCapacityHoursAttribute()
        // and RoomCapacityService), so Master Grid's Room Sidebar was
        // quietly showing a different, wrong ceiling for every term
        // whose actual school hours didn't happen to add up to 60. This
        // is the same call RoomController now makes, so all three
        // surfaces agree on one number per term.
        $capacity = $this->capacity->weeklyCapacityHoursFor($academicTerm);

        return [
            'id' => $room->id,
            'room_code' => $room->room_code,
            'building' => $room->building,
            'floor' => $room->floor,
            'capacity' => $room->capacity,
            'room_type' => $room->room_type,
            'room_group_codes' => $room->room_group_codes,
            'scheduled_count' => $scheduledRows->count(),
            'hours_used' => $hoursUsed,
            'hours_remaining' => max(0, $capacity - $hoursUsed),
            'utilization_percent' => $capacity > 0 ? min(100, (int) round(($hoursUsed / $capacity) * 100)) : 0,
            'weekly_capacity_hours' => $capacity,
            'college_code' => $this->resolveCollegeForCodes($room->room_group_codes, $programDepartmentMap),
        ];
    }

    /**
     * program code (e.g. "BSIT") => owning department abbreviation
     * (e.g. "CCS"). Built once per request and threaded through both
     * Offering and Room presentation so college color-coding always
     * comes from one source.
     */
    private function programDepartmentMap(): array
    {
        return Program::with('department')
            ->get(['id', 'code', 'department_id'])
            ->filter(fn (Program $program) => $program->department)
            ->mapWithKeys(fn (Program $program) => [$program->code => $program->department->abbreviation])
            ->all();
    }

    /**
     * A room's room_group_codes are program codes (or the literal
     * "General"). Resolve them to a single college color bucket:
     *   - no codes, or only "General"          => "General" (gray)
     *   - every resolvable code is one college  => that college
     *   - codes span more than one college      => "Shared" (gray)
     */
    private function resolveCollegeForCodes(array $codes, array $programDepartmentMap): string
    {
        $colleges = collect($codes)
            ->reject(fn ($code) => $code === 'General')
            ->map(fn ($code) => $programDepartmentMap[$code] ?? null)
            ->filter()
            ->unique();

        if ($colleges->isEmpty()) {
            return 'General';
        }

        return $colleges->count() === 1 ? $colleges->first() : 'Shared';
    }

    /**
     * subject_offering_id => first preferred room's room_code, sourced
     * from the room_subject_offering pivot (Room::preferredSubjectOfferings()).
     * A room preference is a many-to-many, but the Subject Card only
     * has room for one "Preferred Room" label — first match wins.
     */
    private function preferredRoomByOffering(int $academicTermId): array
    {
        if (! Schema::hasTable('room_subject_offering')) {
            return [];
        }

        return DB::table('room_subject_offering')
            ->join('rooms', 'rooms.id', '=', 'room_subject_offering.room_id')
            ->join('subject_offerings', 'subject_offerings.id', '=', 'room_subject_offering.subject_offering_id')
            ->where('subject_offerings.academic_term_id', $academicTermId)
            ->orderBy('room_subject_offering.id')
            ->get(['room_subject_offering.subject_offering_id', 'rooms.room_code'])
            ->groupBy('subject_offering_id')
            ->map(fn ($rows) => $rows->first()->room_code)
            ->all();
    }

    /**
     * subject_offering_id => preferred faculty full name, IF a
     * faculty-preference table exists. There is no such table in the
     * schema today — Teaching Assignments (Faculty Loading) is the
     * only place a Faculty is actually attached to an Offering, and
     * that's already surfaced separately as "Faculty Assigned".
     *
     * This is a forward-compatible placeholder, mirroring the same
     * defensive Schema::hasTable() pattern SubjectOffering already
     * uses for room_status/overall_status: if a future
     * "faculty_subject_offering" preference table ships with the same
     * shape as room_subject_offering, this starts populating
     * automatically with no other change needed. Until then it always
     * returns an empty map, and the frontend simply shows "—".
     */
    private function preferredFacultyByOffering(int $academicTermId): array
    {
        if (! Schema::hasTable('faculty_subject_offering')) {
            return [];
        }

        return DB::table('faculty_subject_offering')
            ->join('faculties', 'faculties.id', '=', 'faculty_subject_offering.faculty_id')
            ->join('subject_offerings', 'subject_offerings.id', '=', 'faculty_subject_offering.subject_offering_id')
            ->where('subject_offerings.academic_term_id', $academicTermId)
            ->orderBy('faculty_subject_offering.id')
            ->get([
                'faculty_subject_offering.subject_offering_id',
                DB::raw("TRIM(CONCAT(faculties.first_name, ' ', faculties.last_name)) as full_name"),
            ])
            ->groupBy('subject_offering_id')
            ->map(fn ($rows) => $rows->first()->full_name)
            ->all();
    }

    /**
     * Single source of truth for College => color. Do NOT scatter
     * per-component color choices — every Vue component that needs a
     * college color reads from this same map (mirrored client-side in
     * resources/js/Utils/collegeColors.js). Update BOTH places if this
     * ever changes.
     */
    private function collegeColorMap(): array
    {
        return [
            'CCS' => 'yellow',
            'CRIM' => 'purple',
            'CTE' => 'blue',
            'SHTM' => 'orange',
            'CBA' => 'green',
            'General' => 'gray',
            'Shared' => 'gray',
        ];
    }
}