<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AcademicTermController extends Controller implements HasMiddleware
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
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('AcademicTerms/Index', [

            'academicTerms' => AcademicTerm::orderByDesc('academic_year')
                ->orderBy('semester')
                ->get(),

        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('AcademicTerms/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateAcademicTerm($request);

        DB::transaction(function () use ($validated) {

            if (! empty($validated['active'])) {
                AcademicTerm::where('active', true)->update(['active' => false]);
            }

            AcademicTerm::create($validated);

        });

        return redirect()
            ->route('academic-terms.index')
            ->with('success', 'Academic Term created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AcademicTerm $academicTerm)
    {
        return Inertia::render('AcademicTerms/Edit', [

            'academicTerm' => $academicTerm,

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcademicTerm $academicTerm)
    {
        $validated = $this->validateAcademicTerm($request, $academicTerm);

        DB::transaction(function () use ($validated, $academicTerm) {

            if (! empty($validated['active'])) {
                AcademicTerm::where('active', true)
                    ->where('id', '!=', $academicTerm->id)
                    ->update(['active' => false]);
            }

            $academicTerm->update($validated);

        });

        return redirect()
            ->route('academic-terms.index')
            ->with('success', 'Academic Term updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicTerm $academicTerm)
    {
        abort_if(
            $academicTerm->sections()->exists() || $academicTerm->schedules()->exists(),
            422,
            'Cannot delete an Academic Term that already has Sections or Schedules attached.'
        );

        $academicTerm->delete();

        return redirect()
            ->route('academic-terms.index')
            ->with('success', 'Academic Term deleted successfully.');
    }

    /**
     * Shared validation rules for store/update.
     */
    private function validateAcademicTerm(Request $request, ?AcademicTerm $academicTerm = null): array
    {
        return $request->validate([

            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
            ],

            'semester' => [
                'required',
                'integer',
                Rule::in([1, 2, 3]),
                Rule::unique('academic_terms', 'semester')
                    ->where(fn ($query) => $query->where('academic_year', $request->academic_year))
                    ->ignore($academicTerm?->id),
            ],

            'registration_start_date' => [
                'required',
                'date',
            ],

            'registration_end_date' => [
                'required',
                'date',
                'after_or_equal:registration_start_date',
            ],

            'class_start_date' => [
                'required',
                'date',
            ],

            'class_end_date' => [
                'required',
                'date',
                'after_or_equal:class_start_date',
            ],

            'school_start_time' => [
                'required',
                'date_format:H:i',
            ],

            'school_end_time' => [
                'required',
                'date_format:H:i',
                'after:school_start_time',
            ],

            'lunch_start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'lunch_end_time' => [
                'nullable',
                'date_format:H:i',
                'after:lunch_start_time',
            ],

            'time_interval' => [
                'required',
                'integer',
                'min:5',
                'max:120',
            ],

            'monday' => ['boolean'],
            'tuesday' => ['boolean'],
            'wednesday' => ['boolean'],
            'thursday' => ['boolean'],
            'friday' => ['boolean'],
            'saturday' => ['boolean'],
            'sunday' => ['boolean'],

            'status' => [
                'required',
                Rule::in([
                    'Draft',
                    'Published',
                    'Archived',
                ]),
            ],

            'active' => ['boolean'],

        ]);
    }
}