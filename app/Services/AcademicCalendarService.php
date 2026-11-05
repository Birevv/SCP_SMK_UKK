<?php

namespace App\Services;

use App\Enums\AcademicEventCategory;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcademicCalendarService
{
    /**
     * Sinkronisasi hari libur nasional dari API resmi dengan dual fallback.
     * Hanya memasukkan tanggal merah yang berada dalam rentang start_date & end_date Tahun Ajaran.
     */
    public function syncNationalHolidays(AcademicYear $academicYear, int $year): array
    {
        $startDate = $academicYear->start_date ?? Carbon::create($year, 1, 1);
        $endDate = $academicYear->end_date ?? Carbon::create($year, 12, 31);

        $holidays = $this->fetchHolidaysFromApi($year);
        $syncedCount = 0;
        foreach ($holidays as $item) {
            $holidayDate = Carbon::parse($item['date']);
            $holidayName = trim($item['name']);

            // Filter ketat: Hanya simpan jika berada di dalam periode semester terpilih
            if ($holidayDate->between($startDate, $endDate)) {
                $isKemerdekaan = str_contains(strtolower($holidayName), 'kemerdekaan')
                    || str_contains(strtolower($holidayName), 'proklamasi')
                    || str_contains(strtolower($holidayName), 'upacara');

                AcademicEvent::updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'start_date' => $holidayDate->toDateString(),
                        'title' => $holidayName,
                    ],
                    [
                        'end_date' => $holidayDate->toDateString(),
                        'category' => $isKemerdekaan ? AcademicEventCategory::UPACARA : AcademicEventCategory::LIBUR_NASIONAL,
                        'is_holiday' => true,
                        'disable_kbm' => true,
                        'requires_attendance' => $isKemerdekaan, // Wajib hadir upacara pada 17 Agustus
                        'target_grades' => null,           // Berlaku untuk seluruh sekolah
                        'is_all_day' => true,
                        'description' => 'Hari Libur Nasional Resmi (Sinkronisasi Otomatis)',
                        'created_by' => Auth::id(),
                    ]
                );

                $syncedCount++;
            }
        }

        return [
            'total_synced' => $syncedCount,
            'year' => $year,
            'semester' => $academicYear->name,
        ];
    }

    /**
     * Mengambil data hari libur dengan strategi fallback bertingkat.
     */
    protected function fetchHolidaysFromApi(int $year): array
    {
        // 1. Endpoint Utama: api-hari-libur.vercel.app (SKB 3 Menteri)
        try {
            $response = Http::timeout(8)->get("https://api-hari-libur.vercel.app/api?year={$year}");
            if ($response->successful()) {
                $data = $response->json('data') ?? [];
                if (! empty($data)) {
                    return array_map(fn ($item) => [
                        'date' => $item['date'],
                        'name' => $item['description'] ?? $item['name'] ?? 'Libur Nasional',
                    ], $data);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Primary Holiday API failed: '.$e->getMessage());
        }

        // 2. Endpoint Fallback: Nager.Date Global Public API
        try {
            $response = Http::timeout(8)->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/ID");
            if ($response->successful()) {
                $data = $response->json() ?? [];
                if (! empty($data)) {
                    return array_map(fn ($item) => [
                        'date' => $item['date'],
                        'name' => $item['localName'] ?? $item['name'] ?? 'Libur Nasional',
                    ], $data);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Fallback Nager Date API failed: '.$e->getMessage());
        }

        // 3. Fallback Data Preset Lokal
        return $this->getLocalHolidayPreset($year);
    }

    /**
     * Data preset lokal dasar jika server tidak memiliki koneksi internet.
     */
    protected function getLocalHolidayPreset(int $year): array
    {
        return [
            ['date' => "{$year}-01-01", 'name' => 'Tahun Baru Masehi'],
            ['date' => "{$year}-05-01", 'name' => 'Hari Buruh Internasional'],
            ['date' => "{$year}-06-01", 'name' => 'Hari Lahir Pancasila'],
            ['date' => "{$year}-08-17", 'name' => 'Hari Kemerdekaan Republik Indonesia'],
            ['date' => "{$year}-12-25", 'name' => 'Hari Raya Natal'],
        ];
    }

    /**
     * Membangun Map In-Memory Lookup Bebas N+1 untuk Presensi dan Jurnal KBM.
     */
    public function buildExclusionsLookupMap(string $startDate, string $endDate, ?int $academicYearId = null): array
    {
        $query = AcademicEvent::query()
            ->forDateRange($startDate, $endDate)
            ->where(function ($q) {
                $q->where('is_holiday', true)
                    ->orWhere('disable_kbm', true)
                    ->orWhere('requires_attendance', false);
            });

        if ($academicYearId) {
            $query->forAcademicYear($academicYearId);
        }

        $events = $query->get([
            'id', 'title', 'start_date', 'end_date', 'is_holiday',
            'disable_kbm', 'requires_attendance', 'target_grades', 'category',
        ]);

        $exclusions = [];
        foreach ($events as $event) {
            $period = CarbonPeriod::create($event->start_date, $event->end_date);
            foreach ($period as $date) {
                $dateKey = $date->toDateString();
                $exclusions[$dateKey][] = [
                    'title' => $event->title,
                    'is_holiday' => (bool) $event->is_holiday,
                    'disable_kbm' => (bool) $event->disable_kbm,
                    'requires_attendance' => (bool) $event->requires_attendance,
                    'target_grades' => $event->target_grades,
                    'category' => $event->category instanceof AcademicEventCategory ? $event->category->value : (string) $event->category,
                ];
            }
        }

        return $exclusions;
    }

    /**
     * Helper Memory O(1): Apakah KBM Guru dinonaktifkan pada tanggal tersebut?
     * Hari Sabtu & Minggu otomatis dinonaktifkan (hari libur akhir pekan).
     */
    public function isKbmDisabled(string $dateString, ?string $grade, array $exclusionsMap): bool
    {
        $date = Carbon::parse($dateString);
        if ($date->isWeekend()) {
            return true;
        }

        if (! isset($exclusionsMap[$dateString])) {
            return false;
        }

        foreach ($exclusionsMap[$dateString] as $rule) {
            if ($rule['disable_kbm']) {
                if (empty($rule['target_grades']) || in_array($grade, $rule['target_grades'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper Memory O(1): Apakah siswa bebas dari kewajiban presensi pada tanggal tersebut?
     * Hari Sabtu & Minggu otomatis bebas presensi kecuali ada agenda khusus yang mewajibkan hadir.
     */
    public function isAttendanceExempted(string $dateString, ?string $grade, array $exclusionsMap): bool
    {
        $date = Carbon::parse($dateString);
        if ($date->isWeekend()) {
            if (isset($exclusionsMap[$dateString])) {
                foreach ($exclusionsMap[$dateString] as $rule) {
                    if ($rule['requires_attendance']) {
                        if (empty($rule['target_grades']) || in_array($grade, $rule['target_grades'], true)) {
                            return false; // Wajib hadir walau akhir pekan jika ada agenda khusus bertarget
                        }
                    }
                }
            }

            return true;
        }

        if (! isset($exclusionsMap[$dateString])) {
            return false;
        }

        foreach ($exclusionsMap[$dateString] as $rule) {
            // Bebas presensi jika libur resmi dan TIDAK mewajibkan presensi (seperti Upacara)
            if ($rule['is_holiday'] && ! $rule['requires_attendance']) {
                if (empty($rule['target_grades']) || in_array($grade, $rule['target_grades'], true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
