<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


class DepartmentController extends Controller implements HasMiddleware
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
        return Inertia::render('Departments/Index', [
            'departments' => Department::orderBy('abbreviation')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Departments/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'abbreviation' => 'required|string|max:20|unique:departments,abbreviation',

            'description' => 'nullable|string|max:500',

            'active' => 'required|boolean',
        ]);

        Department::create([
            'name' => $validated['name'],

            'abbreviation' => strtoupper($validated['abbreviation']),

            'description' => $validated['description'],

            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'College created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        return Inertia::render('Departments/Edit', [
            'department' => $department,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'abbreviation' => 'required|string|max:20|unique:departments,abbreviation,' . $department->id,

            'description' => 'nullable|string|max:500',

            'active' => 'required|boolean',
        ]);

        $department->update([
            'name' => $validated['name'],

            'abbreviation' => strtoupper($validated['abbreviation']),

            'description' => $validated['description'],

            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'College updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'College deleted successfully.');
    }
}