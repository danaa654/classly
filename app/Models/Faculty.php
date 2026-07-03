<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'gender',
        'contact_number',
        'email',
        'department_id',
        'faculty_scope',
        'employment_type',
        'max_units',
        'status',
    ];

    protected $appends = [
        'full_name',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Faculty Subjects
    |--------------------------------------------------------------------------
    |
    | Every subject this faculty member is qualified to teach, via the
    | dedicated faculty_subjects table (added for the Faculty Subject
    | Assignment module).
    |
    */

    public function facultySubjects()
    {
        return $this->hasMany(FacultySubject::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function getFullNameAttribute()
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
        ])->filter()->implode(' ');
    }
}