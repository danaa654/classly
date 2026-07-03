<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
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
        $query = Room::with('roomGroups')->orderBy('room_code');

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