<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectOffering extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    |
    | This module (Generate Subject Offerings) only ever writes PENDING.
    | Confirmed / Cancelled are reserved for future Faculty Loading /
    | Scheduling modules to transition an offering through.
    */

    public const STATUS_PENDING = 'Pending';
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'academic_term_id',
        'curriculum_id',
        'curriculum_item_id',
        'subject_id',
        'section_id',
        'edp_code',
        'year_level',
        'semester',
        'faculty_id',
        'room_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'semester' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function curriculumItem()
    {
        return $this->belongsTo(CurriculumItem::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForTerm($query, int $academicTermId)
    {
        return $query->where('academic_term_id', $academicTermId);
    }

    public function scopeForProgram($query, int $programId)
    {
        return $query->whereHas('curriculum', function ($inner) use ($programId) {
            $inner->where('program_id', $programId);
        });
    }
}