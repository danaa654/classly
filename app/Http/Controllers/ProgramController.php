<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProgramController extends Controller implements HasMiddleware
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
        return Inertia::render('Programs/Index', [
            'programs' => Program::with('department')
                ->orderBy('code')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Programs/Create', [
            'departments' => Department::where('active', true)
                ->orderBy('abbreviation')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'department_id' => 'required|exists:departments,id',

            'code' => 'required|string|max:20|unique:programs,code',

            'name' => 'required|string|max:255',

            'years' => 'required|integer|min:1|max:8',

            'active' => 'required|boolean',

        ]);

        Program::create($validated);

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Program $program)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        return Inertia::render('Programs/Edit', [

            'program' => $program,

            'departments' => Department::where('active', true)
                ->orderBy('abbreviation')
                ->get(),

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([

            'department_id' => 'required|exists:departments,id',

            'code' => 'required|string|max:20|unique:programs,code,' . $program->id,

            'name' => 'required|string|max:255',

            'years' => 'required|integer|min:1|max:8',

            'active' => 'required|boolean',

        ]);

        $program->update($validated);

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program deleted successfully.');
    }
}