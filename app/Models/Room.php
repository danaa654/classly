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
        */

        'room_code',
        'room_name',

        /*
        |--------------------------------------------------------------------------
        | Room Type / Room Group
        |--------------------------------------------------------------------------
        |
        | room_type reflects PAP's actual room inventory (Lecture /
        | Laboratory). room_group mirrors Subject::required_room_group — it
        | names the academic program whose lecture rooms / laboratories this
        | room belongs to (General, BSIT, BSED, BSHM, BSTM, BSCRIM).
        | "General" is a Lecture-only value.
        |
        */

        'room_type',
        'room_group',

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
}