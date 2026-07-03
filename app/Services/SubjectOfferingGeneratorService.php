<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\CurriculumItem;
use App\Models\Section;
use App\Models\SubjectOffering;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Generates Subject Offerings for a single Academic Term.
 *
 * Pipeline: Academic Term -> active Sections -> each Section's
 * Curriculum -> that Curriculum's active Curriculum Items whose
 * year_level matches the Section and whose semester matches the
 * selected Academic Term. Every matching (Section, Curriculum Item)
 * pair becomes exactly one Subject Offering, status Pending, with a
 * freshly generated EDP Code — no manual creation path exists for this
 * table.
 *
 * Generation is additive and non-destructive: generate() never deletes
 * or overwrites an existing Subject Offering, and never needs the
 * caller to decide anything about existing data up front — it's always
 * safe to call, any time, and it only ever fills in whatever's missing.
 */
class SubjectOfferingGeneratorService
{
    /**
     * Running EDP sequence counters, keyed by
     * EdpCodeService::scopeKey() — reset for every generate() call, and
     * lazily seeded (per scope) from the highest sequence already in
     * the DB the first time that scope is touched.
     *
     * @var array<string, int>
     */
    private array $sequences = [];

    /**
     * Whether any Subject Offerings already exist for this Academic
     * Term. Purely informational — used by the Generate page to show a
     * "N offerings already exist" note; generate() itself doesn't need
     * this, since it's always safe to run regardless.
     */
    public function hasExistingOfferings(AcademicTerm $academicTerm): bool
    {
        return SubjectOffering::where('academic_term_id', $academicTerm->id)->exists();
    }

    /**
     * Delete every Subject Offering already generated for this Academic
     * Term. NOT called by generate() or by SubjectOfferingController
     * anymore — Generate is now additive/non-destructive by design (see
     * generate() below) and never deletes existing offerings on its
     * own. Left in place only as an explicit, deliberate escape hatch
     * for a future "reset this term's offerings" admin action, should
     * one ever be added — it must never be wired into the normal
     * generate flow.
     */
    public function deleteExisting(AcademicTerm $academicTerm): int
    {
        return SubjectOffering::where('academic_term_id', $academicTerm->id)->delete();
    }

    /**
     * Generate Subject Offerings for the given Academic Term.
     *
     * NEVER recreates or deletes an existing Subject Offering — for
     * every (Section, Curriculum Item) pair: if a Subject Offering
     * already exists for this Academic Term, it's left completely
     * untouched (faculty/room assignment, status, schedule, everything)
     * and just counted as skipped; only pairs with no existing offering
     * get a new one created. This means running Generate again after
     * adding a new Section (e.g. BSIT-1B alongside an already-generated
     * BSIT-1A) only ever fills in the gap.
     *
     * Returns a summary array the controller flashes back to the user:
     * how many Sections were scanned, how many (Section, Curriculum
     * Item) pairs were evaluated in total, how many new Offerings were
     * created, how many were skipped because they already existed, and
     * how many were skipped because an EDP prefix couldn't be resolved
     * (e.g. a BSCRIM Curriculum whose Specialization is missing its
     * code).
     *
     * Wrapped in a single transaction: either every new offering in
     * this run is created, or none are — a partial batch would leave
     * some Sections partially filled in and others silently missing.
     */
    public function generate(AcademicTerm $academicTerm, User $generatedBy): array
    {
        $this->sequences = [];

        $summary = [
            'sections_scanned' => 0,
            'items_matched' => 0,
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_unresolved' => 0,
        ];

        DB::transaction(function () use ($academicTerm, $generatedBy, &$summary) {

            $sections = Section::with([
                    'curriculum.program.specializations',
                    'curriculum.specialization',
                ])
                ->where('status', 'Active')
                ->orderBy('section_code')
                ->get();

            $summary['sections_scanned'] = $sections->count();

            // Every (section_id, curriculum_item_id) pair that already
            // has a Subject Offering for this Academic Term, loaded
            // once up front so the "does this already exist?" check
            // below is an in-memory lookup instead of a query per
            // Curriculum Item.
            $existingPairs = SubjectOffering::where('academic_term_id', $academicTerm->id)
                ->get(['section_id', 'curriculum_item_id'])
                ->map(fn ($offering) => $offering->section_id . ':' . $offering->curriculum_item_id)
                ->flip();

            foreach ($sections as $section) {

                $curriculum = $section->curriculum;

                if (! $curriculum) {
                    continue;
                }

                $items = CurriculumItem::where('curriculum_id', $curriculum->id)
                    ->where('year_level', $section->year_level)
                    ->where('semester', $academicTerm->semester)
                    ->where('active', true)
                    ->whereNotNull('subject_id')
                    ->orderBy('sort_order')
                    ->get();

                foreach ($items as $item) {

                    $summary['items_matched']++;

                    $pairKey = $section->id . ':' . $item->id;

                    if (isset($existingPairs[$pairKey])) {
                        $summary['skipped_existing']++;
                        continue;
                    }

                    $created = $this->createOffering(
                        $academicTerm,
                        $section,
                        $item,
                        $generatedBy
                    );

                    if ($created) {
                        $summary['created']++;
                        // Keep the in-memory set current — defensive,
                        // in case the same pair is ever visited twice
                        // in one run.
                        $existingPairs[$pairKey] = true;
                    } else {
                        $summary['skipped_unresolved']++;
                    }
                }
            }

        });

        return $summary;
    }

    /**
     * Create a single Subject Offering for one (Section, Curriculum
     * Item) pair. Returns false (and creates nothing) if an EDP prefix
     * can't be resolved — e.g. a BSCRIM Curriculum whose Specialization
     * is missing its short code — rather than generating a broken code.
     *
     * Caller (generate()) has already confirmed no offering exists yet
     * for this exact (Section, Curriculum Item, Academic Term) — this
     * method only ever creates, never overwrites.
     */
    private function createOffering(
        AcademicTerm $academicTerm,
        Section $section,
        CurriculumItem $item,
        User $generatedBy
    ): bool {
        $curriculum = $section->curriculum;
        $program = $curriculum->program;

        $prefix = EdpCodeService::prefixFor($program, $curriculum->specialization);

        if (! $prefix) {
            return false;
        }

        $scopeKey = EdpCodeService::scopeKey($prefix, $academicTerm, $section->year_level);

        if (! array_key_exists($scopeKey, $this->sequences)) {
            // First time this scope is touched in this run — start
            // counting from whatever's already in the DB (e.g. another
            // Section's offerings already generated earlier), not from
            // 0, so we never re-issue an EDP Code that's already taken.
            $this->sequences[$scopeKey] = $this->highestExistingSequence($scopeKey, $academicTerm);
        }

        $this->sequences[$scopeKey]++;

        $edpCode = EdpCodeService::build(
            $prefix,
            $academicTerm,
            $section->year_level,
            $this->sequences[$scopeKey]
        );

        SubjectOffering::create([
            'academic_term_id' => $academicTerm->id,
            'curriculum_id' => $curriculum->id,
            'curriculum_item_id' => $item->id,
            'subject_id' => $item->subject_id,
            'section_id' => $section->id,
            'edp_code' => $edpCode,
            'year_level' => $item->year_level,
            'semester' => $item->semester,
            'faculty_id' => null,
            'room_id' => null,
            'status' => SubjectOffering::STATUS_PENDING,
            'created_by' => $generatedBy->id,
        ]);

        return true;
    }

    /**
     * The highest EDP sequence number already used within this scope
     * (Prefix + Academic Year + Semester + Year Level) for this
     * Academic Term. A scope is shared across every Section at that
     * year level (e.g. BSIT-1A and BSIT-1B both fall under the same
     * "IT-2611" scope), so when a later run adds offerings for a new
     * Section in an already-used scope, numbering must continue after
     * the highest existing number rather than restart at 1 and collide
     * with codes another Section already has.
     */
    private function highestExistingSequence(string $scopeKey, AcademicTerm $academicTerm): int
    {
        $maxSuffix = SubjectOffering::where('academic_term_id', $academicTerm->id)
            ->where('edp_code', 'like', $scopeKey . '%')
            ->pluck('edp_code')
            ->map(fn ($code) => (int) substr($code, strlen($scopeKey)))
            ->max();

        return (int) $maxSuffix;
    }
}