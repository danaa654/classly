<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacultyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faculties = Faculty::with('department')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Faculty/Index', [
            'faculties' => $faculties,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Faculty/Create', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            // Personal Information
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:20',

            'gender' => 'nullable|in:Male,Female',

            'contact_number' => 'nullable|string|max:20',

            'email' => 'nullable|email|unique:faculties,email',

            // Home College
            'department_id' => 'nullable|exists:departments,id',

            // Employment
            'employment_type' => 'required|in:Full-Time,Part-Time',

            // Teaching Load
            'max_units' => 'required|integer|min:1|max:24',

            // Teaching Qualification
            'teaching_qualification' => 'required|in:Major,Minor,Both',

            // Status
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->boolean('status');

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

            'departments' => Department::orderBy('name')->get(),

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([

            // Personal Information
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:20',

            'gender' => 'nullable|in:Male,Female',

            'contact_number' => 'nullable|string|max:20',

            'email' => 'nullable|email|unique:faculties,email,' . $faculty->id,

            // Home College
            'department_id' => 'nullable|exists:departments,id',

            // Employment
            'employment_type' => 'required|in:Full-Time,Part-Time',

            // Teaching Load
            'max_units' => 'required|integer|min:1|max:24',

            // Teaching Qualification
            'teaching_qualification' => 'required|in:Major,Minor,Both',

            // Status
            'status' => 'boolean',
        ]);

        $validated['status'] = $request->boolean('status');

        $faculty->update($validated);

        return redirect()
            ->route('faculty.index')
            ->with('success', 'Faculty member updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faculty $faculty)
    {
        $faculty->delete();

        return redirect()
            ->route('faculty.index')
            ->with('success', 'Faculty member deleted successfully.');
    }
}