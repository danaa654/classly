<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SpecializationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {

                abort_unless(
                    auth()->user()->hasAnyRole(['Admin', 'Registrar']),
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
        return Inertia::render('Specializations/Index', [
            'specializations' => Specialization::with('program.department')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Specializations/Create', [
            'programs' => Program::with('department')
                ->where('active', true)
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

            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('specializations')
                    ->where(fn ($query) =>
                        $query->where('program_id', $request->program_id)
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('specializations')
                    ->where(fn ($query) =>
                        $query->where('program_id', $request->program_id)
                    ),
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        Specialization::create($validated);

        return redirect()
            ->route('specializations.index')
            ->with('success', 'Specialization created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Specialization $specialization)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Specialization $specialization)
    {
        return Inertia::render('Specializations/Edit', [

            'specialization' => $specialization,

            'programs' => Program::with('department')
                ->where('active', true)
                ->orderBy('name')
                ->get(),

        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Specialization $specialization)
    {
        $validated = $request->validate([

            'program_id' => [
                'required',
                'exists:programs,id',
            ],

            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('specializations')
                    ->ignore($specialization->id)
                    ->where(fn ($query) =>
                        $query->where('program_id', $request->program_id)
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('specializations')
                    ->ignore($specialization->id)
                    ->where(fn ($query) =>
                        $query->where('program_id', $request->program_id)
                    ),
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

        if (!empty($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $specialization->update($validated);

        return redirect()
            ->route('specializations.index')
            ->with('success', 'Specialization updated successfully.');
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Specialization $specialization)
    {
        $specialization->delete();

        return redirect()
            ->route('specializations.index')
            ->with('success', 'Specialization deleted successfully.');
    }
}