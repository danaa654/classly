<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Subject Information
        |--------------------------------------------------------------------------
        */

        'subject_code',
        'descriptive_title',

        /*
        |--------------------------------------------------------------------------
        | Academic Information
        |--------------------------------------------------------------------------
        */

        'units',

        'lecture_hours',
        'laboratory_hours',
        'total_hours',

        'is_major',

        /*
        |--------------------------------------------------------------------------
        | Scheduling
        |--------------------------------------------------------------------------
        */

        'required_room',

        'allow_split_schedule',

        /*
        |--------------------------------------------------------------------------
        | Prerequisite
        |--------------------------------------------------------------------------
        */

        'prerequisite_id',

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
    | Matches the column types defined in the subjects migration so values
    | come back from Eloquent as the correct native PHP types (booleans as
    | booleans, integers as integers) instead of raw strings/ints from MySQL.
    */

    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'lecture_hours' => 'integer',
            'laboratory_hours' => 'integer',
            'total_hours' => 'integer',
            'is_major' => 'boolean',
            'allow_split_schedule' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function prerequisite()
    {
        return $this->belongsTo(
            Subject::class,
            'prerequisite_id'
        );
    }

    public function dependents()
    {
        return $this->hasMany(
            Subject::class,
            'prerequisite_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Curriculum Assignment
    |--------------------------------------------------------------------------
    |
    | A subject is a master-list entry that can be reused across many
    | curriculums (e.g. NSTP1 under BSIT, BSCS, BSHM, etc.). The pivot
    | carries the year_level/semester placement, which differs per
    | curriculum.
    |
    */

    public function curriculumSubjects()
    {
        return $this->hasMany(CurriculumSubject::class);
    }

    public function curriculums()
    {
        return $this->belongsToMany(Curriculum::class, 'curriculum_subjects')
            ->withPivot(['year_level', 'semester'])
            ->withTimestamps();
    }
}