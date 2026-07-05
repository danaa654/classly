<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateSubjectOfferingRequest;
use App\Models\AcademicTerm;
use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Section;
use App\Models\Specialization;
use App\Models\SubjectOffering;
use App\Services\SubjectOfferingGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class SubjectOfferingController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubjectOfferingGeneratorService $generator
    ) {
    }

    /**
     * Everyone with any stake in scheduling can VIEW Subject Offerings
     * — Admin, Registrar, Dean, Assistant Dean, OIC. Generating and
     * Deleting are each gated separately, further down: Generate via
     * SubjectOfferingPolicy::generate() in create()/store() (Admin +
     * Registrar only, unchanged), Delete via an explicit role check
     * in destroy() (Admin + Registrar only). Dean/Assistant Dean/OIC
     * hitting create/store/destroy directly still get a clean 403 —
     * this middleware only clears them past the front door.
     */
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
     * Query string params (all optional):
     *   academic_term_id, program_id, year_level, section_id, status, search
     *
     * status filters on the DERIVED overall_status (see
     * SubjectOffering::getOverallStatusAttribute()) rather than a real
     * column, so it can't be pushed into the SQL where() the other
     * filters use. When it's present, this pulls the (already
     * term/program/section/search-filtered) matches into memory once
     * and paginates that filtered collection by hand — fine at the
     * scale one Academic Term's offerings run at; revisit only if
     * that scale changes materially.
     */
    public function index(Request $request)
    {
        $academicTermId = $request->input('academic_term_id')
            ?: AcademicTerm::where('active', true)->value('id');

        $programId = $request->input('program_id');
        $specializationId = $request->input('specialization_id');
        $yearLevel = $request->input('year_level');
        $sectionId = $request->input('section_id');
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = SubjectOffering::with([
                'section:id,section_code',
                'subject:id,subject_code,descriptive_title',
                'program:id,code',
                'academicTerm:id,status,class_end_date',
                'teachingAssignment',
            ])
            ->when($academicTermId, fn ($q) => $q->where('academic_term_id', $academicTermId))
            ->when($programId, fn ($q) => $q->where('program_id', $programId))
            // Specialization isn't denormalized onto subject_offerings
            // (only curriculum_id/program_id are) — reach through the
            // Curriculum to filter Offerings for a specific
            // specialization (e.g. BSCRIM-FB vs BSCRIM-LD).
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
            })
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

            // Every active Program's Specializations (e.g. BSCRIM's FB/
            // LD/QD/FI) — the frontend only shows this as a filter once
            // a Program that actually has any is selected.
            'specializations' => Specialization::where('active', true)
                ->orderBy('name')
                ->get(['id', 'program_id', 'code', 'name']),

            // program_id/specialization_id are denormalized here (read
            // off each Section's Curriculum) purely so the Index page
            // can narrow the Section dropdown to the selected Program/
            // Specialization client-side, without a round trip.
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

            // Computed once here, same as UserController's
            // is_protected/protected_reason pattern — the Vue page
            // just reads booleans, it never has to know or guess
            // which role names map to which permission.
            'can' => [
                'generate' => auth()->user()->can('generate', SubjectOffering::class),
                'delete' => auth()->user()->hasAnyRole(['Admin', 'Registrar']),
            ],

        ]);
    }

    /**
     * "Generate Subject Offerings" — Academic Term (defaulted to the
     * active one) + Curriculum + which Sections (grouped by Year
     * Level) should be opened. Program/Year Level/Subject are never
     * asked for directly; they're read off the Curriculum + its
     * Sections once the registrar clicks Generate.
     */
    public function create()
    {
        abort_unless(auth()->user()->can('generate', SubjectOffering::class), 403, 'Unauthorized.');

        return Inertia::render('SubjectOfferings/Generate', [

            'academicTerms' => AcademicTerm::orderByDesc('academic_year')->orderBy('semester')->get(),

            'activeAcademicTermId' => AcademicTerm::where('active', true)->value('id'),

            // Sections nested under each Curriculum, grouped by Year
            // Level on the frontend — kept as one payload (no extra
            // route) since a school's Curriculum+Section counts are
            // small enough for this to stay cheap.
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

        $summary = $this->generator->generate(
            $academicTerm,
            $curriculum,
            $validated['section_ids'],
            $request->user()
        );

        $label = "{$curriculum->display_name} — {$academicTerm->display_name}";

        // Nothing new was created. Two distinct reasons why, each with
        // its own toast so the registrar isn't told "0 generated" with
        // no explanation:
        //   1. Every matching pair already existed — this Curriculum
        //      (for these Sections) was already generated earlier.
        //   2. Nothing matched at all, or every match was unresolved
        //      (e.g. missing Specialization code) — a real problem,
        //      not just a harmless re-run.
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

        return redirect()
            ->route('subject-offerings.index', ['academic_term_id' => $academicTerm->id])
            ->with('success', $message);
    }

    /**
     * Blocked once Faculty Loading has touched this offering — mirrors
     * Section::isInUse()'s guard.
     */
    /**
     * Deleting is its own permission, separate from Generate — Admin
     * + Registrar only. Dean/Assistant Dean/OIC can view this page
     * (see middleware() above) but hitting this action directly still
     * 403s for them; the Delete button is hidden for them in the UI
     * via the 'can.delete' prop from index(), this check is what
     * actually enforces it.
     */
    public function destroy(SubjectOffering $subjectOffering)
    {
        abort_unless(
            auth()->user()->hasAnyRole(['Admin', 'Registrar']),
            403,
            'You do not have permission to delete Subject Offerings.'
        );

        if ($subjectOffering->teachingAssignment()->exists()) {
            return back()->with('error', "{$subjectOffering->edp_code} already has a Faculty assignment and cannot be deleted.");
        }

        $edpCode = $subjectOffering->edp_code;
        $subjectOffering->delete();

        return back()->with('success', "{$edpCode} deleted.");
    }
}