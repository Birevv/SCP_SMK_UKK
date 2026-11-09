<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

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
        if (static::$current !== null) {
            return static::$current;
        }

        $attributes = Cache::remember('system_config_data', 3600, function () {
            $model = static::first();

            return $model ? $model->getAttributes() : [
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
            ];
        });

        if (! is_array($attributes)) {
            static::clearCache();
            $model = static::first();
            $attributes = $model ? $model->getAttributes() : [
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
            ];
        }

        $instance = new static;
        $instance->setRawAttributes($attributes, true);
        $instance->exists = isset($attributes['id']);

        return static::$current = $instance;
    }

    public static function clearCache(): void
    {
        static::$current = null;
        Cache::forget('system_config_data');
        Cache::forget('system_configuration_current');
    }

    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
