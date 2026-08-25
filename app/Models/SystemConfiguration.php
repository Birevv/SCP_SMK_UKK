<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_schedule_locked',
        'updated_by',
    ];

    protected $casts = [
        'is_schedule_locked' => 'boolean',
    ];

    protected static ?self $current = null;

    public static function current(): self
    {
        return static::$current ??= (static::first() ?? new static([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]));
    }

    public static function clearCache(): void
    {
        static::$current = null;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
