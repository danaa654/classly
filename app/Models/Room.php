<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Room Identity
        |--------------------------------------------------------------------------
        |
        | room_code is the only display identifier now (e.g. "Room 108",
        | "Room 304 (ICT Workshop)"). room_name was dropped — the two
        | fields answered the same "what do we call this room" question.
        |
        */

        'room_code',

        /*
        |--------------------------------------------------------------------------
        | Room Type
        |--------------------------------------------------------------------------
        |
        | room_type reflects PAP's actual room inventory (Lecture /
        | Laboratory). Which program(s) the room is available to now lives
        | in the roomGroups() relationship below, not a column here — a
        | room can be General (every department), Shared (several
        | departments), or Exclusive (one department).
        |
        */

        'room_type',

        /*
        |--------------------------------------------------------------------------
        | Location
        |--------------------------------------------------------------------------
        */

        'building',
        'floor',

        /*
        |--------------------------------------------------------------------------
        | Capacity
        |--------------------------------------------------------------------------
        */

        'capacity',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'active',
    ];

    /**
     * room_group_codes is derived from the roomGroups relationship, not a
     * real column — appending it here means it's always present on the
     * model's array/JSON form (what Inertia sends to the Vue pages)
     * without every caller having to remember to add it manually.
     */
    protected $appends = [
        'room_group_codes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    | Matches the column types defined in the rooms migration so values come
    | back from Eloquent as the correct native PHP types.
    */

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The programs this room is available to. Zero-or-more rows via
     * room_group_room — a single "General" row, one Exclusive row, or
     * several rows for a room Shared across departments.
     */
    public function roomGroups()
    {
        return $this->hasMany(RoomGroupRoom::class);
    }

    /**
     * Flat array of program codes (e.g. ['BSHM', 'BSTM']) for the roomGroups
     * relationship above — what the Create/Edit/Index pages actually bind
     * to and display. Call ->load('roomGroups') first when fetching many
     * rooms to avoid an N+1 query per room.
     */
    public function getRoomGroupCodesAttribute(): array
    {
        return $this->roomGroups->pluck('room_group')->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Rooms eligible for the automatic scheduler
     * (i.e. active rooms only).
     */
    public function scopeSchedulable($query)
    {
        return $query->where('active', true);
    }

    /**
     * Rooms available to a given program — either flagged General
     * (available to every program) or specifically assigned to
     * $programCode (Shared or Exclusive). For the future Greedy Scheduler
     * to pick a valid room for a given subject/section.
     */
    public function scopeAvailableFor($query, string $programCode)
    {
        return $query->whereHas('roomGroups', function ($query) use ($programCode) {
            $query->whereIn('room_group', ['General', $programCode]);
        });
    }

    /**
     * Rooms matching a single program filter — used by the Index page's
     * "Available Programs" dropdown. Matches any room that has that
     * program among its (possibly several) assigned programs. Unlike
     * scopeAvailableFor(), this does NOT also match General when filtering
     * by a specific department — it's a literal "has this exact group"
     * filter, not a scheduling-eligibility check.
     */
    public function scopeForRoomGroup($query, string $roomGroup)
    {
        return $query->whereHas('roomGroups', function ($query) use ($roomGroup) {
            $query->where('room_group', $roomGroup);
        });
    }
}