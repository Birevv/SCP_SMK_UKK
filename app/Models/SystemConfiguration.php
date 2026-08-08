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

    /**
     * Mendapatkan instance konfigurasi aktif.
     * Di-cache per-request menggunakan once() untuk menghindari query berulang.
     */
    public static function current(): self
    {
        return once(fn () => static::first() ?? new static([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]));
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
