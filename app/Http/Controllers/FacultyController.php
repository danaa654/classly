<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
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
}