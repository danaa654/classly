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
    | Sections
    |--------------------------------------------------------------------------
    |
    | The section groupings (e.g. BSIT-1A) that follow this curriculum.
    |
    */

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Curriculum Items (Subjects, OJT, and future item types)
    |--------------------------------------------------------------------------
    |
    | This curriculum's full prospectus — every item type included. This is
    | the relationship to reach for on the Manage/Index pages, which need
    | to show Subject and OJT rows side by side.
    |
    */

    public function curriculumItems()
    {
        return $this->hasMany(CurriculumItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Subjects (convenience accessor)
    |--------------------------------------------------------------------------
    |
    | Subjects are a master list shared across curriculums. This exposes
    | just the Subject-type items of this curriculum's prospectus as an
    | actual collection of Subject models — this is what the scheduler
    | should use, since it only ever schedules Subject items into rooms.
    |
    */

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'curriculum_items')
            ->wherePivot('item_type', CurriculumItem::TYPE_SUBJECT)
            ->withPivot(['id', 'item_type', 'year_level', 'semester', 'sort_order', 'active'])
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