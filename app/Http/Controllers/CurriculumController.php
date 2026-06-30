<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CurriculumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Curriculums/Index', [
            'curricula' => Curriculum::with([
                'program.department',
                'specialization',
            ])
            ->orderBy('effective_year', 'desc')
            ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Curriculums/Create', [

            'programs' => Program::with('department')
                ->where('active', true)
                ->orderBy('name')
                ->get(),

            'specializations' => Specialization::where('active', true)
                ->orderBy('name')
                ->get(),

        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'program_id' => [
                'required',
                'exists:programs,id',
            ],

            'specialization_id' => [
                'nullable',
                'exists:specializations,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:curricula,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'academic_year' => [
                'required',
                'string',
                'max:20',
            ],

            'effective_year' => [
                'required',
                'digits:4',
                Rule::unique('curricula')->where(function ($query) use ($request) {
                    return $query
                        ->where('program_id', $request->program_id)
                        ->where('specialization_id', $request->specialization_id);
                }),
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

        $validated['code'] = strtoupper($validated['code']);

        Curriculum::create($validated);

        return redirect()
            ->route('curriculums.index')
            ->with('success', 'Curriculum created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Curriculum $curriculum)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Curriculum $curriculum)
    {
        return Inertia::render('Curriculums/Edit', [

            'curriculum' => $curriculum,

            'programs' => Program::with('department')
                ->where('active', true)
                ->orderBy('name')
                ->get(),

            'specializations' => Specialization::where('active', true)
                ->orderBy('name')
                ->get(),

        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Curriculum $curriculum)
    {
        $validated = $request->validate([

            'program_id' => [
                'required',
                'exists:programs,id',
            ],

            'specialization_id' => [
                'nullable',
                'exists:specializations,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('curricula')
                    ->ignore($curriculum->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'academic_year' => [
                'required',
                'string',
                'max:20',
            ],

            'effective_year' => [
                'required',
                'digits:4',
                Rule::unique('curricula')
                    ->ignore($curriculum->id)
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('program_id', $request->program_id)
                            ->where('specialization_id', $request->specialization_id);
                    }),
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

        $validated['code'] = strtoupper($validated['code']);

        $curriculum->update($validated);

        return redirect()
            ->route('curriculums.index')
            ->with('success', 'Curriculum updated successfully.');
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Curriculum $curriculum)
    {
        $curriculum->delete();

        return redirect()
            ->route('curriculums.index')
            ->with('success', 'Curriculum deleted successfully.');
    }
}