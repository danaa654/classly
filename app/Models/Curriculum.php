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
    | Prospectus (Subjects inside the curriculum)
    |--------------------------------------------------------------------------
    |
    | Subjects are a master list shared across curriculums. This
    | curriculum's actual prospectus is the set of subjects attached
    | through the curriculum_subjects pivot, each with its own
    | year_level/semester placement.
    |
    */

    public function curriculumSubjects()
    {
        return $this->hasMany(CurriculumSubject::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'curriculum_subjects')
            ->withPivot(['year_level', 'semester'])
            ->withTimestamps();
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