<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateWeeklyHoursRequest;
use App\Http\Requests\GenerateSubjectOfferingRequest;
use App\Models\AcademicTerm;
use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Section;
use App\Models\Specialization;
use App\Models\SubjectOffering;
use App\Services\SchedulingWorkspaceService;
use App\Services\SubjectOfferingGeneratorService;
use App\Services\AuditLogService;
use App\Services\ActivityHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubjectOfferingController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubjectOfferingGeneratorService $generator,
        private readonly SchedulingWorkspaceService $workspace
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
        ];
    }

    /**
     * Shared filter logic used by both index() (paginated, Inertia)
     * and print() (unpaginated, Blade). Keeping this in one place
     * means the Print button always reflects exactly what's on
     * screen — same academic_term_id/program_id/specialization_id/
     * year_level/section_id/search, applied the same way.
     *
     * NOTE: deliberately does NOT apply the `status` filter — status
     * is derived in PHP after the query runs (see index()), and the
     * print view has no use for it anyway since it never shows
     * Faculty/Room/Status columns.
     */
    private function filteredOfferingsQuery(Request $request)
    {
        $academicTermId = $request->input('academic_term_id')
            ?: $this->workspace->getTermForUser(auth()->user())?->id;

        $programId = $request->input('program_id');
        $specializationId = $request->input('specialization_id');
        $yearLevel = $request->input('year_level');
        $sectionId = $request->input('section_id');
        $search = trim((string) $request->input('search', ''));

        return SubjectOffering::with([
                'section:id,section_code',
                'subject:id,subject_code,descriptive_title',
                'program:id,code',
                'academicTerm:id,status,class_end_date',
                'teachingAssignment',
                // Added alongside the fix in SubjectOffering.php:
                // overall_status/room_status now read these two
                // relations in-memory when eager-loaded, instead of
                // firing a raw query per offering. index() below calls
                // ->get()->filter(...) against overall_status whenever
                // a status filter is applied — that used to mean one
                // to three extra queries PER OFFERING on top of the
                // full unpaginated fetch already required to filter on
                // a derived value.
                'schedule',
                'preferredByRooms',
            ])
            ->when($academicTermId, fn ($q) => $q->where('academic_term_id', $academicTermId))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            ->when($specializationId, fn ($q) => $q->whereHas(
                'curriculum',
                fn ($c) => $c->where('specialization_id', $specializationId)
            ))
            ->when($yearLevel, fn ($q) => $q->where('year_level', $yearLevel))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . strtolower($search) . '%';

                $query->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(edp_code) LIKE ?', [$term])
                        ->orWhereHas('section', fn ($q) => $q->whereRaw('LOWER(section_code) LIKE ?', [$term]))
                        ->orWhereHas('subject', function ($q) use ($term) {
                            $q->whereRaw('LOWER(subject_code) LIKE ?', [$term])
                                ->orWhereRaw('LOWER(descriptive_title) LIKE ?', [$term]);
                        });
                });
            });
    }

    public function index(Request $request)
    {
        $academicTermId = $request->input('academic_term_id')
            ?: $this->workspace->getTermForUser(auth()->user())?->id;

        $programId = $request->input('program_id');
        $specializationId = $request->input('specialization_id');
        $yearLevel = $request->input('year_level');
        $sectionId = $request->input('section_id');
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = $this->filteredOfferingsQuery($request)
            ->orderBy('year_level')
            ->orderBy('edp_code');

        if (in_array($status, SubjectOffering::STATUSES, true)) {
            $matching = $query->get()->filter(fn ($offering) => $offering->overall_status === $status)->values();

            $offerings = new \Illuminate\Pagination\LengthAwarePaginator(
                $matching->forPage($page, $perPage)->values(),
                $matching->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $offerings = $query->paginate($perPage)->withQueryString();
        }

        return Inertia::render('SubjectOfferings/Index', [

            'offerings' => $offerings,

            'academicTerms' => AcademicTerm::orderByDesc('academic_year')->orderBy('semester')->get(),

            'programs' => Program::where('active', true)->orderBy('name')->get(['id', 'code', 'name']),

            'specializations' => Specialization::where('active', true)
                ->orderBy('name')
                ->get(['id', 'program_id', 'code', 'name']),

            'sections' => Section::with('curriculum:id,program_id,specialization_id')
                ->where('status', 'Active')
                ->orderBy('section_code')
                ->get(['id', 'section_code', 'section_name', 'curriculum_id', 'year_level'])
                ->map(fn ($section) => [
                    'id' => $section->id,
                    'section_code' => $section->section_code,
                    'section_name' => $section->section_name,
                    'year_level' => $section->year_level,
                    'program_id' => $section->curriculum?->program_id,
                    'specialization_id' => $section->curriculum?->specialization_id,
                ])
                ->values(),

            'statuses' => SubjectOffering::STATUSES,

            'filters' => [
                'academic_term_id' => $academicTermId ? (int) $academicTermId : null,
                'program_id' => $programId ? (int) $programId : null,
                'specialization_id' => $specializationId ? (int) $specializationId : null,
                'year_level' => $yearLevel ? (int) $yearLevel : null,
                'section_id' => $sectionId ? (int) $sectionId : null,
                'status' => in_array($status, SubjectOffering::STATUSES, true) ? $status : null,
                'search' => $search,
            ],

            'can' => [
                'generate' => auth()->user()->can('generate', SubjectOffering::class),
                'delete' => auth()->user()->hasAnyRole(['Admin', 'Registrar']),
                // Dean/Assistant Dean/OIC can see Weekly Hours (it's
                // already in the Hours column below) but never select
                // rows or open the Bulk Update modal — same tier as
                // `delete` above, not the broader "can view this page
                // at all" tier the route middleware already enforces.
                'bulkUpdateWeeklyHours' => auth()->user()->hasAnyRole(['Admin', 'Registrar']),
            ],

        ]);
    }

    /**
     * Printable Class List — a partial-list handout for posting
     * before enrollment: "which Subjects is BSIT 1-A taking this
     * term," grouped by Section, with no Faculty/Room/Time/Status
     * columns since none of that exists yet at this stage.
     *
     * Reuses index()'s exact filters (minus `status`, which doesn't
     * apply here) so the printed list always matches whatever the
     * Registrar/Dean currently has on screen. Deliberately NOT
     * paginated — a posted class list needs every matching row, not
     * page 1 of 20.
     *
     * Returns a plain Blade view (not Inertia) so it opens cleanly in
     * its own tab and the browser's native "Print > Save as PDF"
     * works without any extra PDF library.
     */
    public function print(Request $request)
    {
        $academicTermId = $request->input('academic_term_id')
            ?: $this->workspace->getTermForUser(auth()->user())?->id;

        $academicTerm = $academicTermId ? AcademicTerm::find($academicTermId) : null;

        $offerings = $this->filteredOfferingsQuery($request)
            ->orderBy('year_level')
            ->get();

        // Group by Section so the printout reads "BSIT 1-A" as a
        // header with its Subjects listed underneath, rather than one
        // long flat table repeating the Section on every row.
        $sections = $offerings
            ->groupBy(fn ($offering) => $offering->section_id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'section_code' => $first->section?->section_code ?? 'Unassigned Section',
                    'year_level' => $first->year_level,
                    'program_code' => $first->program?->code,
                    'offerings' => $group->sortBy(fn ($o) => $o->subject?->subject_code)->values(),
                ];
            })
            ->sortBy(['year_level', 'section_code'])
            ->values();

        return view('subject-offerings.print', [
            'academicTerm' => $academicTerm,
            'sections' => $sections,
            'generatedAt' => now(),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('generate', SubjectOffering::class), 403, 'Unauthorized.');

        return Inertia::render('SubjectOfferings/Generate', [

            'academicTerms' => AcademicTerm::orderByDesc('academic_year')->orderBy('semester')->get(),

            // Generate Subject Offerings is a scheduling module — it
            // pre-selects the Planning Academic Term, not the Active
            // one, so a Registrar preparing next semester's offerings
            // doesn't have to hunt for the right term in the list.
            'planningAcademicTermId' => $this->workspace->getTermForUser(auth()->user())?->id,

            'curriculums' => Curriculum::with('program', 'specialization')
                ->where('active', true)
                ->get()
                ->map(fn ($curriculum) => [
                    'id' => $curriculum->id,
                    'display_name' => $curriculum->display_name,
                    'has_items' => $curriculum->has_items,
                    'sections' => Section::where('curriculum_id', $curriculum->id)
                        ->where('status', 'Active')
                        ->orderBy('year_level')
                        ->orderBy('section_letter')
                        ->get(['id', 'section_code', 'section_name', 'year_level']),
                ])
                ->values(),

        ]);
    }

    public function store(GenerateSubjectOfferingRequest $request)
    {
        abort_unless(auth()->user()->can('generate', SubjectOffering::class), 403, 'Unauthorized.');

        $validated = $request->validated();

        $academicTerm = AcademicTerm::findOrFail($validated['academic_term_id']);
        $curriculum = Curriculum::with('program', 'specialization')->findOrFail($validated['curriculum_id']);

        $this->workspace->assertWritable($academicTerm);

        $summary = $this->generator->generate(
            $academicTerm,
            $curriculum,
            $validated['section_ids'],
            $request->user()
        );

        $label = "{$curriculum->display_name} — {$academicTerm->display_name}";

        if ($summary['created'] === 0) {
            if ($summary['skipped_existing'] > 0 && $summary['skipped_unresolved'] === 0) {
                return redirect()
                    ->route('subject-offerings.index', ['academic_term_id' => $academicTerm->id])
                    ->with('warning', "{$label} has already been generated — no new Subject Offerings were created.");
            }

            $message = "No Subject Offerings were generated for {$label}.";

            if ($summary['skipped_unresolved'] > 0) {
                $message .= " {$summary['skipped_unresolved']} item(s) could not be generated (missing Specialization code).";
            }

            return redirect()
                ->route('subject-offerings.index', ['academic_term_id' => $academicTerm->id])
                ->with('error', $message);
        }

        $message = "{$summary['created']} Subject Offering(s) generated for {$label}.";

        if ($summary['skipped_existing'] > 0) {
            $message .= " {$summary['skipped_existing']} already existed and were left untouched.";
        }

        if ($summary['skipped_unresolved'] > 0) {
            $message .= " {$summary['skipped_unresolved']} could not be generated (missing Specialization code).";
        }

        AuditLogService::log(
            action: 'generated',
            module: 'Subject Offering',
            model: $academicTerm,
            description: "Generated {$summary['created']} Subject Offering(s) for {$label}",
            newValues: [
                'curriculum' => $curriculum->display_name,
                'academic_term' => $academicTerm->display_name,
                'sections' => count($validated['section_ids']),
                'created' => $summary['created'],
            ],
            recordName: $label,
        );

        // Activity History milestone — the Timeline cares about "how
        // many classes got imported for this term," not the row-level
        // detail Audit Logs already captures above.
        ActivityHistoryService::recordSubjectOfferingsGenerated(
            $academicTerm,
            $summary['created'],
            ['curriculum' => $curriculum->display_name]
        );

        return redirect()
            ->route('subject-offerings.index', ['academic_term_id' => $academicTerm->id])
            ->with('success', $message);
    }

    public function destroy(SubjectOffering $subjectOffering)
    {
        abort_unless(
            auth()->user()->hasAnyRole(['Admin', 'Registrar']),
            403,
            'You do not have permission to delete Subject Offerings.'
        );

        $this->workspace->assertWritable($subjectOffering->academicTerm);

        if ($subjectOffering->teachingAssignment()->exists()) {
            return back()->with('error', "{$subjectOffering->edp_code} already has a Faculty assignment and cannot be deleted.");
        }

        $edpCode = $subjectOffering->edp_code;
        $subjectOffering->delete();

        AuditLogService::log(
            action: 'deleted',
            module: 'Subject Offering',
            description: "Deleted subject offering {$edpCode}",
            oldValues: ['edp_code' => $edpCode],
            recordName: $edpCode,
        );

        return back()->with('success', "{$edpCode} deleted.");
    }

    /**
     * Bulk Update Weekly Hours — lets a Registrar/Admin override the
     * per-Term weekly hours for a hand-picked set of Subject
     * Offerings (e.g. "schedule Programming 1 at 4 hrs/week instead
     * of the curriculum's 5 for this Section, this Term only"),
     * without ever touching the Subject master, the Curriculum, or
     * the Prospectus — see SubjectOffering::getHoursAttribute() for
     * the read side that makes this override visible to Session
     * Settings / the Greedy Scheduler / the Master Grid.
     *
     * A JSON endpoint (not an Inertia redirect) on purpose, same
     * convention as MasterGridController's axios-driven actions —
     * the Index page reloads just the `offerings` prop afterward so
     * filters/sorting/pagination are preserved instead of a full
     * navigation resetting them.
     */
    public function bulkUpdateWeeklyHours(BulkUpdateWeeklyHoursRequest $request): JsonResponse
    {
        abort_unless(
            auth()->user()->hasAnyRole(['Admin', 'Registrar']),
            403,
            'Only Admin and Registrar can perform Bulk Update Weekly Hours.'
        );

        $validated = $request->validated();

        $offerings = SubjectOffering::with([
                'academicTerm',
                'subject:id,subject_code,descriptive_title',
                'section:id,section_code',
                'program:id,code',
            ])
            ->whereIn('id', $validated['subject_offering_ids'])
            ->get();

        abort_if($offerings->isEmpty(), 404, 'No matching Subject Offerings were found.');

        // A mixed selection spanning more than one Academic Term must
        // never partially apply — assertWritable() throws on the
        // first Archived/locked term it finds, before any row is
        // touched, same guard store()/destroy() already use above.
        $offerings->pluck('academicTerm')->filter()->unique('id')->each(
            fn (AcademicTerm $term) => $this->workspace->assertWritable($term)
        );

        $newHours = (int) $validated['hours'];

        $changes = [];

        DB::transaction(function () use ($offerings, $newHours, &$changes) {
            foreach ($offerings as $offering) {
                $changes[] = [
                    'edp_code' => $offering->edp_code,
                    // getRawOriginal bypasses the fallback accessor
                    // (SubjectOffering::getHoursAttribute) so the log
                    // always reflects this row's actual previous
                    // value, not a value borrowed from the Subject
                    // master.
                    'from' => $offering->getRawOriginal('hours'),
                    'to' => $newHours,
                ];

                // ONLY the `hours` column on THIS row. subject_id,
                // curriculum_id, curriculum_item_id, and every other
                // field are left untouched — this is the one write
                // path for the feature, and it never reaches the
                // Subject master, Curriculum, or Prospectus.
                $offering->update(['hours' => $newHours]);
            }
        });

        $term = $offerings->first()->academicTerm;

        $fromValues = collect($changes)->pluck('from')->unique()->filter(fn ($v) => $v !== null)->values();
        $fromLabel = $fromValues->isEmpty()
            ? '—'
            : ($fromValues->count() === 1 ? (string) $fromValues->first() : $fromValues->join(', '));

        $label = $term?->display_name ?? 'Multiple Terms';

        AuditLogService::log(
            action: 'updated',
            module: 'Subject Offering',
            model: $term,
            description: "Bulk updated Weekly Hours for {$offerings->count()} Subject Offering(s)",
            oldValues: ['weekly_hours' => $fromLabel],
            newValues: [
                'weekly_hours' => $newHours,
                'affected_subject_offerings' => $offerings->count(),
                'edp_codes' => $offerings->pluck('edp_code')->values(),
            ],
            recordName: $label,
        );

        // Activity History milestone. NOTE: ActivityHistoryService
        // needs a small addition to support this — see
        // recordBulkWeeklyHoursUpdated() below, mirroring the shape
        // of the existing recordSubjectOfferingsGenerated() /
        // recordScheduleManuallyAdjusted() wrapper methods.
        if ($term) {
            ActivityHistoryService::recordBulkWeeklyHoursUpdated(
                $term,
                $offerings->count(),
                [
                    'program' => $offerings->first()->program?->code,
                    'year_level' => $offerings->first()->year_level,
                    'section' => $offerings->first()->section?->section_code,
                    'weekly_hours' => "{$fromLabel} → {$newHours}",
                ]
            );
        }

        return response()->json([
            'message' => "{$offerings->count()} Subject Offering(s) updated to {$newHours} weekly hours.",
            'updated_count' => $offerings->count(),
        ]);
    }
}