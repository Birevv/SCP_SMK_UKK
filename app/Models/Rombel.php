<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rombel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'description',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
