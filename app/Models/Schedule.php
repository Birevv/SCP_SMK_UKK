<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder forActiveCycle()
 * @method static \Illuminate\Database\Eloquent\Builder forGrade(string|int $grade)
 * @method static \Illuminate\Database\Eloquent\Builder forMajor(string|int $major)
 * @method static \Illuminate\Database\Eloquent\Builder forClassLevel(string|int $level)
 * @method static \Illuminate\Database\Eloquent\Builder forRombel(string|int $rombel)
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

    protected static function booted(): void
    {
        static::creating(function (Schedule $schedule) {
            if (empty($schedule->academic_year_id)) {
                $schedule->academic_year_id = AcademicYear::getActiveId();
            }
        });
    }

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

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject()
    {
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

    public function scopeForGrade(Builder $query, string|int $grade): Builder
    {
        return $query->whereHas('classroom', fn (Builder $q) => $q->forGrade($grade));
    }

    public function scopeForMajor(Builder $query, string|int $major): Builder
    {
        return $query->whereHas('classroom', fn (Builder $q) => $q->forMajor($major));
    }

    public function scopeForClassLevel(Builder $query, string|int $level): Builder
    {
        return $query->whereHas('classroom', fn (Builder $q) => $q->forGrade($level));
    }

    public function scopeForRombel(Builder $query, string|int $rombel): Builder
    {
        return $query->whereHas('classroom', fn (Builder $q) => $q->forRombel($rombel));
    }
}
