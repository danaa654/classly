<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SubjectController extends Controller implements HasMiddleware
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
     * Display all subjects.
     */
    public function index()
    {
        return Inertia::render('Subjects/Index', [

            'subjects' => Subject::with('prerequisite')
                ->orderBy('subject_code')
                ->get(),

        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return Inertia::render('Subjects/Create', [

            'subjects' => Subject::orderBy('subject_code')->get(),

        ]);
    }

    /**
     * Store subject.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'subject_code' => [
                'required',
                'string',
                'max:20',
                'unique:subjects,subject_code',
            ],

            'descriptive_title' => [
                'required',
                'string',
                'max:255',
            ],

            // units / lecture_hours / laboratory_hours are stored as
            // unsignedTinyInteger columns, so they must be validated as
            // whole numbers, not arbitrary decimals.
            'units' => [
                'required',
                'integer',
                'min:1',
                'max:6',
            ],

            'lecture_hours' => [
                'required',
                'integer',
                'min:0',
                'max:10',
            ],

            'laboratory_hours' => [
                'required',
                'integer',
                'min:0',
                'max:10',
            ],

            'is_major' => [
                'required',
                'boolean',
            ],

            'required_room' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Computer Laboratory',
                    'Science Laboratory',
                    'Speech Laboratory',
                    'PE Area',
                    'Any',
                ]),
            ],

            'allow_split_schedule' => [
                'required',
                'boolean',
            ],

            'prerequisite_id' => [
                'nullable',
                'exists:subjects,id',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Compute Total Contact Hours
        |--------------------------------------------------------------------------
        */

        $validated['total_hours'] =
            $validated['lecture_hours']
            + $validated['laboratory_hours'];

        /*
        |--------------------------------------------------------------------------
        | Subject Code
        |--------------------------------------------------------------------------
        */

        $validated['subject_code'] = strtoupper(
            $validated['subject_code']
        );

        Subject::create($validated);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(Subject $subject)
    {
        return Inertia::render('Subjects/Edit', [

            'subject' => $subject,

            'subjects' => Subject::where('id', '!=', $subject->id)
                ->orderBy('subject_code')
                ->get(),

        ]);
    }

    /**
     * Update subject.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([

            'subject_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('subjects', 'subject_code')
                    ->ignore($subject->id),
            ],

            'descriptive_title' => [
                'required',
                'string',
                'max:255',
            ],

            'units' => [
                'required',
                'integer',
                'min:1',
                'max:6',
            ],

            'lecture_hours' => [
                'required',
                'integer',
                'min:0',
                'max:10',
            ],

            'laboratory_hours' => [
                'required',
                'integer',
                'min:0',
                'max:10',
            ],

            'is_major' => [
                'required',
                'boolean',
            ],

            'required_room' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Computer Laboratory',
                    'Science Laboratory',
                    'Speech Laboratory',
                    'PE Area',
                    'Any',
                ]),
            ],

            'allow_split_schedule' => [
                'required',
                'boolean',
            ],

            'prerequisite_id' => [
                'nullable',
                'exists:subjects,id',
                // A subject can't be its own prerequisite.
                Rule::notIn([$subject->id]),
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

        /*
        |--------------------------------------------------------------------------
        | Compute Total Contact Hours
        |--------------------------------------------------------------------------
        */

        $validated['total_hours'] =
            $validated['lecture_hours']
            + $validated['laboratory_hours'];

        /*
        |--------------------------------------------------------------------------
        | Subject Code
        |--------------------------------------------------------------------------
        */

        $validated['subject_code'] = strtoupper(
            $validated['subject_code']
        );

        $subject->update($validated);

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Delete subject.
     */
    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()
            ->route('subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }
}