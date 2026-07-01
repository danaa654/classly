<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'room_code',
        'room_name',
        'room_type',
        'building',
        'floor',
        'capacity',
        'active',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'capacity' => 'integer',
        'active' => 'boolean',
    ];
}