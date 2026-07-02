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
     * Display all subjects — filtered, searched, and paginated
     * server-side.
     *
     * Query string params (all optional):
     *   - search:         matches subject_code OR descriptive_title
     *                      (case-insensitive, partial match)
     *   - room_type:      Lecture | Laboratory | Practicum
     *   - classification: Major | Minor
     *   - room_group:     General | BSIT | BSED | BSHM | BSTM | BSCRIM
     *   - status:         Active | Inactive
     *   - page:           handled automatically by paginate()
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'room_type',
            'classification',
            'room_group',
            'status',
        ]);

        $subjects = Subject::query()
            ->with('prerequisite')

            /*
            |--------------------------------------------------------------------------
            | Search — Subject Code / Descriptive Title
            |--------------------------------------------------------------------------
            |
            | LOWER() on both sides keeps this case-insensitive regardless
            | of the database's default collation.
            |
            */
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%' . strtolower($search) . '%';

                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(subject_code) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(descriptive_title) LIKE ?', [$term]);
                });
            })

            /*
            |--------------------------------------------------------------------------
            | Room Type Filter
            |--------------------------------------------------------------------------
            |
            | "Practicum/OJT" is not a required_room_type value — it's
            | driven by the is_practicum flag — so it's handled as its own
            | branch rather than a plain column match.
            |
            */
            ->when($filters['room_type'] ?? null, function ($query, $roomType) {
                if ($roomType === 'Practicum') {
                    $query->where('is_practicum', true);
                } elseif (in_array($roomType, ['Lecture', 'Laboratory'])) {
                    $query->where('required_room_type', $roomType);
                }
            })

            /*
            |--------------------------------------------------------------------------
            | Classification Filter (Major / Minor)
            |--------------------------------------------------------------------------
            */
            ->when($filters['classification'] ?? null, function ($query, $classification) {
                $query->where('is_major', $classification === 'Major');
            })

            /*
            |--------------------------------------------------------------------------
            | Room Group Filter
            |--------------------------------------------------------------------------
            */
            ->when($filters['room_group'] ?? null, function ($query, $roomGroup) {
                $query->where('required_room_group', $roomGroup);
            })

            /*
            |--------------------------------------------------------------------------
            | Status Filter (Active / Inactive)
            |--------------------------------------------------------------------------
            */
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('active', $status === 'Active');
            })

            ->orderBy('subject_code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Subjects/Index', [

            'subjects' => $subjects,

            'filters' => [
                'search' => $filters['search'] ?? '',
                'room_type' => $filters['room_type'] ?? '',
                'classification' => $filters['classification'] ?? '',
                'room_group' => $filters['room_group'] ?? '',
                'status' => $filters['status'] ?? '',
            ],

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
        $validated = $request->validate($this->rules($request));

        $validated = $this->applyRoomGroupOverrides($validated);

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
        $validated = $request->validate($this->rules($request, $subject));

        $validated = $this->applyRoomGroupOverrides($validated);

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

    /**
     * Shared validation rules for store() and update().
     *
     * @param  \Illuminate\Http\Request  $request  The current request —
     *         needed so the required_room_group rule can look at the
     *         sibling required_room_type / is_practicum values.
     * @param  \App\Models\Subject|null  $subject  The subject being updated,
     *         null when creating (used for the unique/notIn ignore rules).
     */
    private function rules(Request $request, ?Subject $subject = null): array
    {
        return [

            'subject_code' => [
                'required',
                'string',
                'max:20',
                $subject
                    ? Rule::unique('subjects', 'subject_code')->ignore($subject->id)
                    : Rule::unique('subjects', 'subject_code'),
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

            /*
            |--------------------------------------------------------------------------
            | Room Type / Room Group / Practicum
            |--------------------------------------------------------------------------
            |
            | required_room_type reflects PAP's actual room inventory
            | (Lecture / Laboratory / None) and replaces the old, more
            | granular required_room enum.
            |
            | required_room_group replaces the old required_specialization
            | field. It no longer names individual specializations (IT, HM,
            | TM, ED, FB, LD, QD, FI) — it names the academic program whose
            | laboratory the scheduler should search (General, BSIT, BSED,
            | BSHM, BSTM, BSCRIM). Criminalistics specializations (FB / LD /
            | QD / FI) all collapse to BSCRIM; the scheduler picks whichever
            | Criminalistics lab is free.
            |
            | required_room_type and required_room_group are still validated
            | against their full allowed lists even when is_practicum is
            | true — applyRoomGroupOverrides() forces the scheduler-relevant
            | value server-side afterwards, so a disabled/tampered frontend
            | field can't smuggle in a bad state.
            |
            */

            'required_room_type' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Laboratory',
                    'None',
                ]),
            ],

            'required_room_group' => [
                'nullable',
                Rule::in([
                    'General',
                    'BSIT',
                    'BSED',
                    'BSHM',
                    'BSTM',
                    'BSCRIM',
                ]),
                function ($attribute, $value, $fail) use ($request) {

                    // Practicum/OJT and "None" subjects never get a room —
                    // any value here gets nulled server-side regardless, so
                    // there's nothing to enforce.
                    if ($request->boolean('is_practicum')) {
                        return;
                    }

                    $roomType = $request->input('required_room_type');

                    if ($roomType === 'Laboratory') {
                        if (blank($value)) {
                            $fail('A required room group is required for Laboratory subjects.');
                        } elseif ($value === 'General') {
                            $fail('General is a Lecture-only room group. Laboratory subjects must select a specific program (BSIT, BSED, BSHM, BSTM, or BSCRIM).');
                        }
                    }

                },
            ],

            'is_practicum' => [
                'required',
                'boolean',
            ],

            'allow_split_schedule' => [
                'required',
                'boolean',
            ],

            'prerequisite_id' => $subject
                ? [
                    'nullable',
                    'exists:subjects,id',
                    // A subject can't be its own prerequisite.
                    Rule::notIn([$subject->id]),
                ]
                : [
                    'nullable',
                    'exists:subjects,id',
                ],

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }

    /**
     * Server-side source of truth for the required_room_type /
     * required_room_group relationship — mirrors the frontend watchers but
     * doesn't trust them, so a disabled/tampered field can't smuggle in a
     * bad state:
     *
     *   - is_practicum forces required_room_type to "None".
     *   - required_room_type = "None" forces required_room_group to NULL
     *     (Practicum/OJT subjects never get a room).
     *   - required_room_type = "Lecture" defaults required_room_group to
     *     "General" when left blank (lecture rooms are standard
     *     classrooms; "General" is never forced on subjects that already
     *     specify a program).
     *   - required_room_type = "Laboratory" is left as submitted — the
     *     required_room_group validation rule already rejects blank or
     *     "General" values for laboratory subjects, so nothing to fix up
     *     here.
     */
    private function applyRoomGroupOverrides(array $validated): array
    {
        if ($validated['is_practicum']) {
            $validated['required_room_type'] = 'None';
        }

        if ($validated['required_room_type'] === 'None') {
            $validated['required_room_group'] = null;
        } elseif (
            $validated['required_room_type'] === 'Lecture'
            && blank($validated['required_room_group'] ?? null)
        ) {
            $validated['required_room_group'] = 'General';
        }

        return $validated;
    }
}