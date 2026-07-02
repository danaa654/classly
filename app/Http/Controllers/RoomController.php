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
     */
    public function index()
    {
        return Inertia::render('Rooms/Index', [

            'rooms' => Room::orderBy('room_code')->get(),

        ]);
    }

    /**
     * Show create form.
     */
    public function create()
    {
        return Inertia::render('Rooms/Create');
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

        Room::create($validated);

        return redirect()
            ->route('rooms.index')
            ->with('success', 'Room created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(Room $room)
    {
        return Inertia::render('Rooms/Edit', [

            'room' => $room,

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

        $room->update($validated);

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
     * Shared validation rules for store() and update().
     *
     * @param  \Illuminate\Http\Request  $request  The current request —
     *         needed so the room_group rule can look at the sibling
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

            'room_name' => [
                'required',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Room Type / Room Group
            |--------------------------------------------------------------------------
            |
            | room_type reflects PAP's actual room inventory (Lecture /
            | Laboratory only). room_group mirrors
            | Subject::required_room_group — it names the academic program
            | whose lecture rooms / laboratories this room belongs to
            | (General, BSIT, BSED, BSHM, BSTM, BSCRIM). Criminalistics
            | specializations (FB / LD / QD / FI) all collapse to BSCRIM.
            |
            | Business rule: "General" is a Lecture-only room group.
            | Laboratory rooms must always belong to a specific program —
            | this mirrors the equivalent rule already enforced in
            | SubjectController for required_room_group.
            |
            */

            'room_type' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Laboratory',
                ]),
            ],

            'room_group' => [
                'required',
                Rule::in([
                    'General',
                    'BSIT',
                    'BSED',
                    'BSHM',
                    'BSTM',
                    'BSCRIM',
                ]),
                function ($attribute, $value, $fail) use ($request) {

                    if ($request->input('room_type') === 'Laboratory' && $value === 'General') {
                        $fail('General is a Lecture-only room group. Laboratory rooms must select a specific program (BSIT, BSED, BSHM, BSTM, or BSCRIM).');
                    }

                },
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

            'capacity' => [
                'required',
                'numeric',
                'min:1',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ];
    }
}