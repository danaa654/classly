<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single Faculty Loading assignment: one faculty member teaching one
 * Subject Offering. Every other fact about the assignment (academic
 * term, section, curriculum item, subject, year level, semester) is
 * reached through subjectOffering — it is never duplicated here.
 */
class TeachingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [

        'subject_offering_id',

        'faculty_id',

        'remarks',

        'active',

    ];

    protected $casts = [

        'active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function subjectOffering()
    {
        return $this->belongsTo(SubjectOffering::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
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

    /**
     * Assignments whose Subject Offering belongs to the given academic
     * term. Since Teaching Assignments no longer carry their own
     * academic_term_id, this is the replacement for the old
     * ->where('academic_term_id', $id) filter.
     */
    public function scopeForTerm($query, int $academicTermId)
    {
        return $query->whereHas(
            'subjectOffering',
            fn ($inner) => $inner->where('academic_term_id', $academicTermId)
        );
    }
}