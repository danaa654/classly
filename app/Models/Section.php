<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'curriculum_id',
        'section_code',
        'section_name',
        'year_level',
        'section_letter',
        'capacity',
        'status',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'year_level' => 'integer',
        'capacity' => 'integer',
    ];

    /**
     * Computed attributes always included when a Section is serialized
     * (e.g. sent to the frontend via Inertia).
     */
    protected $appends = [
        'is_in_use',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Deletion guards
    |--------------------------------------------------------------------------
    */

    /**
     * Whether this Section is referenced by any existing scheduling-related
     * records and therefore cannot be safely deleted.
     *
     * Deliberately does NOT check student enrollment — that module doesn't
     * exist yet. Extend this as new scheduling modules ship, e.g.:
     *
     *   || $this->schedules()->exists()
     *   || $this->facultyLoadings()->exists()
     */
    public function isInUse(): bool
    {
        return $this->teachingAssignments()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    |
    | College and Program are intentionally NOT stored on the sections
    | table — they are always resolved through the Curriculum relationship
    | so a Section can never drift out of sync with the curriculum it
    | belongs to. Year Level, unlike those, is stored directly on the
    | section (see migration) since it's part of what makes a section
    | unique within a curriculum, alongside its letter.
    |
    */

    public function getProgramAttribute()
    {
        return $this->curriculum?->program;
    }

    public function getSpecializationAttribute()
    {
        return $this->curriculum?->specialization;
    }

    /**
     * Frontend-facing mirror of isInUse(). Prefers the eager-loaded
     * `teaching_assignments_count` (see SectionController::index(), which
     * uses withCount() so the whole list avoids an N+1 query) and only
     * falls back to a live query when that count isn't loaded — e.g. if
     * this Section was fetched some other way.
     */
    public function getIsInUseAttribute(): bool
    {
        if (array_key_exists('teaching_assignments_count', $this->attributes)) {
            return (int) $this->attributes['teaching_assignments_count'] > 0;
        }

        return $this->isInUse();
    }
}