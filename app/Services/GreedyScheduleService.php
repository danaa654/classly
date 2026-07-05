<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\Faculty;
use App\Models\Room;
use App\Models\Section;
use App\Models\Specialization;
use App\Models\SubjectOffering;
use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Greedy Schedule Generator — v2 (Intelligent Scheduling Engine).
 *
 * Produces an in-memory DRAFT/PREVIEW schedule for a single Section
 * (Department + Program + [Specialization] + Year Level + Section).
 * Nothing is persisted here — this service only computes and returns
 * an array of proposed blocks. Saving is a deliberately separate
 * concern (see MasterGridController::save()).
 *
 * ── What changed from v1 ─────────────────────────────────────────────
 * v1 required a Subject Offering to already have BOTH an assigned
 * Faculty (Faculty Loading) and a preferred Room before it could be
 * placed reliably, and any single unplaceable subject could make the
 * whole run look like it produced nothing. v2 never requires either:
 *
 *   Faculty — if Faculty Loading already assigned someone, we try to
 *   honor that first and only fall back to an automatic search if that
 *   faculty genuinely cannot take the slot (busy, overloaded,
 *   inactive). If nobody is assigned at all, we search automatically
 *   from the very start — same department -> qualified for the
 *   subject's classification -> lowest current load -> no conflict.
 *
 *   Room — the preferred Room (if any) is always tried first, but a
 *   fully automatic, type/program/capacity-compatible Room search
 *   always runs immediately after, in the same pass.
 *
 * Every subject is still processed independently and greedily (first
 * conflict-free combination wins, no backtracking, no reshuffling of
 * already-accepted blocks — see findPlacement()). If one subject can't
 * be placed anywhere, we record why and move on to the next one; we
 * never abort the batch. The goal is maximum subjects scheduled, not
 * an all-or-nothing batch.
 *
 * ── Why conflict-checking is in-memory only ─────────────────────────
 * There is no need to also query the `schedules` table here — Save
 * Schedule (MasterGridController::save()) re-validates the whole
 * preview against both the in-memory set AND `schedules` via
 * ScheduleValidationService right before committing. This service's
 * only job is to produce a good first draft; every conflict check here
 * is against the blocks generated within THIS SAME preview run.
 */
class GreedyScheduleService
{
    /**
     * Entry point. Generates a draft schedule for one Section.
     *
     * @param  AcademicTerm  $term
     * @param  array{department_id:int,program_id:int,specialization_id:?int,year_level:int,section_id:int}  $filters
     * @return array{academic_term_id:int,section_id:int,generated_at:string,blocks:array<int,array>,scheduled_count:int,unscheduled_count:int}
     */
    public function generateForSection(AcademicTerm $term, array $filters): array
    {
        $section = Section::find($filters['section_id']);

        $offerings = $this->loadOfferings($term, $filters);

        $offerings = $this->applyPriorityOrder($offerings);

        $grid = $this->buildTimeGrid($term);

        // Running faculty load, seeded from whatever is already
        // committed in Faculty Loading for this term (units), then
        // incremented in-memory as this run places more subjects. This
        // is what "lowest current workload" and "faculty overload"
        // checks are measured against — always live, never stale.
        $facultyLoad = $this->initialFacultyLoad($term);

        $placedBlocks = [];
        $scheduledSubjectIds = [];
        $results = [];

        foreach ($offerings as $offering) {
            // Duplicate subject guard — the same subject should never be
            // scheduled twice for the same section in one run.
            if (in_array($offering->subject_id, $scheduledSubjectIds, true)) {
                $results[] = $this->presentBlock($offering, null, 'skipped', 'Duplicate subject — already scheduled for this section.');
                continue;
            }

            $durationMinutes = (int) $offering->hours * 60;

            if ($durationMinutes <= 0) {
                $this->debug("Processing: {$this->subjectLabel($offering)}\n\nRejected — Subject Offering has no hours configured.");

                $results[] = $this->presentBlock($offering, null, 'unscheduled', 'Subject Offering has no hours configured.');
                continue;
            }

            $assignedFaculty = $this->resolveAssignedFaculty($offering);

            $this->debug(sprintf(
                "Processing: %s\n\nFaculty:\n%s\n\nPreferred Room:\n%s",
                $this->subjectLabel($offering),
                $assignedFaculty ? "{$assignedFaculty->full_name} (Source: Faculty Loading)" : 'None assigned — will search automatically.',
                $this->preferredRoomCode($offering) ?? 'None set — will search automatically.'
            ));

            $placement = $this->findPlacement($offering, $section, $grid, $durationMinutes, $assignedFaculty, $facultyLoad, $placedBlocks);

            if (! $placement) {
                $reason = $this->unscheduledReason($offering, $assignedFaculty);

                $this->debug("Rejected: {$this->subjectLabel($offering)}\n\nReason:\n{$reason}");

                $results[] = $this->presentBlock($offering, null, 'unscheduled', $reason);
                continue;
            }

            /** @var Faculty|null $usedFaculty */
            $usedFaculty = $placement['faculty'];

            $placedBlocks[] = [
                'room_id' => $placement['room']->id,
                'faculty_id' => $usedFaculty?->id,
                'section_id' => $offering->section_id,
                'day' => $placement['day'],
                'start' => $placement['start'],
                'end' => $placement['end'],
            ];

            if ($usedFaculty) {
                $facultyLoad[$usedFaculty->id] = ($facultyLoad[$usedFaculty->id] ?? 0) + (int) $offering->units;
            }

            $scheduledSubjectIds[] = $offering->subject_id;

            $this->debug(sprintf(
                "Accepted: %s\n\nFaculty: %s (%s)\nRoom: %s\nDay: %s\nTime: %s - %s",
                $this->subjectLabel($offering),
                $usedFaculty?->full_name ?? 'Unassigned',
                $placement['faculty_source'] ?? 'none',
                $placement['room']->room_code,
                ucfirst($placement['day']),
                $this->label($placement['start']),
                $this->label($placement['end'])
            ));

            $results[] = $this->presentBlock($offering, $placement, 'preview', null);
        }

        $scheduledCount = count($scheduledSubjectIds);
        $unscheduledCount = collect($results)->where('status', '!=', 'preview')->count();

        $this->debug("Preview Result\n\nScheduled: {$scheduledCount}\nFailed: {$unscheduledCount}");

        return [
            'academic_term_id' => $term->id,
            'section_id' => $filters['section_id'],
            'generated_at' => now()->toIso8601String(),
            'blocks' => $results,
            'scheduled_count' => $scheduledCount,
            'unscheduled_count' => $unscheduledCount,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Subject Offering Source
    |--------------------------------------------------------------------------
    */

    /**
     * Only Subject Offerings for: Active Academic Term, selected
     * Department (via Program), Program, Year Level, and Section.
     * Already-scheduled/completed/archived offerings are excluded —
     * "ignore already scheduled offerings" per spec.
     */
    private function loadOfferings(AcademicTerm $term, array $filters): Collection
    {
        $offerings = SubjectOffering::with([
                'subject.roomGroups',
                'teachingAssignment.faculty',
                'program.department',
                'section',
            ])
            ->forTerm($term->id)
            ->where('program_id', $filters['program_id'])
            ->where('year_level', $filters['year_level'])
            ->where('section_id', $filters['section_id'])
            ->whereHas('program', fn ($q) => $q->where('department_id', $filters['department_id']))
            ->get()
            ->reject(fn (SubjectOffering $o) => in_array($o->overall_status, [
                SubjectOffering::STATUS_SCHEDULED,
                SubjectOffering::STATUS_COMPLETED,
                SubjectOffering::STATUS_ARCHIVED,
            ], true));

        // Specialization is an optional narrowing filter (e.g. BSCRIM's
        // FB/LD/QD/FI tracks). Specializations were folded into the
        // room_group taxonomy on Subject, so we match a subject as
        // "belonging" to a specialization when its room_group_codes
        // contains that specialization's code. If the specialization
        // can't be resolved, or no code matches, we leave the offering
        // list untouched rather than silently returning nothing.
        if (! empty($filters['specialization_id'])) {
            $specialization = Specialization::find($filters['specialization_id']);

            if ($specialization) {
                $offerings = $offerings->filter(
                    fn (SubjectOffering $o) => in_array($specialization->code, $o->subject?->room_group_codes ?? [], true)
                );
            }
        }

        return $offerings->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Greedy Priority Order
    |--------------------------------------------------------------------------
    */

    /**
     * Priority, highest first:
     *   1. Subjects with an assigned faculty (from Faculty Loading)
     *   2. Laboratory subjects
     *   3. Major subjects
     *   4. Minor subjects
     *
     * Implemented as a rank tuple per offering (0 = higher priority),
     * compared elementwise — PHP's <=> on equal-length arrays compares
     * index by index, so [0,1,0] sorts before [0,1,1], etc.
     */
    private function applyPriorityOrder(Collection $offerings): Collection
    {
        return $offerings
            ->sort(fn (SubjectOffering $a, SubjectOffering $b) => $this->priorityRank($a) <=> $this->priorityRank($b))
            ->values();
    }

    private function priorityRank(SubjectOffering $offering): array
    {
        $hasFaculty = $this->resolveAssignedFaculty($offering) ? 0 : 1;
        $isLab = strtolower((string) $offering->room_type) === 'laboratory' ? 0 : 1;
        $isMajor = $offering->classification === SubjectOffering::CLASSIFICATION_MAJOR ? 0 : 1;

        return [$hasFaculty, $isLab, $isMajor];
    }

    /*
    |--------------------------------------------------------------------------
    | Faculty — Assigned (Faculty Loading)
    |--------------------------------------------------------------------------
    |
    | Faculty Loading (the "Assign Subject" workflow) writes exactly one
    | row per Subject Offering into `teaching_assignments`
    | (subject_offering_id unique, faculty_id) — see
    | TeachingAssignmentController::store(). SubjectOffering::
    | teachingAssignment() is the one relationship that reaches it, and
    | it is what MasterGridDataService::presentOffering() already reads
    | to show "Faculty Assigned" on the Subject Card. This method is the
    | ONLY place GreedyScheduleService resolves an assigned faculty, so
    | it can never drift out of sync with what the Master Grid displays.
    |
    | This only recognizes a MANUALLY assigned faculty. It never picks
    | one on its own — that's resolveAutoFacultyCandidates() below,
    | which findPlacement() falls back to whenever this returns null OR
    | the assigned faculty turns out to be unusable for every slot.
    */
    private function resolveAssignedFaculty(SubjectOffering $offering): ?Faculty
    {
        $assignment = $offering->teachingAssignment;
        $faculty = $assignment?->faculty_id ? $assignment->faculty : null;

        return ($faculty && $faculty->status) ? $faculty : null;
    }

    /**
     * Every active Faculty member eligible to automatically pick up
     * this Subject Offering, sorted best-candidate-first.
     *
     * ── Faculty Scope rules (mirrors the Faculty Loading module's
     *    three-scope system — see Faculty::scopeEligibleFor() /
     *    TeachingAssignmentService) ──────────────────────────────────
     *
     *   Departmental — belongs to exactly one college. Can ONLY teach
     *   Major subjects, and ONLY inside their own college. Never
     *   eligible for a Minor subject, anywhere, and never eligible for
     *   a Major belonging to another department.
     *
     *   Cross-Department — also belongs to one home college, but has a
     *   wider reach: can teach both Major AND Minor subjects inside
     *   their own college, AND can additionally teach Minor subjects
     *   belonging to OTHER departments. They are never handed a Major
     *   subject outside their own college.
     *
     *   General / GenEd — belongs to no department (department_id is
     *   null) and can ONLY teach Minor subjects, but across every
     *   department without restriction.
     *
     * In table form, for a Subject Offering belonging to department D:
     *
     *   | Scope            | Major (in D) | Minor (in D) | Minor (other) |
     *   |-------------------|:-----------:|:------------:|:--------------:|
     *   | Departmental (D)  |     YES     |      NO      |       NO       |
     *   | Cross-Dept (D)    |     YES     |     YES      |      YES       |
     *   | General/GenEd     |     NO      |     YES      |      YES       |
     *
     * A Major is therefore always restricted to Departmental/Cross-
     * Department faculty who belong to that exact department — never
     * General/GenEd, and never a Departmental/Cross-Department faculty
     * from a different college. A Minor is open to General/GenEd
     * faculty (any department) and Cross-Department faculty (any
     * department, including their own) — Departmental faculty are
     * never eligible for a Minor.
     *
     * Secondary ranking, once scope-eligible:
     *   1. Same department as the offering (helps a Cross-Department
     *      faculty's own-college minors edge out their outside-college
     *      minors, all else equal)
     *   2. Lowest current teaching load (live — includes everything
     *      already placed earlier in THIS run)
     *
     * Eligibility mirrors ScheduleRecommendationService::suggestFaculty()
     * so "who could teach this" never disagrees between the Greedy
     * Scheduler and the Interactive Review's suggestion panel — if you
     * change the rule here, mirror it there too.
     *
     * @return Collection<int,Faculty>
     */
    private function resolveAutoFacultyCandidates(SubjectOffering $offering, array $facultyLoad): Collection
    {
        $departmentId = $offering->program?->department_id;
        $isMajor = $offering->classification === SubjectOffering::CLASSIFICATION_MAJOR;

        $candidates = Faculty::where('status', true)
            ->where(function ($query) use ($departmentId, $isMajor) {
                if ($isMajor) {
                    // Major: Departmental or Cross-Department faculty,
                    // strictly within their own home department. Never
                    // General/GenEd, never a different department.
                    $query->whereIn('faculty_scope', ['departmental', 'cross_department'])
                        ->where('department_id', $departmentId);
                } else {
                    // Minor: General/GenEd (any department, they carry
                    // none of their own) or Cross-Department (any
                    // department — inside or outside their own home
                    // college). Departmental faculty are excluded
                    // entirely from Minor subjects.
                    $query->whereIn('faculty_scope', ['general', 'cross_department']);
                }
            })
            ->get();

        return $candidates
            ->map(function (Faculty $faculty) use ($facultyLoad, $departmentId) {
                $faculty->setAttribute('_current_load', $facultyLoad[$faculty->id] ?? 0);
                $faculty->setAttribute('_same_department', $faculty->department_id === $departmentId);

                return $faculty;
            })
            ->sort(function (Faculty $a, Faculty $b) {
                return [$a->_same_department ? 0 : 1, $a->_current_load]
                    <=> [$b->_same_department ? 0 : 1, $b->_current_load];
            })
            ->values();
    }

    /**
     * Seeds the running faculty-load map from what's already committed
     * in Faculty Loading for this term (units per faculty across their
     * existing Teaching Assignments) — so "lowest current workload" and
     * the max-load hard constraint reflect real load from the very
     * first subject processed, not just what this run places.
     *
     * @return array<int,int> faculty_id => units
     */
    private function initialFacultyLoad(AcademicTerm $term): array
    {
        return TeachingAssignment::forTerm($term->id)
            ->active()
            ->with('subjectOffering')
            ->get()
            ->groupBy('faculty_id')
            ->map(fn ($rows) => $rows->sum(fn (TeachingAssignment $ta) => (int) ($ta->subjectOffering?->units ?? 0)))
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Time Grid (derived from the Active Academic Term)
    |--------------------------------------------------------------------------
    */

    /**
     * Mirrors the client-side useTimetableGrid.js logic in PHP: working
     * days (filtered to whichever the term has enabled) and every valid
     * meeting start time (interval steps across school hours, with any
     * start that falls inside the lunch window excluded outright).
     * Starts are naturally ascending, so morning slots are always tried
     * first — this is what gives the greedy search its "prefer morning"
     * soft-constraint behavior without any extra scoring pass.
     */
    private function buildTimeGrid(AcademicTerm $term): array
    {
        $dayFields = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $workingDays = array_values(array_filter($dayFields, fn ($field) => (bool) $term->{$field}));

        $start = $this->toMinutes($term->school_start_time);
        $end = $this->toMinutes($term->school_end_time);
        $lunchStart = $this->toMinutes($term->lunch_start_time);
        $lunchEnd = $this->toMinutes($term->lunch_end_time);
        $interval = $term->time_interval ?: 30;

        $starts = [];

        if ($start !== null && $end !== null && $interval > 0) {
            for ($cursor = $start; $cursor < $end; $cursor += $interval) {
                $insideLunch = $lunchStart !== null && $lunchEnd !== null
                    && $cursor >= $lunchStart && $cursor < $lunchEnd;

                if ($insideLunch) {
                    continue;
                }

                $starts[] = $cursor;
            }
        }

        return [
            'working_days' => $workingDays,
            'starts' => $starts,
            'school_end' => $end,
            'lunch_start' => $lunchStart,
            'lunch_end' => $lunchEnd,
        ];
    }

    /**
     * AcademicTerm casts these as 'datetime:H:i' — but that format
     * suffix ONLY controls array/JSON serialization (which is why the
     * Master Grid header correctly shows "08:00 – 20:00"). Plain PHP
     * attribute access (`$term->school_start_time`) hands back a raw
     * Carbon instance instead, NOT the "H:i" string this used to
     * assume. Exploding a stringified Carbon ("2026-07-05 08:00:00")
     * on ':' produced garbage hour/minute values (e.g. hours=2026),
     * which silently broke every single time-slot check — the actual
     * cause of "0 scheduled" regardless of faculty/room assignment.
     *
     * Handles a Carbon/DateTime instance directly, and falls back to a
     * regex pull of the trailing H:i(:s) segment for anything that
     * arrives as a string instead, so this is correct either way.
     */
    private function toMinutes($value): ?int
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return ((int) $value->format('H')) * 60 + (int) $value->format('i');
        }

        $value = (string) $value;

        if (preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?\s*$/', $value, $matches)) {
            return ((int) $matches[1]) * 60 + (int) $matches[2];
        }

        return null;
    }

    /**
     * A candidate block is valid only if it fits inside school hours
     * and never overlaps the lunch window — it is not split around
     * lunch, it simply isn't offered as a start option if it would
     * cross into it. Also enforces allow_split_schedule = false means
     * nothing here needs to change — a block's duration is always one
     * contiguous run in v2, same as v1.
     */
    private function isValidBlock(array $grid, int $start, int $duration): bool
    {
        $end = $start + $duration;

        if ($grid['school_end'] !== null && $end > $grid['school_end']) {
            return false;
        }

        if ($grid['lunch_start'] !== null && $grid['lunch_end'] !== null) {
            $overlapsLunch = $start < $grid['lunch_end'] && $end > $grid['lunch_start'];

            if ($overlapsLunch) {
                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Room Candidates
    |--------------------------------------------------------------------------
    */

    /**
     * Preferred room (from the Manage Subjects preference pivot) always
     * tried first — "use it if available" per spec, meaning we still
     * check it for conflicts, we just don't re-validate its type/program/
     * capacity fit since that preference was set deliberately. Every
     * other schedulable room that matches room type + allowed program +
     * capacity follows, ordered smallest-sufficient-capacity first so we
     * don't burn a large room on a small section.
     */
    private function candidateRooms(SubjectOffering $offering, ?Section $section): Collection
    {
        $preferredRoomId = DB::table('room_subject_offering')
            ->where('subject_offering_id', $offering->id)
            ->value('room_id');

        $fallback = Room::schedulable()
            ->with('roomGroups')
            ->get()
            ->filter(function (Room $room) use ($offering, $section) {
                if ($offering->room_type && $room->room_type !== $offering->room_type) {
                    return false;
                }

                $programCode = $offering->program?->code;
                $allowed = in_array('General', $room->room_group_codes, true)
                    || ($programCode && in_array($programCode, $room->room_group_codes, true));

                if (! $allowed) {
                    return false;
                }

                if ($section && $section->capacity && (int) $room->capacity < (int) $section->capacity) {
                    return false;
                }

                return true;
            })
            ->sortBy('capacity')
            ->values();

        $ordered = collect();

        if ($preferredRoomId) {
            $preferredRoom = $fallback->firstWhere('id', $preferredRoomId) ?? Room::find($preferredRoomId);

            if ($preferredRoom && $preferredRoom->active) {
                $ordered->push($preferredRoom);
            }
        }

        foreach ($fallback as $room) {
            if ($room->id !== $preferredRoomId) {
                $ordered->push($room);
            }
        }

        return $ordered;
    }

    private function preferredRoomCode(SubjectOffering $offering): ?string
    {
        return DB::table('room_subject_offering')
            ->join('rooms', 'rooms.id', '=', 'room_subject_offering.room_id')
            ->where('room_subject_offering.subject_offering_id', $offering->id)
            ->value('rooms.room_code');
    }

    /*
    |--------------------------------------------------------------------------
    | Placement Search (Greedy — first fit wins, no backtracking)
    |--------------------------------------------------------------------------
    */

    /**
     * Two passes, in order:
     *
     *   Pass 1 — Assigned Faculty. If Faculty Loading already assigned
     *   someone AND that faculty isn't already over their max load,
     *   walk rooms -> days -> starts and take the first slot where that
     *   exact faculty, the room, and the section are all free. This is
     *   the "use assigned faculty, only reject on real conflict" rule.
     *
     *   Pass 2 — Automatic Faculty Search. Runs whenever Pass 1 didn't
     *   place the subject (no assigned faculty at all, assigned faculty
     *   is overloaded, or assigned faculty had no free slot anywhere).
     *   Walks rooms -> days -> starts and, for each slot, tries
     *   auto-search candidates — filtered to Faculty Scope-eligible
     *   candidates only, see resolveAutoFacultyCandidates() — in
     *   priority order until one is free.
     *
     * Either pass returns the very first working combination it finds
     * — this is the greedy choice per spec: accept immediately, never
     * reshuffle, never revisit an earlier subject.
     */
    private function findPlacement(
        SubjectOffering $offering,
        ?Section $section,
        array $grid,
        int $duration,
        ?Faculty $assignedFaculty,
        array $facultyLoad,
        array $placedBlocks
    ): ?array {
        $rooms = $this->candidateRooms($offering, $section);

        $assignedUsable = $assignedFaculty
            && ! $this->exceedsMaxLoad($assignedFaculty, $facultyLoad, (int) $offering->units);

        if ($assignedUsable) {
            foreach ($rooms as $room) {
                foreach ($grid['working_days'] as $day) {
                    foreach ($grid['starts'] as $start) {
                        if (! $this->isValidBlock($grid, $start, $duration)) {
                            continue;
                        }

                        $end = $start + $duration;

                        if ($this->hasConflict($placedBlocks, $day, $start, $end, $room->id, $assignedFaculty->id, $offering->section_id)) {
                            continue;
                        }

                        return [
                            'room' => $room,
                            'faculty' => $assignedFaculty,
                            'faculty_source' => 'assigned',
                            'day' => $day,
                            'start' => $start,
                            'end' => $end,
                        ];
                    }
                }
            }

            $this->debug("{$this->subjectLabel($offering)} — assigned faculty {$assignedFaculty->full_name} has no free slot anywhere. Falling back to automatic search.");
        } elseif ($assignedFaculty) {
            $this->debug("{$this->subjectLabel($offering)} — assigned faculty {$assignedFaculty->full_name} would exceed max load. Falling back to automatic search.");
        }

        $autoCandidates = $this->resolveAutoFacultyCandidates($offering, $facultyLoad)
            ->reject(fn (Faculty $f) => $assignedFaculty && $f->id === $assignedFaculty->id)
            ->values();

        if ($autoCandidates->isEmpty() && ! $assignedFaculty) {
            // No one is even theoretically eligible — no point walking
            // the whole room/day/time grid for nothing.
            return null;
        }

        foreach ($rooms as $room) {
            foreach ($grid['working_days'] as $day) {
                foreach ($grid['starts'] as $start) {
                    if (! $this->isValidBlock($grid, $start, $duration)) {
                        continue;
                    }

                    $end = $start + $duration;

                    if ($this->hasConflict($placedBlocks, $day, $start, $end, $room->id, null, $offering->section_id)) {
                        // Room or section already taken at this slot —
                        // no faculty choice will fix that, skip ahead.
                        continue;
                    }

                    foreach ($autoCandidates as $candidate) {
                        if ($this->exceedsMaxLoad($candidate, $facultyLoad, (int) $offering->units)) {
                            continue;
                        }

                        if ($this->hasConflict($placedBlocks, $day, $start, $end, $room->id, $candidate->id, $offering->section_id)) {
                            continue;
                        }

                        return [
                            'room' => $room,
                            'faculty' => $candidate,
                            'faculty_source' => 'auto',
                            'day' => $day,
                            'start' => $start,
                            'end' => $end,
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function exceedsMaxLoad(Faculty $faculty, array $facultyLoad, int $additionalUnits): bool
    {
        if (! $faculty->max_units) {
            return false;
        }

        $currentLoad = $facultyLoad[$faculty->id] ?? 0;

        return ($currentLoad + $additionalUnits) > $faculty->max_units;
    }

    /**
     * A single overlap check that covers all three conflict types at
     * once: two blocks on the same day whose time ranges overlap
     * conflict if they share a Room, a Faculty (when one is being
     * checked), or a Section.
     */
    private function hasConflict(
        array $placedBlocks,
        string $day,
        int $start,
        int $end,
        int $roomId,
        ?int $facultyId,
        int $sectionId
    ): bool {
        foreach ($placedBlocks as $block) {
            if ($block['day'] !== $day) {
                continue;
            }

            $overlaps = $start < $block['end'] && $end > $block['start'];

            if (! $overlaps) {
                continue;
            }

            if ($block['room_id'] === $roomId) {
                return true;
            }

            if ($facultyId && $block['faculty_id'] === $facultyId) {
                return true;
            }

            if ($block['section_id'] === $sectionId) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Output Shaping
    |--------------------------------------------------------------------------
    */

    /**
     * Shapes one result row. When $placement is null the offering is
     * reported as unscheduled/skipped with a reason instead of a
     * day/time/room, so the frontend can distinguish a real preview
     * block from one that still needs manual attention.
     */
    private function presentBlock(SubjectOffering $offering, ?array $placement, string $status, ?string $reason): array
    {
        /** @var Faculty|null $faculty */
        $faculty = $placement['faculty'] ?? null;

        return [
            'subject_offering_id' => $offering->id,
            'subject_id' => $offering->subject_id,
            'subject_code' => $offering->subject?->subject_code,
            'descriptive_title' => $offering->subject?->descriptive_title,
            'edp_code' => $offering->edp_code,
            'section_id' => $offering->section_id,
            'section_code' => $offering->section?->section_code,
            'year_level' => $offering->year_level,
            'program_id' => $offering->program_id,
            'program_code' => $offering->program?->code,
            'department_id' => $offering->program?->department_id,
            'college_code' => $offering->program?->department?->abbreviation ?? 'General',
            'academic_term_id' => $offering->academic_term_id,
            'classification' => $offering->classification,
            'room_type' => $offering->room_type,
            'units' => $offering->units,
            'hours' => $offering->hours,

            'faculty_id' => $faculty?->id,
            'faculty_name' => $faculty?->full_name,
            'faculty_source' => $placement['faculty_source'] ?? null, // 'assigned' | 'auto' | null

            'room_id' => $placement['room']->id ?? null,
            'room_code' => $placement['room']->room_code ?? null,

            'day' => $placement['day'] ?? null,
            'start_minutes' => $placement['start'] ?? null,
            'end_minutes' => $placement['end'] ?? null,

            'status' => $status, // 'preview' | 'unscheduled' | 'skipped'
            'reason' => $reason,
        ];
    }

    /**
     * Reason text shown on the preview when a subject could not be
     * placed at all — distinguishes "nobody could ever teach this" from
     * "someone could, but every room/day/time is taken", matching the
     * spec's "No Faculty Found" vs generic conflict examples.
     */
    private function unscheduledReason(SubjectOffering $offering, ?Faculty $assignedFaculty): string
    {
        if (! $assignedFaculty && $this->resolveAutoFacultyCandidates($offering, [])->isEmpty()) {
            return 'No Faculty Found — no eligible faculty exists for this subject (check Faculty Scope vs. this subject\'s classification and department).';
        }

        return 'No conflict-free day/time/room combination found.';
    }

    private function subjectLabel(SubjectOffering $offering): string
    {
        return $offering->subject?->subject_code ?? "Offering #{$offering->id}";
    }

    private function label(int $minutes): string
    {
        $h24 = intdiv($minutes, 60) % 24;
        $m = $minutes % 60;
        $period = $h24 >= 12 ? 'PM' : 'AM';
        $h12 = $h24 % 12 === 0 ? 12 : $h24 % 12;

        return sprintf('%d:%02d %s', $h12, $m, $period);
    }

    private function debug(string $message): void
    {
        Log::debug($message);
    }
}