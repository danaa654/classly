<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubjectOffering extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    |
    | These are the possible values of the DERIVED overall_status
    | accessor below — there is no status column. STATUS_DRAFT is kept
    | only for forward compatibility (e.g. a future "save without
    | generating" step); getOverallStatusAttribute() never returns it
    | today, since every row here was, by definition, generated.
    */

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_GENERATED = 'Generated';
    public const STATUS_FACULTY_ASSIGNED = 'Faculty Assigned';
    public const STATUS_ROOM_ASSIGNED = 'Room Assigned';
    public const STATUS_READY_FOR_SCHEDULING = 'Ready for Scheduling';
    public const STATUS_SCHEDULED = 'Scheduled';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_ARCHIVED = 'Archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_GENERATED,
        self::STATUS_FACULTY_ASSIGNED,
        self::STATUS_ROOM_ASSIGNED,
        self::STATUS_READY_FOR_SCHEDULING,
        self::STATUS_SCHEDULED,
        self::STATUS_COMPLETED,
        self::STATUS_ARCHIVED,
    ];

    public const CLASSIFICATION_MAJOR = 'Major';
    public const CLASSIFICATION_MINOR = 'Minor';

    protected $fillable = [
        'academic_term_id',
        'curriculum_id',
        'curriculum_item_id',
        'program_id',
        'subject_id',
        'section_id',
        'year_level',
        'semester',
        'units',
        'hours',
        'classification',
        'room_type',
        'edp_code',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'semester' => 'integer',
            'units' => 'integer',
            'hours' => 'integer',
        ];
    }

    /**
     * faculty_status / room_status / overall_status are all derived,
     * never stored — this module must never itself carry an
     * assignment, and overall_status must never be able to drift out
     * of sync with the real Faculty/Room/Term data it summarizes.
     */
    protected $appends = [
        'faculty_status',
        'room_status',
        'overall_status',
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

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function curriculumItem()
    {
        return $this->belongsTo(CurriculumItem::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The Faculty Loading assignment for this Offering, if one
     * exists — the only place a Faculty assignment actually lives.
     * Read-only from here; this model never writes to it.
     */
    public function teachingAssignment()
    {
        return $this->hasOne(TeachingAssignment::class);
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
        return $query->where('program_id', $programId);
    }

    /*
    |--------------------------------------------------------------------------
    | Derived Assignment Status (read-only)
    |--------------------------------------------------------------------------
    |
    | Defensive on purpose, same pattern as
    | AcademicTerm::hasSchedulingData() — Faculty Loading's and the
    | future Preferred Rooms module's schema aren't owned here, so
    | these check for the relevant table/column before trusting it,
    | rather than hard-failing if either hasn't been built out yet.
    */

    public function getFacultyStatusAttribute(): string
    {
        return $this->teachingAssignment?->faculty_id ? 'Assigned' : 'Unassigned';
    }

    public function getRoomStatusAttribute(): string
    {
        if (
            $this->teachingAssignment
            && Schema::hasColumn('teaching_assignments', 'room_id')
            && $this->teachingAssignment->room_id
        ) {
            return 'Assigned';
        }

        // Future "Preferred Rooms" module, if it lands as its own table
        // keyed by subject_offering_id.
        if (
            Schema::hasTable('room_assignments')
            && Schema::hasColumn('room_assignments', 'subject_offering_id')
            && DB::table('room_assignments')->where('subject_offering_id', $this->id)->exists()
        ) {
            return 'Assigned';
        }

        return 'Unassigned';
    }

    /**
     * The single "how far along is this class" summary shown on the
     * Index page — fully derived, checked in this order:
     *
     *   1. Archived   — the Academic Term itself has been Archived
     *                    (set in the Academic Terms module; read-only
     *                    from here).
     *   2. Completed  — the Academic Term's class_end_date has
     *                    already passed.
     *   3. Scheduled  — the future Scheduler has written a schedule
     *                    row for this offering.
     *   4. Ready for Scheduling — Faculty AND Room are both assigned.
     *   5. Room Assigned    — only Room is assigned.
     *   6. Faculty Assigned — only Faculty is assigned.
     *   7. Generated  — none of the above; fresh from generation.
     *
     * Requires academicTerm and teachingAssignment to be loaded (or
     * loadable) — eager-load both wherever offerings are listed to
     * avoid N+1 queries.
     */
    public function getOverallStatusAttribute(): string
    {
        $term = $this->academicTerm;

        if ($term?->status === 'Archived') {
            return self::STATUS_ARCHIVED;
        }

        if ($term?->class_end_date && now()->gt($term->class_end_date)) {
            return self::STATUS_COMPLETED;
        }

        if ($this->hasScheduleAssigned()) {
            return self::STATUS_SCHEDULED;
        }

        $facultyAssigned = $this->faculty_status === 'Assigned';
        $roomAssigned = $this->room_status === 'Assigned';

        if ($facultyAssigned && $roomAssigned) {
            return self::STATUS_READY_FOR_SCHEDULING;
        }

        if ($roomAssigned) {
            return self::STATUS_ROOM_ASSIGNED;
        }

        if ($facultyAssigned) {
            return self::STATUS_FACULTY_ASSIGNED;
        }

        return self::STATUS_GENERATED;
    }

    /**
     * Defensive the same way room_status is — the Scheduler doesn't
     * exist yet, so this simply returns false today and starts
     * reporting Scheduled the moment a 'schedules' table with a
     * subject_offering_id column ships, with no change needed here.
     */
    private function hasScheduleAssigned(): bool
    {
        if (! Schema::hasTable('schedules') || ! Schema::hasColumn('schedules', 'subject_offering_id')) {
            return false;
        }

        return DB::table('schedules')->where('subject_offering_id', $this->id)->exists();
    }
}