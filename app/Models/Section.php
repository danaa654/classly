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
        'capacity',
        'status',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'capacity' => 'integer',
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

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    |
    | College, Program, and Year Level are intentionally NOT stored on the
    | sections table — they are always resolved through the Curriculum
    | relationship so a Section can never drift out of sync with the
    | curriculum it belongs to.
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
}