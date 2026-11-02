<?php

namespace App\Models;

use App\Enums\AcademicEventCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicEvent extends Model
{
    protected $fillable = [
        'academic_year_id',
        'title',
        'category',
        'target_grades',
        'is_holiday',
        'disable_kbm',
        'requires_attendance',
        'start_date',
        'end_date',
        'is_all_day',
        'start_time',
        'end_time',
        'description',
        'created_by',
    ];

    protected $casts = [
        'category' => AcademicEventCategory::class,
        'target_grades' => 'array',
        'is_holiday' => 'boolean',
        'disable_kbm' => 'boolean',
        'requires_attendance' => 'boolean',
        'is_all_day' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForAcademicYear(Builder $query, ?int $academicYearId): Builder
    {
        if ($academicYearId) {
            return $query->where('academic_year_id', $academicYearId);
        }

        return $query;
    }

    public function scopeForDateRange(Builder $query, string|Carbon $start, string|Carbon $end): Builder
    {
        $startDate = is_string($start) ? $start : $start->toDateString();
        $endDate = is_string($end) ? $end : $end->toDateString();

        return $query->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }

    public function scopeActiveHolidayOrDisabledKbm(Builder $query, string|Carbon $start, string|Carbon $end): Builder
    {
        return $query->forDateRange($start, $end)
            ->where(function (Builder $q) {
                $q->where('is_holiday', true)
                    ->orWhere('disable_kbm', true)
                    ->orWhere('requires_attendance', false);
            });
    }

    public function appliesToGrade(?string $grade): bool
    {
        if (empty($this->target_grades)) {
            return true;
        }

        if (! $grade) {
            return true;
        }

        return in_array($grade, $this->target_grades, true);
    }

    public function targetGradesLabel(): string
    {
        if (empty($this->target_grades)) {
            return 'Semua Tingkat';
        }

        return implode(', ', array_map(fn ($g) => 'Kls ' . $g, $this->target_grades));
    }

    public function getColorAttribute(): string
    {
        return $this->category instanceof AcademicEventCategory
            ? (string) $this->category->getColor()
            : 'gray';
    }

    public function getIconAttribute(): ?string
    {
        return $this->category instanceof AcademicEventCategory
            ? $this->category->getIcon()
            : 'heroicon-o-calendar';
    }
}

