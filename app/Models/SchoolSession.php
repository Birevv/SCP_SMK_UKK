<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'session_number',
        'label',
        'start_time',
        'end_time',
        'duration_minutes',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /**
     * Ambil data sesi untuk hari tertentu dari database.
     * Jika database kosong / belum di-seed, otomatis fallback ke default.
     *
     * @return array<int, array{start: string, end: string, label: string, duration?: int}>
     */
    public static function getSessionsForDay(?string $dayOfWeek = null): array
    {
        $dayOfWeek = $dayOfWeek ?: 'Senin';

        try {
            $records = static::query()
                ->where('day_of_week', $dayOfWeek)
                ->orderBy('session_number')
                ->get();

            if ($records->isNotEmpty()) {
                $sessions = [];
                foreach ($records as $record) {
                    $sessions[$record->session_number] = [
                        'start' => substr((string) $record->start_time, 0, 5),
                        'end' => substr((string) $record->end_time, 0, 5),
                        'label' => $record->label ?: "Jam ke - {$record->session_number}",
                        'duration' => $record->duration_minutes,
                    ];
                }

                return $sessions;
            }
        } catch (\Throwable $e) {
            // Jika tabel belum di-migrate, fallback ke default
        }

        return static::getDefaultSessions($dayOfWeek);
    }

    /**
     * Konfigurasi default sesi:
     * - Default 40 menit per 1 sesi
     * - Senin - Kamis: s/d jam ke-10 (15:30)
     * - Jumat: s/d jam ke-8 (14:00)
     */
    public static function getDefaultSessions(?string $dayOfWeek = null): array
    {
        if ($dayOfWeek === 'Jumat') {
            return [
                1 => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam ke - 1', 'duration' => 40],
                2 => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam ke - 2', 'duration' => 40],
                3 => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam ke - 3', 'duration' => 40],
                // Istirahat 1: 09:15 - 09:30 (15 menit)
                4 => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam ke - 4', 'duration' => 40],
                5 => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam ke - 5', 'duration' => 40],
                6 => ['start' => '10:50', 'end' => '11:30', 'label' => 'Jam ke - 6', 'duration' => 40],
                // Istirahat Sholat Jumat: 11:30 - 12:40 (70 menit)
                7 => ['start' => '12:40', 'end' => '13:20', 'label' => 'Jam ke - 7', 'duration' => 40],
                8 => ['start' => '13:20', 'end' => '14:00', 'label' => 'Jam ke - 8', 'duration' => 40],
            ];
        }

        return [
            1 => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam ke - 1', 'duration' => 40],
            2 => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam ke - 2', 'duration' => 40],
            3 => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam ke - 3', 'duration' => 40],
            // Istirahat 1: 09:15 - 09:30 (15 menit)
            4 => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam ke - 4', 'duration' => 40],
            5 => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam ke - 5', 'duration' => 40],
            6 => ['start' => '10:50', 'end' => '11:30', 'label' => 'Jam ke - 6', 'duration' => 40],
            // Istirahat 2 (Dzuhur/ISHOMA): 11:30 - 12:30 (60 menit)
            7 => ['start' => '12:30', 'end' => '13:10', 'label' => 'Jam ke - 7', 'duration' => 40],
            8 => ['start' => '13:10', 'end' => '13:50', 'label' => 'Jam ke - 8', 'duration' => 40],
            // Istirahat 3 (Ashar/Jeda Sore): 13:50 - 14:10 (20 menit)
            9 => ['start' => '14:10', 'end' => '14:50', 'label' => 'Jam ke - 9', 'duration' => 40],
            10 => ['start' => '14:50', 'end' => '15:30', 'label' => 'Jam ke - 10', 'duration' => 40],
        ];
    }

    /**
     * Seed atau reset konfigurasi sesi ke nilai default untuk semua hari sekolah.
     */
    public static function seedDefaults(): void
    {
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        foreach ($days as $day) {
            $defaultSessions = static::getDefaultSessions($day);

            foreach ($defaultSessions as $number => $data) {
                static::updateOrCreate(
                    [
                        'day_of_week' => $day,
                        'session_number' => $number,
                    ],
                    [
                        'label' => $data['label'],
                        'start_time' => $data['start'],
                        'end_time' => $data['end'],
                        'duration_minutes' => $data['duration'] ?? 40,
                    ]
                );
            }

            // Hapus sesi di luar batas default jika ada
            static::where('day_of_week', $day)
                ->where('session_number', '>', count($defaultSessions))
                ->delete();
        }
    }

    /**
     * Generate sesi otomatis untuk hari tertentu dengan durasi per sesi kustom.
     */
    public static function generateSessionsForDay(
        string $dayOfWeek,
        int $durationMinutes = 40,
        string $firstStartTime = '07:15',
        int $totalSessions = 10,
        int $break1After = 3,
        int $break1Minutes = 15,
        int $break2After = 6,
        int $break2Minutes = 60,
        int $break3After = 8,
        int $break3Minutes = 20
    ): void {
        $currentTime = Carbon::createFromFormat('H:i', substr($firstStartTime, 0, 5));

        for ($num = 1; $num <= $totalSessions; $num++) {
            $startStr = $currentTime->format('H:i');
            $endTime = $currentTime->copy()->addMinutes($durationMinutes);
            $endStr = $endTime->format('H:i');

            static::updateOrCreate(
                [
                    'day_of_week' => $dayOfWeek,
                    'session_number' => $num,
                ],
                [
                    'label' => "Jam ke - {$num}",
                    'start_time' => $startStr,
                    'end_time' => $endStr,
                    'duration_minutes' => $durationMinutes,
                ]
            );

            // Majukan waktu untuk sesi berikutnya
            $currentTime = $endTime->copy();

            // Tambahkan jeda istirahat jika sesuai
            if ($num === $break1After && $break1Minutes > 0) {
                $currentTime->addMinutes($break1Minutes);
            } elseif ($num === $break2After && $break2Minutes > 0) {
                $currentTime->addMinutes($break2Minutes);
            } elseif ($num === $break3After && $break3Minutes > 0) {
                $currentTime->addMinutes($break3Minutes);
            }
        }

        // Hapus sesi lama yang nomornya melebihi total sesi baru
        static::where('day_of_week', $dayOfWeek)
            ->where('session_number', '>', $totalSessions)
            ->delete();
    }
}
