<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SectionController extends Controller implements HasMiddleware
{
    /**
     * Controller Middleware
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
     * Display all sections.
     */
    public function index()
    {
        return Inertia::render('Sections/Index', [

            'sections' => Section::with([
                'curriculum.program',
                'curriculum.specialization',
            ])
                ->orderBy('section_code')
                ->get(),

        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return Inertia::render('Sections/Create', [

            'curriculums' => $this->curriculumOptions(),

        ]);
    }

    /**
     * Store section.
     */
    public function store(Request $request)
    {
        $validated = $this->validateSection($request);

        /*
        |--------------------------------------------------------------------------
        | Section Code
        |--------------------------------------------------------------------------
        */

        $validated['section_code'] = strtoupper(
            $validated['section_code']
        );

        Section::create($validated);

        return redirect()
            ->route('sections.index')
            ->with('success', 'Section created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(Section $section)
    {
        return Inertia::render('Sections/Edit', [

            'section' => $section->load('curriculum'),

            'curriculums' => $this->curriculumOptions(),

        ]);
    }

    /**
     * Update section.
     */
    public function update(Request $request, Section $section)
    {
        $validated = $this->validateSection($request, $section);

        /*
        |--------------------------------------------------------------------------
        | Section Code
        |--------------------------------------------------------------------------
        */

        $validated['section_code'] = strtoupper(
            $validated['section_code']
        );

        $section->update($validated);

        return redirect()
            ->route('sections.index')
            ->with('success', 'Section updated successfully.');
    }

    /**
     * Delete section.
     */
    public function destroy(Section $section)
    {
        $section->delete();

        return redirect()
            ->route('sections.index')
            ->with('success', 'Section deleted successfully.');
    }

    /**
     * Shared validation rules for store/update.
     */
    private function validateSection(Request $request, ?Section $section = null): array
    {
        return $request->validate([

            'curriculum_id' => [
                'required',
                'integer',
                'exists:curricula,id',
            ],

            'section_code' => [
                'required',
                'string',
                'max:20',
                $section
                    ? Rule::unique('sections', 'section_code')->ignore($section->id)
                    : Rule::unique('sections', 'section_code'),
            ],

            'section_name' => [
                'required',
                'string',
                'max:255',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],

            'status' => [
                'required',
                Rule::in([
                    'Active',
                    'Inactive',
                ]),
            ],

        ]);
    }

    /**
     * Curriculum dropdown options, with display_name resolved on the
     * server so the Create/Edit pages don't need to know how it's built.
     */
    private function curriculumOptions()
    {
        return Curriculum::with(['program', 'specialization'])
            ->orderByDesc('effective_year')
            ->get()
            ->map(fn (Curriculum $curriculum) => [
                'id' => $curriculum->id,
                'display_name' => $curriculum->display_name,
            ]);
    }
}