<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\CurriculumItem;
use App\Models\Faculty;
use App\Models\FacultySubject;
use App\Models\Section;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TeachingAssignmentController extends Controller implements HasMiddleware
{
    /**
     * Controller Middleware
     *
     * Teaching Assignments (Faculty Loading) follow the same permission
     * tier as Faculty, Subjects, and Rooms.
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
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('TeachingAssignments/Index', [

            'teachingAssignments' => TeachingAssignment::with([
                    'academicTerm',
                    'section.curriculum.program',
                    'curriculumItem.subject',
                    'faculty',
                ])
                ->orderByDesc('created_at')
                ->get(),

        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('TeachingAssignments/Create', $this->sharedFormData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateTeachingAssignment($request);

        TeachingAssignment::create($validated);

        return redirect()
            ->route('teaching-assignments.index')
            ->with('success', 'Teaching assignment created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TeachingAssignment $teachingAssignment)
    {
        return Inertia::render('TeachingAssignments/Edit', array_merge(
            $this->sharedFormData(),
            [
                'teachingAssignment' => $teachingAssignment,
            ]
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TeachingAssignment $teachingAssignment)
    {
        $validated = $this->validateTeachingAssignment($request, $teachingAssignment);

        $teachingAssignment->update($validated);

        return redirect()
            ->route('teaching-assignments.index')
            ->with('success', 'Teaching assignment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TeachingAssignment $teachingAssignment)
    {
        $teachingAssignment->delete();

        return back()->with('success', 'Teaching assignment removed successfully.');
    }

    /**
     * Data shared by the Create and Edit forms.
     *
     * The Section -> Curriculum Item, and Curriculum Item -> Faculty
     * dropdowns cascade client-side (no extra round trips), so this
     * hands the Vue page every option plus the raw section/curriculum
     * and faculty/subject maps it needs to filter with.
     */
    private function sharedFormData(): array
    {
        return [

            'academicTerms' => AcademicTerm::orderByDesc('academic_year')
                ->orderBy('semester')
                ->get(),

            'sections' => Section::with('curriculum.program')
                ->orderBy('section_code')
                ->get(),

            // Only Subject-type items are ever taught by a faculty member —
            // OJT items carry no faculty in this module, so they are
            // excluded from the dropdown entirely.
            'curriculumItems' => CurriculumItem::subjects()
                ->with('subject')
                ->where('active', true)
                ->orderBy('year_level')
                ->orderBy('semester')
                ->get(),

            'faculties' => Faculty::where('status', 'Active')
                ->orderBy('last_name')
                ->get(),

            // Qualification map: which faculty may teach which subject,
            // sourced from the Faculty Subjects module.
            'facultySubjects' => FacultySubject::where('active', true)
                ->get(['faculty_id', 'subject_id']),

        ];
    }

    /**
     * Shared validation rules for store/update.
     */
    private function validateTeachingAssignment(Request $request, ?TeachingAssignment $teachingAssignment = null): array
    {
        $validated = $request->validate([

            'academic_term_id' => [
                'required',
                'exists:academic_terms,id',
            ],

            'section_id' => [
                'required',
                'exists:sections,id',
            ],

            'curriculum_item_id' => [
                'required',
                'exists:curriculum_items,id',
                // Only one faculty member per curriculum item, per
                // section, per academic term — mirrors the DB-level
                // ta_term_section_item_unique constraint.
                Rule::unique('teaching_assignments')
                    ->where(fn ($query) => $query
                        ->where('academic_term_id', $request->academic_term_id)
                        ->where('section_id', $request->section_id))
                    ->ignore($teachingAssignment?->id),
            ],

            'faculty_id' => [
                'required',
                // Faculty model has no $table override, so its default
                // table name is the plural "faculties" (matches the
                // migration's foreignId('faculty_id')->constrained()
                // convention). Was incorrectly "faculty" (singular),
                // which either throws a QueryException or silently
                // fails validation on every submit.
                'exists:faculties,id',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:255',
            ],

            'active' => ['boolean'],

        ], [
            'curriculum_item_id.unique' => 'This subject already has a faculty assigned for the selected section and academic term.',
        ]);

        $this->assertCurriculumItemBelongsToSection($validated);
        $this->assertFacultyIsQualified($validated);

        return $validated;
    }

    /**
     * The curriculum item picked must belong to the curriculum the
     * selected section actually follows — otherwise a stale form could
     * attach a subject from an unrelated program/curriculum to this
     * section.
     */
    private function assertCurriculumItemBelongsToSection(array $validated): void
    {
        $section = Section::findOrFail($validated['section_id']);
        $curriculumItem = CurriculumItem::findOrFail($validated['curriculum_item_id']);

        abort_if(
            $curriculumItem->curriculum_id !== $section->curriculum_id,
            422,
            "The selected curriculum item does not belong to this section's curriculum."
        );
    }

    /**
     * The faculty picked must be marked qualified (via Faculty Subjects)
     * to teach the curriculum item's underlying subject.
     */
    private function assertFacultyIsQualified(array $validated): void
    {
        $curriculumItem = CurriculumItem::findOrFail($validated['curriculum_item_id']);

        if (! $curriculumItem->isSubject()) {
            return;
        }

        $isQualified = FacultySubject::where('faculty_id', $validated['faculty_id'])
            ->where('subject_id', $curriculumItem->subject_id)
            ->where('active', true)
            ->exists();

        abort_unless(
            $isQualified,
            422,
            'The selected faculty is not qualified to teach this subject.'
        );
    }
}