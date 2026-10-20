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
        'is_active',
    ];

    protected $casts = [
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
}
