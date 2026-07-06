<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use App\Services\GreedyScheduleService;
use App\Services\MasterGridDataService;
use App\Services\ScheduleRecommendationService;
use App\Services\ScheduleValidationService;
use App\Services\SchedulingWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

/**
 * The Master Grid Scheduling Workspace.
 *
 * index() is a READ-ONLY visual workspace. generate() runs the Greedy
 * Scheduling Algorithm and returns an in-memory preview. Phase 2 adds
 * the Interactive Schedule Review loop on top of that same preview:
 *
 *   validateBlock() — real-time conflict check for a single edited
 *                     block, called by the Edit Schedule modal on
 *                     every field change.
 *   save()          — re-validates the WHOLE preview and, only if it
 *                     is 100% conflict-free, writes every block to the
 *                     `schedules` table in one transaction. It also
 *                     keeps `teaching_assignments` in sync (see save()
 *                     below) so the Faculty Loading workspace reflects
 *                     whatever faculty the Greedy Scheduler (or a
 *                     manual edit) ultimately settled on for each
 *                     offering — Faculty Loading and Master Grid share
 *                     the same subject_offering_id, and neither table
 *                     should be able to drift from the other.
 *
 * None of these three actions contain business logic themselves — see
 * GreedyScheduleService (generation), ScheduleValidationService
 * (conflict rules), and ScheduleRecommendationService (suggested
 * alternatives). This controller only translates HTTP <-> services.
 */
class MasterGridController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly MasterGridDataService $data,
        private readonly GreedyScheduleService $greedy,
        private readonly ScheduleValidationService $validator,
        private readonly ScheduleRecommendationService $recommender,
        private readonly SchedulingWorkspaceService $workspace,
    ) {
    }

    public static function middleware(): array
    {
        return [

            new Middleware(function ($request, $next) {

                abort_unless(
                    auth()->user()->hasAnyRole([
                        'Admin',
                        'Registrar',
                        'Dean',
                        'Assistant Dean',
                        'OIC',
                    ]),
                    403,
                    'Unauthorized.'
                );

                return $next($request);

            }),

            // Generate / Validate / Save all require the same
            // Admin/Registrar-only restriction — reviewing and
            // committing a schedule is the same privileged action the
            // spec restricts to "When the Registrar or Admin clicks
            // Generate Schedule...".
            new Middleware(function ($request, $next) {

                if ($request->routeIs('master-grid.generate', 'master-grid.validate-block', 'master-grid.save')) {
                    abort_unless(
                        auth()->user()->hasAnyRole(['Admin', 'Registrar']),
                        403,
                        'Only Admin or Registrar can generate or save a schedule.'
                    );
                }

                return $next($request);

            }),

        ];
    }

    public function index()
    {
        // Admin/Registrar see the Planning Academic Term here (so they
        // can lay out next semester's grid ahead of time); Dean/
        // Assistant Dean/OIC always see the Active Academic Term —
        // see SchedulingWorkspaceService::getTermForUser().
        $term = $this->workspace->getTermForUser(auth()->user());

        return Inertia::render('MasterGrid/Index', $this->data->build($term));
    }

    /**
     * Runs the Greedy Scheduling Algorithm for one Department + Program
     * + [Specialization] + Year Level + Section and returns a draft
     * preview. Nothing is written to the database.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'specialization_id' => ['nullable', 'integer', 'exists:specializations,id'],
            'year_level' => ['required', 'integer', 'min:1', 'max:4'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ]);

        $planningTerm = $this->workspace->getTermForUser(auth()->user());

        abort_unless($planningTerm, 422, 'No Planning Academic Term is set — configure one in Settings > Scheduling Workspace before generating a schedule.');

        // Generate produces an in-memory preview only (nothing is
        // persisted here), but blocking it up front — rather than only
        // at Save — means Admin/Registrar reviewing an Archived term
        // never gets led through a full Generate flow just to be
        // stopped at the last step.
        $this->workspace->assertWritable($planningTerm);

        $result = $this->greedy->generateForSection($planningTerm, $validated);

        return response()->json($result);
    }

    /**
     * Real-time conflict check for ONE edited block, called by the
     * Edit Schedule modal on every field change (Faculty/Room/Day/
     * Start/End). $request->blocks is the FULL current in-memory
     * preview (including the edited block) so the validator can check
     * it against every sibling block, not just the one being edited.
     * If any conflict is found, also returns suggested alternatives
     * from ScheduleRecommendationService.
     */
    public function validateBlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'block' => ['required', 'array'],
            'blocks' => ['required', 'array'],
        ]);

        $planningTerm = $this->workspace->getTermForUser(auth()->user());

        abort_unless($planningTerm, 422, 'No Planning Academic Term is set. Configure one in Settings > Scheduling Workspace.');

        $allBlocks = collect($validated['blocks']);
        $block = $validated['block'];

        $outcome = $this->validator->validateBlock($block, $allBlocks, $planningTerm);

        $recommendations = null;

        if (! empty($outcome['conflicts'])) {
            $recommendations = $this->recommender->recommend($block, $allBlocks, $planningTerm);
        }

        return response()->json([
            'conflicts' => $outcome['conflicts'],
            'warnings' => $outcome['warnings'],
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Re-validates every block in the submitted preview and, only if
     * NONE of them have a conflict, inserts them all into `schedules`
     * inside one transaction — all-or-nothing. If even one block still
     * conflicts (e.g. a race with another user's generate/save), the
     * whole request is rejected and the conflicting blocks are
     * returned so the frontend can highlight them, matching the spec's
     * "Do not save. Highlight all conflicting schedule blocks."
     *
     * Alongside every Schedule row, this also upserts the matching
     * `teaching_assignments` row for that offering (see
     * syncTeachingAssignment() below). Without this, a class the
     * Greedy Scheduler placed on the grid — faculty, room, day, and
     * time all decided — would show up on Master Grid but not on the
     * Faculty Loading workspace, since that workspace has always read
     * exclusively from `teaching_assignments`, never from `schedules`.
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blocks' => ['required', 'array', 'min:1'],
        ]);

        $planningTerm = $this->workspace->getTermForUser(auth()->user());

        abort_unless($planningTerm, 422, 'No Planning Academic Term is set. Configure one in Settings > Scheduling Workspace.');

        $this->workspace->assertWritable($planningTerm);

        $blocks = collect($validated['blocks']);

        $conflictsByOffering = $this->validator->validateAll($blocks, $planningTerm);

        if (! empty($conflictsByOffering)) {
            return response()->json([
                'message' => 'One or more schedule blocks have conflicts. Nothing was saved.',
                'conflicts' => $conflictsByOffering,
            ], 422);
        }

        try {
            DB::transaction(function () use ($blocks, $planningTerm) {
                foreach ($blocks as $block) {
                    Schedule::updateOrCreate(
                        ['subject_offering_id' => $block['subject_offering_id']],
                        [
                            'academic_term_id' => $planningTerm->id,
                            'faculty_id' => $block['faculty_id'] ?? null,
                            'room_id' => $block['room_id'],
                            'day' => $block['day'],
                            'start_minutes' => $block['start_minutes'],
                            'end_minutes' => $block['end_minutes'],
                            'created_by' => auth()->id(),
                        ]
                    );

                    $this->syncTeachingAssignment($block);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to save the schedule. No changes were committed.',
            ], 500);
        }

        return response()->json([
            'message' => 'Schedule generated successfully.',
        ]);
    }

    /**
     * Keeps `teaching_assignments` in step with whatever faculty a
     * saved Schedule block ends up carrying, so Faculty Loading's
     * "Assigned Subjects" table and unit totals never silently fall
     * out of sync with what Master Grid actually committed.
     *
     * Three cases, keyed off subject_offering_id (the same column
     * `teaching_assignments` uniquely indexes on):
     *
     *   1. No faculty on the block — nothing to sync. A Teaching
     *      Assignment made through the Faculty Loading workspace
     *      itself is left untouched; Master Grid never invents a
     *      faculty member that wasn't actually placed on the grid.
     *   2. A Teaching Assignment for this offering already names the
     *      SAME faculty — nothing to do, it's already correct.
     *   3. Otherwise — create or repoint the assignment to the
     *      faculty Master Grid saved. This deliberately covers both
     *      "no assignment existed yet" (the Greedy Scheduler picked a
     *      faculty member directly) and "a different faculty was
     *      assigned before" (a manual Edit Schedule change should win,
     *      since it's the more recent, more specific decision).
     *
     * updateOrCreate() targets subject_offering_id only (not
     * +faculty_id), so re-saving the same offering with a new faculty
     * updates the existing row instead of violating the unique
     * constraint on subject_offering_id by inserting a second one.
     */
    private function syncTeachingAssignment(array $block): void
    {
        $facultyId = $block['faculty_id'] ?? null;

        if (! $facultyId) {
            return;
        }

        $existing = TeachingAssignment::where('subject_offering_id', $block['subject_offering_id'])->first();

        if ($existing && (int) $existing->faculty_id === (int) $facultyId) {
            return;
        }

        TeachingAssignment::updateOrCreate(
            ['subject_offering_id' => $block['subject_offering_id']],
            [
                'faculty_id' => $facultyId,
                'active' => true,
            ]
        );
    }
}