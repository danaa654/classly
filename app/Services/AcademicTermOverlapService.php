<?php

namespace App\Services;

use App\Models\AcademicTerm;

/**
 * Extracted out of AcademicTermRequest so the overlap rule is a single,
 * reusable, independently-testable piece of logic rather than inline
 * validator code — per the "extract into a reusable service/helper"
 * requirement.
 *
 * Two Academic Terms are considered overlapping purely by their
 * Class Start / Class End date ranges — regardless of Academic Year or
 * Semester. A school cannot be running two terms' classes on the same
 * calendar day, even if those terms belong to different "SY" labels.
 */
class AcademicTermOverlapService
{
    /**
     * Whether the given [start, end] range overlaps any other Academic
     * Term's Class Start / Class End range.
     */
    public function overlaps(string $start, string $end, ?int $ignoreId = null): bool
    {
        return $this->conflicting($start, $end, $ignoreId) !== null;
    }

    /**
     * Returns the first Academic Term whose Class Start / Class End range
     * conflicts with the given range, or null if there's no conflict.
     *
     * Standard interval-overlap test: two ranges [aStart, aEnd] and
     * [bStart, bEnd] overlap when aStart <= bEnd AND aEnd >= bStart.
     */
    public function conflicting(string $start, string $end, ?int $ignoreId = null): ?AcademicTerm
    {
        return AcademicTerm::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('class_start_date', '<=', $end)
            ->where('class_end_date', '>=', $start)
            ->first();
    }
}