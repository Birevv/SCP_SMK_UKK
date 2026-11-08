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
        'start_session',
        'end_session',
        'start_time',
        'end_time',
        'cycle_type',
    ];

    protected $casts = [
        'start_session' => 'integer',
        'end_session' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Schedule $schedule) {
            if (empty($schedule->academic_year_id)) {
                $schedule->academic_year_id = AcademicYear::getActiveId();
            }
        });

        static::saving(function (Schedule $schedule) {
            // Jika start_session atau end_session kosong, coba deteksi dari jam sesi yang aktif
            if ((empty($schedule->start_session) || empty($schedule->end_session)) && $schedule->day_of_week && $schedule->start_time && $schedule->end_time) {
                $sessions = SchoolSession::getSessionsForDay($schedule->day_of_week);
                $startHm = substr((string) $schedule->start_time, 0, 5);
                $endHm = substr((string) $schedule->end_time, 0, 5);

                foreach ($sessions as $num => $sess) {
                    if (empty($schedule->start_session) && $sess['start'] === $startHm) {
                        $schedule->start_session = $num;
                    }
                    if (empty($schedule->end_session) && $sess['end'] === $endHm) {
                        $schedule->end_session = $num;
                    }
                }
            }

            // Jika start_session dan end_session ada, pastikan start_time dan end_time selalu sinkron
            if ($schedule->start_session && $schedule->end_session && $schedule->day_of_week) {
                $sessions = SchoolSession::getSessionsForDay($schedule->day_of_week);
                if (isset($sessions[$schedule->start_session])) {
                    $schedule->start_time = substr($sessions[$schedule->start_session]['start'], 0, 5).':00';
                }
                if (isset($sessions[$schedule->end_session])) {
                    $schedule->end_time = substr($sessions[$schedule->end_session]['end'], 0, 5).':00';
                }
            }
        });
    }

    public static function syncTimesFromSchoolSessions(?string $dayOfWeek = null): int
    {
        $days = $dayOfWeek ? [$dayOfWeek] : ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $updatedCount = 0;

        foreach ($days as $day) {
            $sessions = SchoolSession::getSessionsForDay($day);
            if (empty($sessions)) {
                continue;
            }

            $schedules = static::where('day_of_week', $day)
                ->whereNotNull('start_session')
                ->whereNotNull('end_session')
                ->get();

            foreach ($schedules as $schedule) {
                $startSession = $sessions[$schedule->start_session] ?? null;
                $endSession = $sessions[$schedule->end_session] ?? null;

                if ($startSession && $endSession) {
                    $newStart = substr((string) $startSession['start'], 0, 5).':00';
                    $newEnd = substr((string) $endSession['end'], 0, 5).':00';

                    if (substr((string) $schedule->start_time, 0, 5) !== substr($newStart, 0, 5) || substr((string) $schedule->end_time, 0, 5) !== substr($newEnd, 0, 5)) {
                        $schedule->update([
                            'start_time' => $newStart,
                            'end_time' => $newEnd,
                        ]);
                        $updatedCount++;
                    }
                }
            }
        }

        return $updatedCount;
    }

    public function getDynamicStartTimeAttribute(): string
    {
        if ($this->start_session && $this->day_of_week) {
            $sessions = SchoolSession::getSessionsForDay($this->day_of_week);
            if (isset($sessions[$this->start_session])) {
                return $sessions[$this->start_session]['start'];
            }
        }

        return substr((string) $this->start_time, 0, 5);
    }

    public function getDynamicEndTimeAttribute(): string
    {
        if ($this->end_session && $this->day_of_week) {
            $sessions = SchoolSession::getSessionsForDay($this->day_of_week);
            if (isset($sessions[$this->end_session])) {
                return $sessions[$this->end_session]['end'];
            }
        }

        return substr((string) $this->end_time, 0, 5);
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
