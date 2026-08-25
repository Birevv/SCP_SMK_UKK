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
        'academic_year_id',
        'teacher_id',
        'classroom_id',
        'room_id',
        'subject_id',
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
    
    public function academicYear() {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject() {
        return $this->belongsTo(Subject::class);
    }

    public function getSubjectNameAttribute(): ?string
    {
        return $this->subject?->name;
    }

    public function scopeForActiveCycle($query)
    {
        $active_cycle = SystemConfiguration::current()->active_cycle;
        return $query->where('cycle_type', $active_cycle);
    }
}
