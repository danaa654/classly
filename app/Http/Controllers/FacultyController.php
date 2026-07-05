<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\AcademicTerm;
use App\Models\SubjectOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class FacultyController extends Controller implements HasMiddleware
{

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
                        'OIC'
                    ]),
                    403,
                    'Unauthorized.'
                );

                return $next($request);
            }),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Faculty/Index', [
            'faculties' => Faculty::with('department')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Faculty/Create', [
            'departments' => Department::where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        // Faculty Scope drives whether a department is applicable.
        // General Education faculty are never tied to a department,
        // so we normalize department_id to null before validating —
        // regardless of what the form happens to submit.
        $request->merge([
            'department_id' => $request->input('faculty_scope') === 'general'
                ? null
                : $request->input('department_id'),
        ]);

        $validated = $request->validate([

            // Personal Information
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'last_name' => [
                'required',
                'string',
                'max:255',
            ],

            'suffix' => [
                'nullable',
                'string',
                'max:20',
            ],

            'gender' => [
                'nullable',
                'in:Male,Female',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:faculties,email',
            ],

            // Faculty Scope
            'faculty_scope' => [
                'required',
                'in:general,departmental,cross_department',
            ],

            // Department — required for Departmental and Cross
            // Department scope, must be null for General Education.
            'department_id' => [
                Rule::requiredIf(fn () => $request->input('faculty_scope') !== 'general'),
                Rule::prohibitedIf(fn () => $request->input('faculty_scope') === 'general'),
                'nullable',
                'exists:departments,id',
            ],

            // Employment
            'employment_type' => [
                'required',
                'in:Full-Time,Part-Time',
            ],

            // Teaching Load
            'max_units' => [
                'required',
                'integer',
                'min:1',
                'max:24',
            ],

            // Status
            'status' => [
                'required',
                'boolean',
            ],

        ]);

        Faculty::create($validated);

        return redirect()
            ->route('faculty.index')
            ->with('success', 'Faculty member added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Faculty $faculty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Faculty $faculty)
    {
        return Inertia::render('Faculty/Edit', [

            'faculty' => $faculty,

            'departments' => Department::where('active', true)
                ->orderBy('name')
                ->get(),

        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Faculty $faculty)
    {
        // Same normalization as store() — General Education always
        // clears the department, no matter what the client sent.
        $request->merge([
            'department_id' => $request->input('faculty_scope') === 'general'
                ? null
                : $request->input('department_id'),
        ]);

        $validated = $request->validate([

            // Personal Information
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'last_name' => [
                'required',
                'string',
                'max:255',
            ],

            'suffix' => [
                'nullable',
                'string',
                'max:20',
            ],

            'gender' => [
                'nullable',
                'in:Male,Female',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('faculties', 'email')->ignore($faculty->id),
            ],

            // Faculty Scope
            'faculty_scope' => [
                'required',
                'in:general,departmental,cross_department',
            ],

            // Department — required for Departmental and Cross
            // Department scope, must be null for General Education.
            'department_id' => [
                Rule::requiredIf(fn () => $request->input('faculty_scope') !== 'general'),
                Rule::prohibitedIf(fn () => $request->input('faculty_scope') === 'general'),
                'nullable',
                'exists:departments,id',
            ],

            // Employment
            'employment_type' => [
                'required',
                'in:Full-Time,Part-Time',
            ],

            // Teaching Load
            'max_units' => [
                'required',
                'integer',
                'min:1',
                'max:24',
            ],

            // Status
            'status' => [
                'required',
                'boolean',
            ],

        ]);

        $faculty->update($validated);

        return redirect()
            ->route('faculty.index')
            ->with('success', 'Faculty member updated successfully.');
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Faculty $faculty)
    {
        $faculty->delete();

        return redirect()
            ->route('faculty.index')
            ->with('success', 'Faculty member deleted successfully.');
    }

    /**
     * Manage Subjects data for a single Faculty member — returned as
     * plain JSON, not an Inertia page render. Direct mirror of
     * RoomController::manageSubjects(): this is purely the data source
     * for a "Manage Subjects" MODAL on Faculty/Index.vue. The Index
     * page fetches it via axios when a faculty's "Manage Subjects"
     * button is clicked, opens the modal client-side, and never
     * navigates away from Index — filters and scroll position stay
     * exactly as they were.
     *
     * Unlike Room, there is no "Room Type" to pre-filter offerings by —
     * every active-term Subject Offering is a candidate, annotated with
     * is_recommended (Faculty Scope eligibility, informational only —
     * see isEligible()) so the UI can visually group/sort, without
     * blocking a deliberate off-scope preference the same way Room's
     * "unrecommended" preferences are still allowed.
     */
    public function manageSubjects(Faculty $faculty)
    {
        $activeTerm = AcademicTerm::where('active', true)->first();

        $offerings = collect();

        if ($activeTerm) {

            $preferredIds = $faculty->preferredSubjectOfferings()
                ->where('subject_offerings.academic_term_id', $activeTerm->id)
                ->pluck('subject_offerings.id');

            $baseOfferings = SubjectOffering::with([
                    'subject:id,subject_code,descriptive_title,is_major,units',
                    'program:id,code',
                    'section:id,section_code,curriculum_id',
                    'section.curriculum:id,program_id',
                    'section.curriculum.program:id,department_id',
                ])
                ->where('academic_term_id', $activeTerm->id)
                ->orderBy('edp_code')
                ->get();

            $claimedByOtherFaculty = DB::table('faculty_subject_offering')
                ->join('faculties', 'faculties.id', '=', 'faculty_subject_offering.faculty_id')
                ->whereIn('faculty_subject_offering.subject_offering_id', $baseOfferings->pluck('id'))
                ->where('faculty_subject_offering.faculty_id', '!=', $faculty->id)
                ->get(['faculty_subject_offering.subject_offering_id', 'faculties.first_name', 'faculties.last_name'])
                ->mapWithKeys(fn ($row) => [
                    $row->subject_offering_id => trim("{$row->first_name} {$row->last_name}"),
                ]);

            $offerings = $baseOfferings
                ->map(function (SubjectOffering $offering) use ($faculty, $preferredIds, $claimedByOtherFaculty) {
                    return [
                        'id' => $offering->id,
                        'edp_code' => $offering->edp_code,
                        'subject_code' => $offering->subject?->subject_code,
                        'subject_title' => $offering->subject?->descriptive_title,
                        'program_code' => $offering->program?->code,
                        'year_level' => $offering->year_level,
                        'section_code' => $offering->section?->section_code,
                        'units' => $offering->units,
                        'classification' => $offering->classification,
                        'is_preferred' => $preferredIds->contains($offering->id),
                        'is_recommended' => $this->isEligible($faculty, $offering),
                        'claimed_by_faculty_name' => $claimedByOtherFaculty->get($offering->id),
                    ];
                })
                ->values();
        }

        return response()->json([

            'faculty' => [
                'id' => $faculty->id,
                'full_name' => $faculty->full_name,
                'faculty_scope' => $faculty->faculty_scope,
                'department_id' => $faculty->department_id,
            ],

            'active_academic_term' => $activeTerm ? [
                'id' => $activeTerm->id,
                'display_name' => $activeTerm->display_name,
            ] : null,

            'offerings' => $offerings,

        ]);
    }

    /**
     * Replace this faculty member's Preferred Subject Offerings for the
     * ACTIVE Academic Term only. Direct mirror of
     * RoomController::syncPreferredSubjects() — see that method for the
     * full reasoning; the same rules apply here:
     *
     *   - Only the active term's preference rows are ever touched.
     *   - Every incoming ID is re-validated server-side against the
     *     active term. The eligibility smart-filter (is_recommended) is
     *     a UI convenience only and is NOT re-enforced here — a
     *     scheduler may deliberately record an "unrecommended"
     *     preference, and that's allowed. This is a preference, not an
     *     assignment; TeachingAssignmentService's real eligibility
     *     rules still govern the actual Faculty Loading assignment
     *     separately.
     *   - A Subject Offering can only be preferred by ONE faculty
     *     member at a time (room_subject_offering-style unique index on
     *     subject_offering_id). Selecting an offering here that another
     *     faculty member currently claims TRANSFERS it to this faculty
     *     member rather than erroring.
     */
    public function syncPreferredSubjects(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'subject_offering_ids' => ['present', 'array'],
            'subject_offering_ids.*' => ['integer', 'exists:subject_offerings,id'],
        ]);

        $activeTerm = AcademicTerm::where('active', true)->first();

        abort_unless($activeTerm, 422, 'There is no active Academic Term to manage preferences for.');

        $activeTermOfferingIds = SubjectOffering::where('academic_term_id', $activeTerm->id)->pluck('id');

        $selectedIds = collect($validated['subject_offering_ids'])
            ->intersect($activeTermOfferingIds)
            ->values();

        // Only ever touch this term's rows — detach everything this
        // faculty member currently prefers for the active term, then
        // reattach the (re-validated) submitted selection.
        $faculty->preferredSubjectOfferings()->detach($activeTermOfferingIds);

        // Transfer semantics — see docblock above.
        DB::table('faculty_subject_offering')
            ->whereIn('subject_offering_id', $selectedIds)
            ->delete();

        $faculty->preferredSubjectOfferings()->attach($selectedIds);

        $preferredUnits = (int) $faculty->preferredSubjectOfferings()
            ->where('subject_offerings.academic_term_id', $activeTerm->id)
            ->join('subjects', 'subjects.id', '=', 'subject_offerings.subject_id')
            ->sum('subjects.units');

        $preferredCount = $faculty->preferredSubjectOfferings()
            ->where('subject_offerings.academic_term_id', $activeTerm->id)
            ->count();

        return response()->json([
            'message' => 'Preferred subjects updated successfully.',
            'faculty_id' => $faculty->id,
            'preferred_units' => $preferredUnits,
            'preferred_count' => $preferredCount,
        ]);
    }

    /**
     * Whether a Subject Offering fits this Faculty member's Scope —
     * informational only (see manageSubjects() docblock). Mirrors the
     * same three-scope business rule already previewed client-side in
     * TeachingAssignments/Index.vue's checkEligibility(), and enforced
     * authoritatively by TeachingAssignmentService for real Faculty
     * Loading assignments:
     *
     *   - General Education: Minor subjects only.
     *   - Departmental: Major subjects only, within their own
     *     department.
     *   - Cross Department: Major subjects must stay within their own
     *     department; Minor subjects are unrestricted.
     *
     * Requires $offering->section.curriculum.program to already be
     * eager-loaded by the caller to avoid an N+1 query per offering.
     */
    private function isEligible(Faculty $faculty, SubjectOffering $offering): bool
    {
        if (! $faculty->status) {
            return false;
        }

        $isMajor = (bool) $offering->subject?->is_major;
        $subjectDepartmentId = $offering->section?->curriculum?->program?->department_id;

        if ($faculty->faculty_scope === 'general' && $isMajor) {
            return false;
        }

        if ($faculty->faculty_scope === 'departmental' && ! $isMajor) {
            return false;
        }

        if ($faculty->faculty_scope === 'departmental' && $subjectDepartmentId !== $faculty->department_id) {
            return false;
        }

        if ($faculty->faculty_scope === 'cross_department' && $isMajor && $subjectDepartmentId !== $faculty->department_id) {
            return false;
        }

        return true;
    }
}