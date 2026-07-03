<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = [
        'curriculum_id',
        'section_code',
        'section_name',
        'year_level',
        'section_letter',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    // Append these attributes when converting to array/JSON
    protected $appends = ['is_in_use'];

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
    | Accessors & Attributes
    |--------------------------------------------------------------------------
    */

    public function getIsInUseAttribute()
    {
        return $this->isInUse();
    }

    /**
     * Check if this section is currently in use (has teaching assignments).
     */
    public function isInUse()
    {
        return $this->teachingAssignments()->exists();
    }
}
