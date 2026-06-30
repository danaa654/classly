<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    protected $fillable = [

        'program_id',

        'specialization_id',

        'code',

        'name',

        'academic_year',

        'effective_year',

        'active',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Future Relationship
    |--------------------------------------------------------------------------
    |
    | This will contain the prospectus (subjects inside the curriculum).
    |
    */

    public function curriculumSubjects()
    {
        return $this->hasMany(CurriculumSubject::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute()
    {
        if ($this->specialization) {
            return "{$this->program->code} - {$this->specialization->name} ({$this->effective_year})";
        }

        return "{$this->program->code} ({$this->effective_year})";
    }
}