<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\Specialization;

/**
 * Generates EDP Codes for Subject Offerings.
 *
 * Format: PREFIX-YYSYNNN
 *   PREFIX  = Program prefix, or Specialization code for programs that
 *             require one (see prefixFor() below)
 *   YY      = Academic Year start, last 2 digits (2026 -> "26")
 *   S       = Semester (1, 2, or 3 for Summer)
 *   Y       = Year Level (1-4)
 *   NNN     = 3-digit sequential number, scoped to
 *             (PREFIX + YY + S + Y) — i.e. every offering generated for
 *             the same program/specialization, academic year, semester,
 *             and year level shares one running sequence, regardless of
 *             which Section it belongs to.
 *
 * Examples: IT-2611001, HM-2611001, TM-2611001, ED-2611001,
 *           FB-2611001, FI-2611001, LD-2611001, QD-2611001
 *
 * Prefix rule is intentionally NOT the same "does this Program have an
 * active Specialization?" check SectionCodeService uses for Section
 * Codes. That check is too broad for EDP purposes: BSED has active
 * Specializations (English, Math, ...) for Section/Curriculum purposes,
 * but its EDP prefix must stay "ED" regardless — a Section Code like
 * BSED-ENG-1A does NOT mean the EDP prefix is "ENG". Only BSCRIM
 * actually wants its EDP prefix driven by the Specialization:
 *   - Programs NOT in SPECIALIZATION_PREFIXED_PROGRAMS (BSIT, BSHM,
 *     BSTM, BSED, ...) use their own Program code with the leading
 *     "BS" stripped (BSIT -> IT, BSHM -> HM, BSTM -> TM, BSED -> ED —
 *     no matter which Specialization the Curriculum/Section has).
 *   - Programs IN SPECIALIZATION_PREFIXED_PROGRAMS (BSCRIM today) use
 *     the Specialization's own short code instead (FB, FI, LD, QD).
 *
 * This mirrors SectionCodeService's split (server-side generator here;
 * nothing client-side needs to preview an EDP Code today since offerings
 * are never manually created).
 */
class EdpCodeService
{
    /**
     * Programs whose EDP Code prefix comes from the Specialization
     * rather than the Program's own code. Deliberately a hardcoded
     * allow-list (not "any Program with active Specializations", which
     * is what SectionCodeService uses for Section Codes) — add a
     * Program code here only when its EDP prefix should genuinely
     * follow the Specialization instead of the Program.
     */
    private const SPECIALIZATION_PREFIXED_PROGRAMS = ['BSCRIM'];

    /**
     * Resolve the EDP Code prefix for a Program (+ its Specialization,
     * when that Program is specialization-prefixed). Returns null if a
     * required Specialization is missing or has no code — callers
     * should treat that as "cannot generate yet" rather than falling
     * back to something guessed.
     */
    public static function prefixFor(Program $program, ?Specialization $specialization): ?string
    {
        $code = strtoupper($program->code);

        if (in_array($code, self::SPECIALIZATION_PREFIXED_PROGRAMS, true)) {
            if (! $specialization || empty($specialization->code)) {
                return null;
            }

            return strtoupper($specialization->code);
        }

        // Strip a leading "BS" the same way abbreviateProgramName() in
        // useSectionCodeGenerator.js strips "Bachelor of (Science in)?"
        // from the full name — this is the code-side equivalent.
        return str_starts_with($code, 'BS') ? substr($code, 2) : $code;
    }

    /**
     * Build the (PREFIX + YY + S + Y) scope key used to group offerings
     * into one sequential-numbering run. Same shape the generator uses
     * to keep an in-memory counter per scope while it works through a
     * batch of Sections/Curriculum Items.
     */
    public static function scopeKey(string $prefix, AcademicTerm $term, int $yearLevel): string
    {
        return $prefix . '-' . self::yearSemesterYearLevel($term, $yearLevel);
    }

    /**
     * Build the full EDP Code from its already-resolved parts and a
     * sequence number (1-based). $sequence is zero-padded to 3 digits —
     * callers are responsible for tracking/incrementing it per
     * scopeKey() so numbers run continuously across every Section in
     * the same program/specialization + academic year + semester +
     * year level.
     */
    public static function build(string $prefix, AcademicTerm $term, int $yearLevel, int $sequence): string
    {
        return $prefix
            . '-'
            . self::yearSemesterYearLevel($term, $yearLevel)
            . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * The "YYSY" middle segment shared by scopeKey() and build() — kept
     * in one place so the two can never drift out of sync with each
     * other.
     */
    private static function yearSemesterYearLevel(AcademicTerm $term, int $yearLevel): string
    {
        $yy = substr((string) $term->academic_year, 2, 2);

        return $yy . $term->semester . $yearLevel;
    }
}