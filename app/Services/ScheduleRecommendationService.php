<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\Faculty;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates "here's a better option" suggestions once
 * ScheduleValidationService has found a conflict. Reads from the same
 * inputs the Greedy Scheduler and Master Grid already use (Faculty,
 * Rooms, Room/Faculty preference pivots, the in-memory preview) — it
 * never invents data of its own and never writes anything.
 *
 * Kept entirely separate from ScheduleValidationService on purpose:
 * validation answers "is this allowed?", recommendation answers
 * "what would be allowed instead?" — a future optimizer only needs the
 * first; a human reviewing conflicts wants the second too.
 */
class ScheduleRecommendationService
{
    public function __construct(
        private readonly ScheduleValidationService $validator
    ) {
    }

    /**
     * @param  array  $block  The conflicting block (as edited).
     * @param  Collection<int,array>  $allBlocks  Every block in the
     *         current preview, including $block.
     * @return array{faculty: array, rooms: array, times: array}
     */
    public function recommend(array $block, Collection $allBlocks, AcademicTerm $term): array
    {
        // Preview + already-saved schedules, merged — see
        // ScheduleValidationService::allKnownBlocksForTerm(). Every
        // suggestion below is checked against this full picture, not
        // just the in-memory preview batch, so a room/faculty/time
        // already spoken for by a PREVIOUSLY saved class (outside this
        // batch) can never be suggested as if it were free.
        $known = $this->validator->allKnownBlocksForTerm($allBlocks, $term);

        return [
            'faculty' => $this->suggestFaculty($block, $known),
            'rooms' => $this->suggestRooms($block, $known),
            'times' => $this->suggestTimes($block, $known, $term),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Faculty
    |--------------------------------------------------------------------------
    |
    | Sorted by: 1) lowest current teaching load, 2) same department as
    | the offering's program, 3) earliest availability (already
    | filtered — only free faculty are suggested at all).
    */
    private function suggestFaculty(array $block, Collection $allBlocks, int $limit = 3): array
    {
        $departmentId = $block['department_id'] ?? null;

        $candidates = Faculty::where('status', true)
            ->when($block['classification'] ?? null, function ($query, $classification) use ($departmentId) {
                if ($classification === 'Major') {
                    // Major subjects need a faculty scoped to teach
                    // major subjects in this department (Departmental
                    // or Cross Department, same department) or already
                    // department-agnostic Cross Department faculty.
                    $query->where(function ($q) use ($departmentId) {
                        $q->whereIn('faculty_scope', ['departmental', 'cross_department'])
                            ->where(function ($inner) use ($departmentId) {
                                $inner->whereNull('department_id')->orWhere('department_id', $departmentId);
                            });
                    });
                } else {
                    // Minor subjects: General Education faculty, or
                    // Cross Department faculty (minors are unrestricted
                    // for them).
                    $query->where(function ($q) use ($departmentId) {
                        $q->where('faculty_scope', 'general')
                            ->orWhere(function ($inner) use ($departmentId) {
                                $inner->where('faculty_scope', 'cross_department')
                                    ->where(function ($d) use ($departmentId) {
                                        $d->whereNull('department_id')->orWhere('department_id', $departmentId);
                                    });
                            });
                    });
                }
            })
            ->get();

        $loadByFaculty = $allBlocks
            ->whereNotNull('faculty_id')
            ->unique('subject_offering_id')
            ->groupBy('faculty_id')
            ->map(fn ($rows) => $rows->sum(fn ($b) => (int) ($b['units'] ?? 0)));

        return $candidates
            ->filter(fn (Faculty $faculty) => $faculty->id !== ($block['faculty_id'] ?? null))
            ->filter(function (Faculty $faculty) use ($block, $allBlocks) {
                // Must be free at the block's current day/time.
                return ! $this->hasOverlap($allBlocks, $block, 'faculty_id', $faculty->id);
            })
            ->map(function (Faculty $faculty) use ($loadByFaculty, $departmentId) {
                $load = (int) ($loadByFaculty[$faculty->id] ?? 0);

                return [
                    'faculty_id' => $faculty->id,
                    'full_name' => $faculty->full_name,
                    'department_id' => $faculty->department_id,
                    'current_load' => $load,
                    'max_units' => $faculty->max_units,
                    'same_department' => $faculty->department_id === $departmentId,
                    'label' => "{$faculty->full_name} — Current Load: {$load}/{$faculty->max_units} units",
                ];
            })
            ->sort(function ($a, $b) {
                return [$a['current_load'], $a['same_department'] ? 0 : 1]
                    <=> [$b['current_load'], $b['same_department'] ? 0 : 1];
            })
            ->take($limit)
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Rooms
    |--------------------------------------------------------------------------
    |
    | Sorted by: preferred room first, then room type match, capacity
    | fit, and availability at the block's current day/time.
    */
    private function suggestRooms(array $block, Collection $allBlocks, int $limit = 3): array
    {
        $preferredRoomId = DB::table('room_subject_offering')
            ->where('subject_offering_id', $block['subject_offering_id'])
            ->value('room_id');

        $programCode = $block['program_code'] ?? null;

        $candidates = Room::schedulable()
            ->with('roomGroups')
            ->get()
            ->filter(function (Room $room) use ($block, $programCode) {
                if ($room->id === ($block['room_id'] ?? null)) {
                    return false;
                }

                if (! empty($block['room_type']) && $room->room_type !== $block['room_type']) {
                    return false;
                }

                $allowed = in_array('General', $room->room_group_codes, true)
                    || ($programCode && in_array($programCode, $room->room_group_codes, true));

                return $allowed;
            })
            ->filter(fn (Room $room) => ! $this->hasOverlap($allBlocks, $block, 'room_id', $room->id))
            ->values();

        return $candidates
            ->map(fn (Room $room) => [
                'room_id' => $room->id,
                'room_code' => $room->room_code,
                'room_type' => $room->room_type,
                'capacity' => $room->capacity,
                'is_preferred' => $room->id === $preferredRoomId,
                'label' => $room->id === $preferredRoomId
                    ? "{$room->room_code} — Preferred, {$room->room_type}, Available"
                    : "{$room->room_code} — {$room->room_type}, Available",
            ])
            ->sort(fn ($a, $b) => ($a['is_preferred'] ? 0 : 1) <=> ($b['is_preferred'] ? 0 : 1))
            ->take($limit)
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Time
    |--------------------------------------------------------------------------
    |
    | Walks the term's working days/start-times (same grid the Greedy
    | Scheduler builds), starting from the block's current day, and
    | returns the nearest slots where THIS SAME faculty + room + section
    | are all simultaneously free.
    */
    private function suggestTimes(array $block, Collection $allBlocks, AcademicTerm $term, int $limit = 2): array
    {
        $duration = ($block['end_minutes'] ?? null) !== null && ($block['start_minutes'] ?? null) !== null
            ? $block['end_minutes'] - $block['start_minutes']
            : (int) ($block['hours'] ?? 0) * 60;

        if ($duration <= 0) {
            return [];
        }

        $dayFields = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $workingDays = array_values(array_filter($dayFields, fn ($f) => (bool) $term->{$f}));

        // Rotate so we search starting from the block's current day.
        $currentIndex = array_search($block['day'], $workingDays, true);
        if ($currentIndex !== false) {
            $workingDays = array_merge(
                array_slice($workingDays, $currentIndex),
                array_slice($workingDays, 0, $currentIndex)
            );
        }

        [$schoolStart, $schoolEnd] = $this->validator->schoolHours($term);
        [$lunchStart, $lunchEnd] = $this->validator->lunchWindow($term);
        $interval = $term->time_interval ?: 30;

        $suggestions = [];

        if ($schoolStart === null || $schoolEnd === null) {
            return [];
        }

        foreach ($workingDays as $day) {
            for ($start = $schoolStart; $start + $duration <= $schoolEnd; $start += $interval) {
                $end = $start + $duration;

                if ($lunchStart !== null && $lunchEnd !== null && $start < $lunchEnd && $end > $lunchStart) {
                    continue;
                }

                if ($day === $block['day'] && $start === $block['start_minutes']) {
                    continue; // that's the current, conflicting slot
                }

                $candidate = array_merge($block, [
                    'day' => $day,
                    'start_minutes' => $start,
                    'end_minutes' => $end,
                ]);

                $conflictFree = ! $this->hasOverlap($allBlocks, $candidate, 'faculty_id', $block['faculty_id'] ?? null, $day, $start, $end)
                    && ! $this->hasOverlap($allBlocks, $candidate, 'room_id', $block['room_id'] ?? null, $day, $start, $end)
                    && ! $this->hasOverlap($allBlocks, $candidate, 'section_id', $block['section_id'] ?? null, $day, $start, $end);

                if (! $conflictFree) {
                    continue;
                }

                $suggestions[] = [
                    'day' => $day,
                    'start_minutes' => $start,
                    'end_minutes' => $end,
                    'label' => ucfirst($day) . ' ' . $this->validator->label($start) . '–' . $this->validator->label($end),
                ];

                if (count($suggestions) >= $limit) {
                    return $suggestions;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Whether any OTHER block in $allBlocks shares $field's value with
     * $matchValue and overlaps the given day/time window.
     */
    private function hasOverlap(
        Collection $allBlocks,
        array $block,
        string $field,
        ?int $matchValue,
        ?string $day = null,
        ?int $start = null,
        ?int $end = null
    ): bool {
        if (! $matchValue) {
            return false;
        }

        $day ??= $block['day'];
        $start ??= $block['start_minutes'];
        $end ??= $block['end_minutes'];

        return $allBlocks
            ->reject(fn ($b) => $b['subject_offering_id'] === $block['subject_offering_id'])
            ->filter(fn ($b) => ($b[$field] ?? null) === $matchValue)
            ->filter(fn ($b) => ($b['day'] ?? null) === $day)
            ->filter(fn ($b) => $start < ($b['end_minutes'] ?? -1) && $end > ($b['start_minutes'] ?? PHP_INT_MAX))
            ->isNotEmpty();
    }
}