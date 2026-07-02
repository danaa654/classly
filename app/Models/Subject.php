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

        'required_room_type',
        'required_room_group',
        'is_practicum',

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
            'is_practicum' => 'boolean',
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
    | curriculums (e.g. NSTP1 under BSIT, BSCS, BSHM, etc.) via
    | curriculum_items. curriculum_items also holds non-Subject item types
    | (OJT, and future types) that never reference a subject at all — those
    | rows simply never show up on the other side of these relationships.
    |
    */

    public function curriculumItems()
    {
        return $this->hasMany(CurriculumItem::class);
    }

    public function curriculums()
    {
        return $this->belongsToMany(Curriculum::class, 'curriculum_items')
            ->wherePivot('item_type', CurriculumItem::TYPE_SUBJECT)
            ->withPivot(['id', 'item_type', 'year_level', 'semester', 'sort_order', 'active'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Faculty Assignment
    |--------------------------------------------------------------------------
    |
    | Every faculty member qualified to teach this subject, via the
    | dedicated faculty_subjects table (added for the Faculty Subject
    | Assignment module).
    |
    */

    public function facultySubjects()
    {
        return $this->hasMany(FacultySubject::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Subjects eligible for the automatic scheduler
     * (i.e. everything except Practicum/OJT).
     */
    public function scopeSchedulable($query)
    {
        return $query->where('is_practicum', false);
    }
}