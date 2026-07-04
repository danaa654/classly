<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateSubjectOfferingRequest;
use App\Models\AcademicTerm;
use App\Models\Program;
use App\Models\Section;
use App\Models\SubjectOffering;
use App\Services\SubjectOfferingGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SubjectOfferingController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SubjectOfferingGeneratorService $generator
    ) {
    }

    /**
     * Controller Middleware
     *
     * Subject Offerings (view, generate, and regenerate) are Admin +
     * Registrar only. Dean, Assistant Dean, and OIC no longer have any
     * access here — they get a 403 if they hit this controller
     * directly, and the Sidebar hides the "Subject Offerings" link for
     * them so they never see the entry point in the first place. The
     * narrower "generate" ability check inside create()/store() below
     * (via SubjectOfferingPolicy::generate()) is currently redundant
     * with this Admin|Registrar gate — it's left in place as
     * belt-and-suspenders in case this controller-level gate is ever
     * widened again later.
     */
    public static function middleware(): array
    {
        return [

            new Middleware(function ($request, $next) {

                abort_unless(
                    auth()->user()->hasAnyRole([
                        'Admin',
                        'Registrar',
                    ]),
                    403,
                    'Unauthorized.'
                );

                return $next($request);

            }),

        ];
    }

    /**
     * Display all Subject Offerings — filtered and searched
     * server-side, defaulting to the currently active Academic Term
     * when no term filter is given.
     *
     * Query string params (all optional):
     *   - academic_term_id
     *   - program_id
     *   - section_id
     *   - status:   Pending | Confirmed | Cancelled
     *   - search:   matches EDP Code, Section Code, Subject Code, or
     *               Subject Title
     */
    public function index(Request $request)
    {
        $academicTermId = $request->input('academic_term_id')
            ?: AcademicTerm::where('active', true)->value('id');

        $programId = $request->input('program_id');
        $sectionId = $request->input('section_id');
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));

        $offerings = SubjectOffering::with([
                'section',
                'subject',
                'faculty',
                'curriculum.program',
                'curriculum.specialization',
            ])
            ->when($academicTermId, function ($query) use ($academicTermId) {
                $query->where('academic_term_id', $academicTermId);
            })
            ->when($programId, function ($query) use ($programId) {
                $query->forProgram($programId);
            })
            ->when($sectionId, function ($query) use ($sectionId) {
                $query->where('section_id', $sectionId);
            })
            ->when(in_array($status, SubjectOffering::STATUSES, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $term = '%' . strtolower($search) . '%';

                $query->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(edp_code) LIKE ?', [$term])
                        ->orWhereHas('section', function ($q) use ($term) {
                            $q->whereRaw('LOWER(section_code) LIKE ?', [$term]);
                        })
                        ->orWhereHas('subject', function ($q) use ($term) {
                            $q->whereRaw('LOWER(subject_code) LIKE ?', [$term])
                                ->orWhereRaw('LOWER(descriptive_title) LIKE ?', [$term]);
                        });
                });
            })
            ->orderBy('edp_code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SubjectOfferings/Index', [

            'offerings' => $offerings,

            'academicTerms' => AcademicTerm::orderByDesc('academic_year')
                ->orderBy('semester')
                ->get(),

            'programs' => Program::where('active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),

            // Scoped to the selected term's/curricula's sections would
            // be nicer, but a flat active-sections list keeps the
            // filter simple and correct in every case (an offering
            // always points at a currently-real Section anyway).
            'sections' => Section::where('status', 'Active')
                ->orderBy('section_code')
                ->get(['id', 'section_code', 'section_name']),

            'filters' => [
                'academic_term_id' => $academicTermId ? (int) $academicTermId : null,
                'program_id' => $programId ? (int) $programId : null,
                'section_id' => $sectionId ? (int) $sectionId : null,
                'status' => in_array($status, SubjectOffering::STATUSES, true) ? $status : null,
                'search' => $search,
            ],

        ]);
    }

    /**
     * Show the "Generate Subject Offerings" form — Academic Term
     * selection only. Each Academic Term carries its existing
     * subject_offerings_count so the frontend can show the
     * replace-or-cancel prompt the moment a term with prior offerings
     * is selected, before the user ever submits.
     */
    public function create()
    {
        abort_unless(auth()->user()->can('generate', SubjectOffering::class), 403, 'Unauthorized.');

        return Inertia::render('SubjectOfferings/Generate', [

            'academicTerms' => AcademicTerm::withCount('subjectOfferings')
                ->orderByDesc('academic_year')
                ->orderBy('semester')
                ->get(),

        ]);
    }

    /**
     * Run generation for the selected Academic Term.
     *
     * If offerings already exist for that term, `replace` must be
     * explicitly true (the Generate page only sends it after the user
     * confirms the prompt) — otherwise this rejects with a validation
     * error rather than silently doing nothing or silently duplicating
     * data. When replace is confirmed, existing offerings for the term
     * are deleted before generating the fresh batch.
     */
    public function store(GenerateSubjectOfferingRequest $request)
    {
        abort_unless(auth()->user()->can('generate', SubjectOffering::class), 403, 'Unauthorized.');

        $validated = $request->validated();

        $academicTerm = AcademicTerm::findOrFail($validated['academic_term_id']);

        $alreadyExists = $this->generator->hasExistingOfferings($academicTerm);

        if ($alreadyExists && empty($validated['replace'])) {
            throw ValidationException::withMessages([
                'academic_term_id' => "Subject Offerings already exist for {$academicTerm->display_name}. Confirm replacing them to continue.",
            ]);
        }

        if ($alreadyExists) {
            $this->generator->deleteExisting($academicTerm);
        }

        $summary = $this->generator->generate($academicTerm, $request->user());

        $message = "{$summary['created']} Subject Offering(s) generated for {$academicTerm->display_name}.";

        if ($summary['skipped_existing'] > 0) {
            $message .= " {$summary['skipped_existing']} item(s) already had an Offering and were left untouched.";
        }

        if ($summary['skipped_unresolved'] > 0) {
            $message .= " {$summary['skipped_unresolved']} item(s) were skipped (missing Specialization code).";
        }

        return redirect()
            ->route('subject-offerings.index', ['academic_term_id' => $academicTerm->id])
            ->with('success', $message);
    }
}