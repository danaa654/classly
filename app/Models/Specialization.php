<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $fillable = [
        'program_id',
        'code',
        'name',
        'active',
    ];

    /**
     * A specialization belongs to one program.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}