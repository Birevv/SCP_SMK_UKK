<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
