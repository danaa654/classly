<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [

        'academic_term_id',

        'section_id',

        'curriculum_item_id',

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

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function curriculumItem()
    {
        return $this->belongsTo(CurriculumItem::class);
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
}