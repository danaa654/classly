<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FacultyController extends Controller
{
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

            // Department
            'department_id' => [
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

            // Qualification
            'teaching_qualification' => [
                'required',
                'in:Major,Minor,Both',
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

            // Department
            'department_id' => [
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

            // Qualification
            'teaching_qualification' => [
                'required',
                'in:Major,Minor,Both',
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