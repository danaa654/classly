<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Room;
use App\Models\SubjectOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoomController extends Controller implements HasMiddleware
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
     * Display all rooms.
     *
     * Rooms are still a small master list (no pagination), but now support
     * search + three filters: Room Type, Floor, and Available Programs.
     * The Programs filter matches the exact program selected — a room
     * flagged "General" only shows up under the General filter, not under
     * every specific program too (see Room::scopeForRoomGroup()).
     */
    public function index(Request $request)
    {
        $activeTerm = AcademicTerm::where('active', true)->first();

        $query = Room::with('roomGroups')->orderBy('room_code');

        // Total Preferred Hours AND count for the ACTIVE Academic Term
        // only — two aggregate queries (not one query per room) so the
        // list view can show utilization + subject count without an
        // N+1. Aliased so they land on the model as $room->preferred_hours
        // / $room->preferred_count; null (no active term, or nothing
        // preferred yet) is treated as 0 on the frontend.
        if ($activeTerm) {
            $query->withSum(['preferredSubjectOfferings as preferred_hours' => function ($query) use ($activeTerm) {
                $query->where('subject_offerings.academic_term_id', $activeTerm->id);
            }], 'hours');

            $query->withCount(['preferredSubjectOfferings as preferred_count' => function ($query) use ($activeTerm) {
                $query->where('subject_offerings.academic_term_id', $activeTerm->id);
            }]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('room_code', 'like', "%{$search}%")
                    ->orWhere('building', 'like', "%{$search}%");
            });
        }

        if ($roomType = $request->input('room_type')) {
            $query->where('room_type', $roomType);
        }

        if ($floor = $request->input('floor')) {
            $query->where('floor', $floor);
        }

        if ($roomGroup = $request->input('room_group')) {
            $query->forRoomGroup($roomGroup);
        }

        return Inertia::render('Rooms/Index', [

            'rooms' => $query->get(),

            'roomGroupOptions' => $this->roomGroupOptions(),

            // Distinct floors actually in use, rather than a hardcoded
            // list — keeps the filter accurate if a new floor/wing is
            // ever added without a code change here.
            'floorOptions' => Room::query()
                ->whereNotNull('floor')
                ->distinct()
                ->orderBy('floor')
                ->pluck('floor'),

            'filters' => $request->only(['search', 'room_type', 'floor', 'room_group']),

            'weeklyCapacityHours' => Room::WEEKLY_CAPACITY_HOURS,

        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return Inertia::render('Rooms/Create', [

            'roomGroupOptions' => $this->roomGroupOptions(),

        ]);
    }

    /**
     * Store room.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules($request));

        /*
        |--------------------------------------------------------------------------
        | Room Code
        |--------------------------------------------------------------------------
        */

        $validated['room_code'] = strtoupper(
            $validated['room_code']
        );

        /*
        |--------------------------------------------------------------------------
        | Available Programs
        |--------------------------------------------------------------------------
        |
        | room_groups isn't a column on rooms — pull it out before create()
        | and sync it into the room_group_room pivot afterward instead.
        |
        */

        $roomGroups = $validated['room_groups'];
        unset($validated['room_groups']);

        $room = Room::create($validated);

        $this->syncRoomGroups($room, $roomGroups);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(Room $room)
    {
        $room->load('roomGroups');

        return Inertia::render('Rooms/Edit', [

            'room' => $room,

            'roomGroupOptions' => $this->roomGroupOptions(),

        ]);
    }

    /**
     * Update room.
     */
    public function update(Request $request, Room $room)
    {
        $validated = $request->validate($this->rules($request, $room));

        /*
        |--------------------------------------------------------------------------
        | Room Code
        |--------------------------------------------------------------------------
        */

        $validated['room_code'] = strtoupper(
            $validated['room_code']
        );

        $roomGroups = $validated['room_groups'];
        unset($validated['room_groups']);

        $room->update($validated);

        $this->syncRoomGroups($room, $roomGroups);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room updated successfully.');
    }

    /**
     * Delete room.
     */
    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Manage Subjects (Room Preferences)
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: nothing below this point creates, edits, or reasons about
    | a schedule. A "preferred" Subject Offering is stored as a plain
    | Room <-> SubjectOffering pivot row (room_subject_offering) with no
    | day/time/faculty fields at all. This is purely the input the future
    | Greedy Scheduler will read — the actual scheduling decision happens
    | in a different module.
    |
    */

    /**
     * Manage Subjects data for a single Room — returned as plain JSON,
     * not an Inertia page render. This is now purely the data source
     * for the Manage Subjects MODAL on Rooms/Index.vue: the Index page
     * fetches it via axios when a room's "Manage Subjects" button is
     * clicked, opens the modal client-side, and never navigates away
     * from Index at all — filters, scroll position, and which room's
     * modal is open all stay exactly as they were, since Index.vue
     * itself never re-renders.
     *
     * Same underlying query/annotation logic as before (is_preferred,
     * is_recommended, claimed_by_room_code) — only the response shape
     * changed, from Inertia::render() to response()->json().
     */
    public function manageSubjects(Room $room)
    {
        $room->load('roomGroups');

        $activeTerm = AcademicTerm::where('active', true)->first();

        $offerings = collect();

        if ($activeTerm) {

            $preferredIds = $room->preferredSubjectOfferings()
                ->where('subject_offerings.academic_term_id', $activeTerm->id)
                ->pluck('subject_offerings.id');

            $baseOfferings = SubjectOffering::with([
                    'subject:id,subject_code,descriptive_title',
                    'subject.roomGroups',
                    'program:id,code',
                    'section:id,section_code',
                ])
                ->where('academic_term_id', $activeTerm->id)
                ->where('room_type', $room->room_type)
                ->orderBy('edp_code')
                ->get();

            $claimedByOtherRoom = DB::table('room_subject_offering')
                ->join('rooms', 'rooms.id', '=', 'room_subject_offering.room_id')
                ->whereIn('room_subject_offering.subject_offering_id', $baseOfferings->pluck('id'))
                ->where('room_subject_offering.room_id', '!=', $room->id)
                ->pluck('rooms.room_code', 'room_subject_offering.subject_offering_id');

            $offerings = $baseOfferings
                ->map(function (SubjectOffering $offering) use ($room, $preferredIds, $claimedByOtherRoom) {
                    return [
                        'id' => $offering->id,
                        'edp_code' => $offering->edp_code,
                        'subject_code' => $offering->subject?->subject_code,
                        'subject_title' => $offering->subject?->descriptive_title,
                        'program_code' => $offering->program?->code,
                        'year_level' => $offering->year_level,
                        'section_code' => $offering->section?->section_code,
                        'units' => $offering->units,
                        'hours' => $offering->hours,
                        'classification' => $offering->classification,
                        'room_type' => $offering->room_type,
                        'is_preferred' => $preferredIds->contains($offering->id),
                        'is_recommended' => $this->isDepartmentCompatible($offering, $room),
                        'claimed_by_room_code' => $claimedByOtherRoom->get($offering->id),
                    ];
                })
                ->values();
        }

        return response()->json([

            'room' => [
                'id' => $room->id,
                'room_code' => $room->room_code,
                'room_type' => $room->room_type,
                'room_group_codes' => $room->room_group_codes,
            ],

            'active_academic_term' => $activeTerm ? [
                'id' => $activeTerm->id,
                'display_name' => $activeTerm->display_name,
            ] : null,

            'offerings' => $offerings,

            'weekly_capacity_hours' => Room::WEEKLY_CAPACITY_HOURS,

        ]);
    }

    /**
     * Replace this room's Preferred Subject Offerings for the ACTIVE
     * Academic Term only. Preferences belonging to any other (past)
     * term are left completely untouched.
     *
     * Every incoming ID is re-validated server-side against the active
     * term + this room's Room Type — the department smart-filter is a
     * UI convenience only, so it is NOT re-enforced here; a user may
     * deliberately prefer an "unrecommended" Subject Offering (e.g. a
     * Shared room), and that's allowed. What's never allowed is
     * attaching an offering from the wrong term or the wrong Room Type.
     *
     * Returns plain JSON (fresh preferred_hours/preferred_count for
     * THIS room only) rather than redirecting — the modal reads this
     * response to update its own room's row in Rooms/Index.vue
     * in-memory, then closes itself. No Inertia navigation, no
     * page reload, nothing else on the page is touched.
     */
    public function syncPreferredSubjects(Request $request, Room $room)
    {
        $validated = $request->validate([
            'subject_offering_ids' => ['present', 'array'],
            'subject_offering_ids.*' => ['integer', 'exists:subject_offerings,id'],
        ]);

        $activeTerm = AcademicTerm::where('active', true)->first();

        abort_unless($activeTerm, 422, 'There is no active Academic Term to manage preferences for.');

        $activeTermOfferingIds = SubjectOffering::where('academic_term_id', $activeTerm->id)
            ->where('room_type', $room->room_type)
            ->pluck('id');

        $selectedIds = collect($validated['subject_offering_ids'])
            ->intersect($activeTermOfferingIds)
            ->values();

        // Only ever touch this term's rows — detach everything this room
        // currently prefers for the active term, then reattach the
        // (re-validated) submitted selection.
        $room->preferredSubjectOfferings()->detach($activeTermOfferingIds);

        // A Subject Offering can only be preferred by ONE room at a time
        // (see the room_subject_offering unique index on
        // subject_offering_id). Checking an offering here that another
        // room currently claims TRANSFERS it to this room rather than
        // erroring — the same "last save wins" behavior the Manage
        // Subjects modal's "Currently in Room X" tag warns about before
        // the user ever clicks Save.
        //
        // NOTE: if this transfers an offering away from another room,
        // that other room's own preferred_hours/preferred_count in the
        // Index table go stale until the next full reload of this page
        // — this endpoint only recomputes and returns THIS room's
        // numbers, by design (see class docblock above).
        DB::table('room_subject_offering')
            ->whereIn('subject_offering_id', $selectedIds)
            ->delete();

        $room->preferredSubjectOfferings()->attach($selectedIds);

        $preferredHours = (int) $room->preferredSubjectOfferings()
            ->where('subject_offerings.academic_term_id', $activeTerm->id)
            ->sum('hours');

        $preferredCount = $room->preferredSubjectOfferings()
            ->where('subject_offerings.academic_term_id', $activeTerm->id)
            ->count();

        return response()->json([
            'message' => 'Preferred subjects updated successfully.',
            'room_id' => $room->id,
            'preferred_hours' => $preferredHours,
            'preferred_count' => $preferredCount,
        ]);
    }

    /**
     * Whether a Subject Offering fits this room's Department/Program
     * assignment (Room Type has already been filtered out before this
     * is called). Mirrors the PAP business rule:
     *
     *   - General ("Shared") rooms: every offering of the right Room
     *     Type is compatible, regardless of program.
     *   - Program-specific rooms (e.g. BSIT): Major offerings must match
     *     one of the room's assigned programs; Minor offerings are only
     *     compatible when the underlying Subject is itself flagged
     *     "General" (i.e. a General Education subject open to every
     *     program), via Subject::isApplicableToRoomGroup().
     *
     * Requires $offering->subject->roomGroups to already be eager-loaded
     * by the caller to avoid an N+1 query per offering.
     */
    private function isDepartmentCompatible(SubjectOffering $offering, Room $room): bool
    {
        $roomGroups = $room->room_group_codes;

        if (in_array('General', $roomGroups, true)) {
            return true;
        }

        if ($offering->classification === SubjectOffering::CLASSIFICATION_MAJOR) {
            return in_array($offering->program?->code, $roomGroups, true);
        }

        return (bool) $offering->subject?->isApplicableToRoomGroup('General');
    }

    /**
     * Replace a room's assigned programs with the given list.
     *
     * Delete-and-recreate rather than a diff — the list is small (at most
     * six checkbox pills) so there's no meaningful cost to doing it the
     * simple way.
     */
    private function syncRoomGroups(Room $room, array $roomGroups): void
    {
        $room->roomGroups()->delete();

        foreach ($roomGroups as $roomGroup) {
            $room->roomGroups()->create([
                'room_group' => $roomGroup,
            ]);
        }
    }

    /**
     * The fixed set of programs a room can be assigned to. "General" means
     * available to every department; the rest name a specific department a
     * room is Shared or Exclusive to.
     */
    private function roomGroupOptions(): array
    {
        return [
            'General',
            'BSIT',
            'BSED',
            'BSHM',
            'BSTM',
            'BSCRIM',
        ];
    }

    /**
     * Shared validation rules for store() and update().
     *
     * @param  \Illuminate\Http\Request  $request  The current request —
     *         needed so the room_groups rule can look at the sibling
     *         room_type value.
     * @param  \App\Models\Room|null  $room  The room being updated, null
     *         when creating (used for the unique/ignore rule).
     */
    private function rules(Request $request, ?Room $room = null): array
    {
        return [

            'room_code' => [
                'required',
                'string',
                'max:20',
                $room
                    ? Rule::unique('rooms', 'room_code')->ignore($room->id)
                    : Rule::unique('rooms', 'room_code'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Room Type
            |--------------------------------------------------------------------------
            */

            'room_type' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Laboratory',
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Available Programs (Room Groups)
            |--------------------------------------------------------------------------
            |
            | Multi-select: a room can be General (available to every
            | department), Shared by several departments, or Exclusive to
            | one. Business rules, mirroring Subject::room_groups:
            |
            |   - "General" cannot be combined with anything else — a room
            |     is either open to everyone or restricted to specific
            |     programs, not both.
            |   - "General" is a Lecture-only value — Laboratory rooms must
            |     always belong to one or more specific programs.
            |
            */

            'room_groups' => [
                'required',
                'array',
                'min:1',
                function ($attribute, $value, $fail) use ($request) {

                    $hasGeneral = in_array('General', $value, true);

                    if ($hasGeneral && count($value) > 1) {
                        $fail('General cannot be combined with other programs. Select General on its own, or select one or more specific programs instead.');
                        return;
                    }

                    if ($hasGeneral && $request->input('room_type') === 'Laboratory') {
                        $fail('General is a Lecture-only program. Laboratory rooms must select one or more specific programs (BSIT, BSED, BSHM, BSTM, or BSCRIM).');
                    }

                },
            ],

            'room_groups.*' => [
                Rule::in([
                    'General',
                    'BSIT',
                    'BSED',
                    'BSHM',
                    'BSTM',
                    'BSCRIM',
                ]),
            ],

            'building' => [
                'required',
                'string',
                'max:255',
            ],

            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],

            /*
            |--------------------------------------------------------------------------
            | Capacity
            |--------------------------------------------------------------------------
            |
            | Default of 30 is applied client-side (form initial value);
            | enforced here as a hard 20-45 range regardless of what the
            | client sends.
            |
            */

            'capacity' => [
                'required',
                'integer',
                'min:20',
                'max:45',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }
}