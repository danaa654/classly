<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'description',
        'active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Faculty members under this college
    public function faculties()
    {
        return $this->hasMany(Faculty::class);
    }

    // System users assigned to this college
    public function users()
    {
        return $this->hasMany(User::class);
    }
}