<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder forActiveCycle()
 */
class Schedule extends Model
{
    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'room_id',
        'subject_name',
        'day_of_week',
        'start_time',
        'end_time',
        'cycle_type',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function scopeForActiveCycle($query)
    {
        $active_cycle = SystemConfiguration::first()?->active_cycle ?? 'Putaran 1';
        return $query->where('cycle_type', $active_cycle);
    }
}
