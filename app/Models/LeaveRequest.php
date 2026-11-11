<?php

namespace App\Models;

use App\Enums\ReasonTypeEnum;
use App\Observers\LeaveRequestObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(LeaveRequestObserver::class)]
class LeaveRequest extends Model
{
    protected $fillable = [
        'student_id',
        'submitted_by',
        'start_date',
        'end_date',
        'leave_time',
        'attachment_photo',
        'reason_type',
        'description',
        'status',
        'rejection_note',
        'verified_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public static function normalizeReasonType(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof ReasonTypeEnum) {
            return match ($value) {
                ReasonTypeEnum::SAKIT => 'Sakit',
                ReasonTypeEnum::IZIN => 'Izin',
                ReasonTypeEnum::PULANG_CEPAT => 'Pulang Cepat',
                ReasonTypeEnum::KEGIATAN_LUAR => 'Kegiatan Luar',
            };
        }

        $str = (string) $value;

        return match (strtolower(trim(str_replace(' ', '_', $str)))) {
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'pulang_cepat' => 'Pulang Cepat',
            'kegiatan_luar' => 'Kegiatan Luar',
            default => $str,
        };
    }

    protected function reasonType(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_string($value) ? self::normalizeReasonType($value) : $value,
            set: fn ($value) => $value instanceof ReasonTypeEnum
                ? match ($value) {
                    ReasonTypeEnum::SAKIT => 'Sakit',
                    ReasonTypeEnum::IZIN => 'Izin',
                    ReasonTypeEnum::PULANG_CEPAT => 'Pulang Cepat',
                    ReasonTypeEnum::KEGIATAN_LUAR => 'Kegiatan Luar',
                }
                : $value,
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
