<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
