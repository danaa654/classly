<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single (subject, program) assignment row.
 *
 * A subject can have any number of these — this is what makes Program/Room
 * Group assignment a many-to-many relationship instead of the old single
 * required_room_group column. There is no separate `room_groups` lookup
 * table; the allowed values are the fixed program list below, same as it
 * always was.
 */
class SubjectRoomGroup extends Model
{
    protected $table = 'room_group_subject';

    /*
    |--------------------------------------------------------------------------
    | Allowed Programs
    |--------------------------------------------------------------------------
    |
    | Mirrors Room::room_group. "General" is a Lecture-only program — the
    | same Laboratory-can't-be-General rule that used to guard the single
    | required_room_group column is preserved in SubjectController.
    |
    */

    public const GROUPS = [
        'General',
        'BSIT',
        'BSED',
        'BSHM',
        'BSTM',
        'BSCRIM',
    ];

    protected $fillable = [
        'subject_id',
        'room_group',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}