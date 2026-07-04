<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeachingAssignmentRequest;
use App\Models\AcademicTerm;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\SubjectOffering;
use App\Models\TeachingAssignment;
use App\Models\User;
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
     *
     * RBAC: Dean and OIC are scoped to their own college — everything
     * handed down here (roster, assignments, offerings) is narrowed to
     * their department_id (plus General Education, which belongs to no
     * department and serves every college). Admin, Registrar, and
     * Assistant Dean oversee every department, so they see everything
     * unscoped — see managerDepartmentId().
     */
    public function index()
    {
        $activeTerm = AcademicTerm::active()->first();

        $departmentId = $this->managerDepartmentId(auth()->user());

        return Inertia::render('TeachingAssignments/Index', [

            'activeTerm' => $activeTerm,

            'faculties' => Faculty::with('department')
                ->when($departmentId, fn ($query) => $query->where(
                    fn ($inner) => $inner->whereNull('department_id')->orWhere('department_id', $departmentId)
                ))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),

            'departments' => Department::where('active', true)
                ->when($departmentId, fn ($query) => $query->where('id', $departmentId))
                ->orderBy('name')
                ->get(),

            // Every Faculty Loading assignment for the active term,
            // with everything the workspace needs to render the
            // "Assigned Subjects" table and compute each faculty
            // member's load — no per-faculty round trips. Scoped to
            // the same faculty set as the roster above, so a Dean
            // never sees assignment data for faculty they can't even
            // select.
            'teachingAssignments' => $activeTerm
                ? TeachingAssignment::with([
                        'subjectOffering.subject',
                        'subjectOffering.section.curriculum.program.department',
                        'faculty',
                    ])
                    ->forTerm($activeTerm->id)
                    ->when($departmentId, fn ($query) => $query->whereHas(
                        'faculty',
                        fn ($inner) => $inner->whereNull('department_id')->orWhere('department_id', $departmentId)
                    ))
                    ->get()
                : [],

            // Subject Offerings for the active term. The Assign
            // Subject modal filters these down to "not yet assigned"
            // client-side. Scoped to the manager's own department's
            // programs — a Dean of CTE has no reason to see CCS's
            // offerings in the Assign Subject list.
            'subjectOfferings' => $activeTerm
                ? SubjectOffering::with([
                        'subject',
                        'section.curriculum.program.department',
                        'curriculumItem',
                    ])
                    ->where('academic_term_id', $activeTerm->id)
                    ->when($departmentId, fn ($query) => $query->whereHas(
                        'section.curriculum.program',
                        fn ($inner) => $inner->where('department_id', $departmentId)
                    ))
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

        $faculty = Faculty::findOrFail($validated['faculty_id']);

        $this->assertManagesFaculty($faculty);

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
        $this->assertManagesFaculty($teachingAssignment->faculty);

        $teachingAssignment->delete();

        return back()->with('success', 'Assignment removed successfully.');
    }

    /**
     * RBAC guard: can the currently logged-in manager touch this
     * particular faculty member's load at all?
     *
     * This is deliberately separate from TeachingAssignmentService's
     * eligibility rules — those decide whether a faculty member CAN
     * teach a given subject; this decides whether the person making the
     * request is even allowed to manage that faculty member in the
     * first place. That's a question about the authenticated user, not
     * about the Faculty/SubjectOffering pair, so it lives here in the
     * controller rather than in the service.
     *
     * Scoped managers (Dean, OIC) may manage:
     *   - faculty in their own department, or
     *   - General Education faculty (department_id is null — they
     *     carry no department of their own and serve every college).
     *
     * Unscoped managers (Admin, Registrar, Assistant Dean) may manage
     * any faculty member — see managerDepartmentId().
     *
     * A 403 here (rather than a soft validation error) is intentional:
     * this is a genuine permission violation, not something the user
     * can correct by picking a different value in the form.
     */
    private function assertManagesFaculty(Faculty $faculty): void
    {
        $departmentId = $this->managerDepartmentId(auth()->user());

        if ($departmentId === null) {
            return;
        }

        if ($faculty->department_id !== null && (int) $faculty->department_id !== $departmentId) {
            abort(403, 'You do not have permission to manage this faculty member\'s load.');
        }
    }

    /**
     * The department a manager is scoped to, or null if they oversee
     * every department. Admin, Registrar, and Assistant Dean always
     * carry a null department_id and are never scoped — mirrors the
     * exact same role list UserController::index() uses to decide
     * whether to show "All Departments" for a user.
     */
    private function managerDepartmentId(User $user): ?int
    {
        if ($user->hasAnyRole(['Admin', 'Registrar', 'Assistant Dean'])) {
            return null;
        }

        return $user->department_id;
    }
}