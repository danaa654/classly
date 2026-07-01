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
        $validated = $request->validate([

            'room_code' => [
                'required',
                'string',
                'max:20',
                'unique:rooms,room_code',
            ],

            'room_name' => [
                'required',
                'string',
                'max:255',
            ],

            'room_type' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Computer Laboratory',
                    'Science Laboratory',
                    'Speech Laboratory',
                    'PE Area',
                    'Any',
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

            'capacity' => [
                'required',
                'numeric',
                'min:1',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

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
        $validated = $request->validate([

            'room_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'room_code')
                    ->ignore($room->id),
            ],

            'room_name' => [
                'required',
                'string',
                'max:255',
            ],

            'room_type' => [
                'required',
                Rule::in([
                    'Lecture',
                    'Computer Laboratory',
                    'Science Laboratory',
                    'Speech Laboratory',
                    'PE Area',
                    'Any',
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

            'capacity' => [
                'required',
                'numeric',
                'min:1',
            ],

            'active' => [
                'required',
                'boolean',
            ],

        ]);

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
}