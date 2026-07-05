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

    /**
     * Subject Offerings this Faculty member PREFERS to teach, via the
     * faculty_subject_offering pivot (see the Faculty "Manage Subjects"
     * workspace). Direct mirror of Room::preferredSubjectOfferings().
     *
     * This is a preference only — it carries no day/time/room
     * information and is NOT the same thing as an actual Faculty
     * Loading assignment (see teachingAssignments() above). Since every
     * Subject Offering already belongs to one Academic Term, filter by
     * ->where('academic_term_id', ...) wherever only the active term's
     * preferences should count, rather than assuming every row here is
     * current.
     */
    public function preferredSubjectOfferings()
    {
        return $this->belongsToMany(SubjectOffering::class, 'faculty_subject_offering')
            ->withTimestamps();
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