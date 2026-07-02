<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = [

        'academic_year',

        'semester',

        'registration_start_date',
        'registration_end_date',

        'class_start_date',
        'class_end_date',

        'school_start_time',
        'school_end_time',

        'lunch_start_time',
        'lunch_end_time',

        'time_interval',

        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',

        'status',

        'active',

    ];

    /**
     * Attribute casting.
     */
    protected $casts = [

        'registration_start_date' => 'date:Y-m-d',
        'registration_end_date' => 'date:Y-m-d',

        'class_start_date' => 'date:Y-m-d',
        'class_end_date' => 'date:Y-m-d',

        'school_start_time' => 'datetime:H:i',
        'school_end_time' => 'datetime:H:i',

        'lunch_start_time' => 'datetime:H:i',
        'lunch_end_time' => 'datetime:H:i',

        'time_interval' => 'integer',

        'monday' => 'boolean',
        'tuesday' => 'boolean',
        'wednesday' => 'boolean',
        'thursday' => 'boolean',
        'friday' => 'boolean',
        'saturday' => 'boolean',
        'sunday' => 'boolean',

        'active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Appended Accessors
    |--------------------------------------------------------------------------
    |
    | semester_label and display_name are computed accessors. They need to
    | be appended so they actually show up in the JSON/Inertia payload —
    | without this, Index/Edit pages and the AcademicTermSelector component
    | would have no way to read them from the props.
    |
    */

    protected $appends = [
        'semester_label',
        'display_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Semester Labels
    |--------------------------------------------------------------------------
    */

    public const SEMESTERS = [
        1 => '1st Semester',
        2 => '2nd Semester',
        3 => 'Summer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getSemesterLabelAttribute()
    {
        return self::SEMESTERS[$this->semester] ?? null;
    }

    public function getDisplayNameAttribute()
    {
        return "AY {$this->academic_year} \u{2022} {$this->semester_label}";
    }
}