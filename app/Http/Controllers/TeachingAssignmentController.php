<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeachingAssignmentRequest;
use App\Models\AcademicTerm;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\SubjectOffering;
use App\Models\TeachingAssignment;
use App\Services\TeachingAssignmentService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class TeachingAssignmentController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly TeachingAssignmentService $service
    ) {
    }

    /**
     * Controller Middleware
     *
     * Faculty Loading follows the same permission tier as Faculty,
     * Subjects, and Rooms.
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
     * Display the Faculty Loading workspace.
     *
     * A searchable faculty roster on the left, and — once a faculty
     * member is selected — their load summary, assigned Subject
     * Offerings, and an "Assign Subject" action on the right.
     *
     * Faculty are assigned directly to Subject Offerings, not to a
     * bare Subject — an Offering already carries its own program,
     * year level, section, and academic term, so there's no separate
     * "which section/curriculum item" step to resolve. Which
     * offerings are still unassigned is answered client-side by
     * cross-referencing subjectOfferings against teachingAssignments
     * (an offering is unassigned when no Teaching Assignment exists
     * for its subject_offering_id — the same column the
     * teaching_assignments unique index already protects).
     *
     * Eligibility to teach is decided purely by Faculty Scope +
     * Department + Subject Category (Major/Minor) — see
     * TeachingAssignmentService. There is no Faculty Subject
     * qualification list anywhere in this module anymore.
     */
    public function index()
    {
        $activeTerm = AcademicTerm::active()->first();

        return Inertia::render('TeachingAssignments/Index', [

            'activeTerm' => $activeTerm,

            'faculties' => Faculty::with('department')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),

            'departments' => Department::where('active', true)
                ->orderBy('name')
                ->get(),

            // Every Faculty Loading assignment for the active term,
            // with everything the workspace needs to render the
            // "Assigned Subjects" table and compute each faculty
            // member's load — no per-faculty round trips.
            'teachingAssignments' => $activeTerm
                ? TeachingAssignment::with([
                        'subjectOffering.subject',
                        'subjectOffering.section.curriculum.program.department',
                        'faculty',
                    ])
                    ->forTerm($activeTerm->id)
                    ->get()
                : [],

            // Subject Offerings for the active term. The Assign
            // Subject modal filters these down to "not yet assigned"
            // client-side.
            'subjectOfferings' => $activeTerm
                ? SubjectOffering::with([
                        'subject',
                        'section.curriculum.program.department',
                        'curriculumItem',
                    ])
                    ->where('academic_term_id', $activeTerm->id)
                    ->get()
                : [],

        ]);
    }

    /**
     * Assign a faculty member to a Subject Offering.
     */
    public function store(TeachingAssignmentRequest $request)
    {
        $validated = $request->validated();

        $this->service->assertBusinessRules($validated);

        TeachingAssignment::create($validated);

        return redirect()
            ->route('teaching-assignments.index')
            ->with('success', 'Faculty load assigned successfully.');
    }

    /**
     * Remove a faculty member's assignment to a Subject Offering.
     */
    public function destroy(TeachingAssignment $teachingAssignment)
    {
        $teachingAssignment->delete();

        return back()->with('success', 'Assignment removed successfully.');
    }
}