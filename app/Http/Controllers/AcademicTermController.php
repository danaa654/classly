<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicTermRequest;
use App\Models\AcademicTerm;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;

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
    public function store(AcademicTermRequest $request)
    {
        $validated = $request->validatedForSave();

        try {

            DB::transaction(function () use ($validated) {

                if (! empty($validated['active'])) {
                    AcademicTerm::where('active', true)->update(['active' => false]);
                }

                AcademicTerm::create($validated);

            });

        } catch (Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->with('error', 'Failed to save Academic Term.');

        }

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
    public function update(AcademicTermRequest $request, AcademicTerm $academicTerm)
    {
        // Archived Academic Terms are historical record — read-only,
        // never editable again once they reach this status.
        if ($academicTerm->status === 'Archived') {
            return redirect()
                ->route('academic-terms.index')
                ->with('warning', 'Archived Academic Terms are read-only and cannot be edited.');
        }

        $validated = $request->validatedForSave();
        $wasArchived = $academicTerm->status === 'Archived';
        $isBeingArchived = ! $wasArchived && $validated['status'] === 'Archived';

        try {

            DB::transaction(function () use ($validated, $academicTerm) {

                if (! empty($validated['active'])) {
                    AcademicTerm::where('active', true)
                        ->where('id', '!=', $academicTerm->id)
                        ->update(['active' => false]);
                }

                $academicTerm->update($validated);

            });

        } catch (Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->with('error', 'Failed to save Academic Term.');

        }

        return redirect()
            ->route('academic-terms.index')
            ->with('success', $isBeingArchived
                ? 'Academic Term archived successfully.'
                : 'Academic Term updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AcademicTerm $academicTerm)
    {
        // Rule 7: The active term can never be deleted — the rest of the
        // app (dashboards, scheduling, the header badge) assumes there's
        // always exactly one active term to point to.
        if ($academicTerm->active) {
            return redirect()
                ->route('academic-terms.index')
                ->with('warning', 'The active Academic Term cannot be deleted. Activate another Academic Term first.');
        }

        // Rule 9: Terms already carrying real scheduling data must be
        // archived, not deleted, so that data is never orphaned.
        if ($academicTerm->hasSchedulingData()) {
            return redirect()
                ->route('academic-terms.index')
                ->with('warning', 'This Academic Term contains scheduling data and cannot be deleted. Archive it instead.');
        }

        $academicTerm->delete();

        return redirect()
            ->route('academic-terms.index')
            ->with('success', 'Academic Term deleted successfully.');
    }
}