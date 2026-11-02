<?php

namespace App\Models;

use App\Observers\AcademicYearObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(AcademicYearObserver::class)]
class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public static function getActiveId(): ?int
    {
        return static::where('is_active', true)->value('id') ?? static::first()?->id;
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AcademicEvent::class);
    }
}
