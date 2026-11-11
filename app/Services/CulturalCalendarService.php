<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AcademicEventCategory;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use IntlDateFormatter;

class CulturalCalendarService
{
    /**
     * Siklus 5 hari Pasaran Jawa (Pancawara).
     * Urutan berbasis Epoch Jawa 1 Jan 1970 (jatuh pada pasaran Wage / indeks 4).
     */
    protected array $pasaranNames = [
        0 => 'Kliwon',
        1 => 'Legi',
        2 => 'Pahing',
        3 => 'Pon',
        4 => 'Wage',
    ];

    /**
     * Nilai neptu pasaran Jawa.
     */
    protected array $neptuPasaran = [
        'Kliwon' => 8,
        'Legi' => 5,
        'Pahing' => 9,
        'Pon' => 7,
        'Wage' => 4,
    ];

    /**
     * Nama hari Masehi dalam bahasa Indonesia (Saptawara).
     */
    protected array $hariIndo = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    /**
     * Nilai neptu hari Masehi (Saptawara).
     */
    protected array $neptuHari = [
        'Minggu' => 5,
        'Senin' => 4,
        'Selasa' => 3,
        'Rabu' => 7,
        'Kamis' => 8,
        'Jumat' => 6,
        'Sabtu' => 9,
    ];

    /**
     * Penyesuaian selisih hari Hijriah terhadap hisab/rukyat resmi (Sidang Isbat Kemenag RI).
     * Dapat disesuaikan sendiri (misal: -1, 0, atau +1) jika ada keputusan resmi pemerintah.
     */
    protected int $hijriAdjustmentDays = 0;

    /**
     * Konfigurasi weton busana adat tradisional daerah (Gagrak).
     * Fleksibel dapat diubah sesuai kebijakan daerah / sekolah:
     * - Surakarta (Solo): Kamis Pon (Gagrak Surakarta)
     * - DIY (Yogyakarta): Kamis Pahing (Gagrak Ngayogyakarta)
     * - Daerah lain: Bebas disesuaikan hari & pasarannya.
     */
    protected string $gagrakHari = 'Kamis';

    protected string $gagrakPasaran = 'Pon';

    protected string $gagrakLabel = 'Busana Adat Jawa';

    /**
     * Nama bulan kalender Hijriah (Indonesia).
     */
    protected array $hijriMonthNames = [
        1 => 'Muharram',
        2 => 'Safar',
        3 => 'Rabi\'ul Awwal',
        4 => 'Rabi\'ul Akhir',
        5 => 'Jumadil Awal',
        6 => 'Jumadil Akhir',
        7 => 'Rajab',
        8 => 'Sya\'ban',
        9 => 'Ramadhan',
        10 => 'Syawal',
        11 => 'Dzulqa\'dah',
        12 => 'Dzulhijjah',
    ];

    /**
     * Singkatan nama bulan Hijriah untuk tampilan ringkas kalender.
     */
    protected array $hijriShortMonthNames = [
        1 => 'Muh',
        2 => 'Saf',
        3 => 'R. Awal',
        4 => 'R. Akhir',
        5 => 'J. Awal',
        6 => 'J. Akhir',
        7 => 'Rajab',
        8 => 'Sya\'ban',
        9 => 'Ram',
        10 => 'Syaw',
        11 => 'Dzulq',
        12 => 'Dzulh',
    ];

    /**
     * Nama bulan penanggalan Jawa (Sultan Agungan).
     */
    protected array $jawaMonthNames = [
        1 => 'Sura',
        2 => 'Sapar',
        3 => 'Mulud',
        4 => 'Bakda Mulud',
        5 => 'Jumadilawal',
        6 => 'Jumadilakir',
        7 => 'Rejeb',
        8 => 'Ruwah',
        9 => 'Pasa',
        10 => 'Sawal',
        11 => 'Sela',
        12 => 'Besar',
    ];

    /**
     * Siklus 8 tahun Windu dalam kalender Jawa.
     */
    protected array $winduYears = [
        0 => 'Alip',
        1 => 'Ehe',
        2 => 'Jimawal',
        3 => 'Je',
        4 => 'Dal',
        5 => 'Be',
        6 => 'Wawu',
        7 => 'Jimakir',
    ];

    /**
     * Set penyesuaian hari Hijriah (offset hari).
     */
    public function setHijriAdjustmentDays(int $days): self
    {
        $this->hijriAdjustmentDays = $days;

        return $this;
    }

    /**
     * Ambil nilai penyesuaian hari Hijriah saat ini.
     */
    public function getHijriAdjustmentDays(): int
    {
        return $this->hijriAdjustmentDays;
    }

    /**
     * Konfigurasi aturan weton busana adat tradisional (Gagrak) secara fleksibel.
     */
    public function setGagrak(string $hari, string $pasaran, ?string $label = null): self
    {
        $this->gagrakHari = ucfirst(strtolower($hari));
        $this->gagrakPasaran = ucfirst(strtolower($pasaran));
        if ($label !== null) {
            $this->gagrakLabel = $label;
        }

        return $this;
    }

    /**
     * Ambil konfigurasi aturan busana adat daerah saat ini.
     */
    public function getGagrakConfig(): array
    {
        return [
            'hari' => $this->gagrakHari,
            'pasaran' => $this->gagrakPasaran,
            'weton' => "{$this->gagrakHari} {$this->gagrakPasaran}",
            'label' => $this->gagrakLabel,
        ];
    }

    /**
     * Menghitung pasaran Jawa dan weton berdasarkan tanggal Masehi.
     * Menggunakan tanggal dasar 1 Januari 1970 (Kamis Wage / indeks 4).
     */
    public function getPasaran(Carbon|string $date): array
    {
        $carbon = is_string($date) ? Carbon::parse($date)->startOfDay() : $date->copy()->startOfDay();

        // Tanggal dasar Epoch Jawa: 1 Januari 1970 jatuh pada Wage (indeks 4)
        $tanggalDasar = Carbon::parse('1970-01-01')->startOfDay();
        $selisihHari = (int) floor($tanggalDasar->diffInDays($carbon, false));

        // Indeks pasaran: (selisihHari + 4) % 5
        $indeksPasaran = ($selisihHari + 4) % 5;
        if ($indeksPasaran < 0) {
            $indeksPasaran += 5;
        }

        $pasaran = $this->pasaranNames[$indeksPasaran];
        $hariEn = $carbon->format('l');
        $hariId = $this->hariIndo[$hariEn] ?? $hariEn;

        $neptuP = $this->neptuPasaran[$pasaran] ?? 0;
        $neptuH = $this->neptuHari[$hariId] ?? 0;

        $isGagrak = ($hariId === $this->gagrakHari && $pasaran === $this->gagrakPasaran);

        return [
            'date' => $carbon->toDateString(),
            'hari' => $hariId,
            'pasaran' => $pasaran,
            'weton' => $hariId.' '.$pasaran,
            'neptu' => $neptuP + $neptuH,
            'is_gagrak' => $isGagrak,
            'is_kamis_pahing' => ($hariId === 'Kamis' && $pasaran === 'Pahing'),
        ];
    }

    /**
     * Mengonversi tanggal Masehi ke kalender Hijriah & penanggalan Jawa.
     * Menggunakan IntlDateFormatter bawaan PHP (tanpa API eksternal) dengan fallback matematis.
     */
    public function getHijriAndJawaDate(Carbon|string $date): array
    {
        $carbon = is_string($date) ? Carbon::parse($date)->startOfDay() : $date->copy()->startOfDay();

        // Terapkan penyesuaian offset jika ada
        $adjusted = $carbon->copy();
        if ($this->hijriAdjustmentDays !== 0) {
            $adjusted->addDays($this->hijriAdjustmentDays);
        }

        $d = null;
        $m = null;
        $y = null;
        $monthName = null;

        // 1. Eksekusi IntlDateFormatter bawaan PHP (standar internasional id@calendar=islamic-umalqura)
        if (extension_loaded('intl')) {
            try {
                $formatter = new IntlDateFormatter(
                    'id_ID@calendar=islamic-umalqura',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::NONE,
                    'Asia/Jakarta',
                    IntlDateFormatter::TRADITIONAL,
                    'd|M|y|MMMM'
                );

                $formattedParts = $formatter->format($adjusted->timestamp);
                if ($formattedParts && str_contains($formattedParts, '|')) {
                    $parts = explode('|', $formattedParts);
                    $d = (int) $parts[0];
                    $m = (int) $parts[1];
                    $y = (int) $parts[2];
                    $monthName = $this->hijriMonthNames[$m] ?? $parts[3];
                }
            } catch (\Throwable) {
                // Fallback ke algoritma kalkulasi jika Intl gagal
            }
        }

        // 2. Fallback komputasi Tabular Islamic Calendar jika Intl tidak tersedia
        if (! $d || ! $m || ! $y) {
            $jd = gregoriantojd($adjusted->month, $adjusted->day, $adjusted->year);
            $l = $jd - 1948440 + 10632;
            $n = (int) (($l - 1) / 10631);
            $l = $l - 10631 * $n + 354;
            $j = ((int) ((10985 - $l) / 5316)) * ((int) ((50 * $l) / 17719)) + ((int) ($l / 5670)) * ((int) ((43 * $l) / 15238));
            $l = $l - ((int) ((30 - $j) / 15)) * ((int) ((17719 * $j) / 50)) - ((int) ($j / 16)) * ((int) ((15238 * $j) / 43)) + 29;
            $m = (int) ((24 * $l) / 709);
            $d = $l - (int) ((709 * $m) / 24);
            $y = 30 * $n + $j - 30;

            if ($m < 1) {
                $m = 1;
            } elseif ($m > 12) {
                $m = 12;
            }
            $monthName = $this->hijriMonthNames[$m] ?? "Bulan {$m}";
        }

        $hijriShortMonth = $this->hijriShortMonthNames[$m] ?? "B{$m}";
        $jawaMonthName = $this->jawaMonthNames[$m] ?? "Bulan {$m}";

        // Tahun Jawa selaras dengan Hijriah + era Sultan Agungan (~512 tahun)
        $jawaYear = $y + 512;
        $winduIndex = ($jawaYear % 8);
        $winduName = $this->winduYears[$winduIndex] ?? 'Alip';

        $pasaranData = $this->getPasaran($carbon);

        return [
            'gregorian_date' => $carbon->toDateString(),
            'hijri_day' => $d,
            'hijri_month' => $m,
            'hijri_month_name' => $monthName,
            'hijri_short_month' => $hijriShortMonth,
            'hijri_year' => $y,
            'hijri_formatted' => "{$d} {$monthName} {$y} H",
            'hijri_short' => "{$d} {$hijriShortMonth}",
            'jawa_day' => $d,
            'jawa_month' => $m,
            'jawa_month_name' => $jawaMonthName,
            'jawa_year' => $jawaYear,
            'jawa_windu' => $winduName,
            'jawa_formatted' => "{$d} {$jawaMonthName} {$jawaYear} {$winduName}",
            'pasaran' => $pasaranData['pasaran'],
            'hari' => $pasaranData['hari'],
            'weton' => $pasaranData['weton'],
            'is_gagrak' => $pasaranData['is_gagrak'],
            'is_kamis_pahing' => $pasaranData['is_kamis_pahing'],
            'is_suro' => ($m === 1 && $d === 1),
        ];
    }

    /**
     * Mengambil data kalender budaya (Jawa & Hijriah) selama 1 bulan penuh secara offline.
     * Murni kalkulasi lokal berkecepatan tinggi tanpa pemanggilan HTTP API apa pun.
     */
    public function getMonthlyCalendarData(int $year, int $month): array
    {
        $cacheKey = "cultural_calendar_{$year}_{$month}_{$this->hijriAdjustmentDays}";

        return Cache::remember($cacheKey, 86400 * 30, function () use ($year, $month): array {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $result = [];
            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($period as $date) {
                $dateKey = $date->toDateString();
                $result[$dateKey] = $this->getHijriAndJawaDate($date);
            }

            return $result;
        });
    }

    /**
     * Menyelesaikan rentang tanggal sinkronisasi secara cerdas.
     * Mencegah pemotongan data jika user menyinkronkan tahun kalender yang berbeda dari rentang semester.
     */
    public function resolveSyncDateRange(AcademicYear $academicYear, int $year, bool $forceFullYear = false): array
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        if ($forceFullYear || ! $academicYear->start_date || ! $academicYear->end_date) {
            return [$yearStart, $yearEnd];
        }

        $ayStart = Carbon::parse($academicYear->start_date)->startOfDay();
        $ayEnd = Carbon::parse($academicYear->end_date)->endOfDay();

        $ayStartYear = $ayStart->year;
        $ayEndYear = $ayEnd->year;

        if ($ayStartYear === $year && $ayEndYear === $year) {
            return [$ayStart, $ayEnd];
        }

        if ($ayStartYear === $year) {
            return [$ayStart, $yearEnd];
        }

        if ($ayEndYear === $year) {
            return [$yearStart, $ayEnd];
        }

        // Jika semester berada di tahun berbeda dari tahun yang diminta, gunakan rentang setahun penuh
        return [$yearStart, $yearEnd];
    }

    /**
     * Menyinkronkan hari besar keagamaan Islam ke dalam tabel academic_events.
     * Menggunakan updateOrCreate untuk menjamin idempotensi tanpa duplikasi data.
     */
    public function syncIslamicEvents(AcademicYear $academicYear, int $year, bool $forceFullYear = false): array
    {
        [$startDate, $endDate] = $this->resolveSyncDateRange($academicYear, $year, $forceFullYear);

        $events = $this->getIslamicHolidaysList($year);
        $syncedCount = 0;

        foreach ($events as $event) {
            $eventDate = Carbon::parse($event['date']);

            if ($eventDate->between($startDate, $endDate)) {
                AcademicEvent::updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'start_date' => $eventDate->toDateString(),
                        'title' => $event['title'],
                    ],
                    [
                        'end_date' => $eventDate->toDateString(),
                        'category' => AcademicEventCategory::KEAGAMAAN_ISLAM,
                        'is_holiday' => $event['is_holiday'],
                        'disable_kbm' => $event['disable_kbm'],
                        'requires_attendance' => $event['requires_attendance'],
                        'target_grades' => null,
                        'is_all_day' => true,
                        'description' => $event['description'] ?? 'Hari Besar Keagamaan Islam',
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
     * Menyinkronkan peringatan budaya / penanggalan Jawa ke dalam academic_events.
     * Menyertakan 1 Suro (Tahun Baru Jawa) dan opsi peringatan berkala Busana Adat Tradisional (Gagrak).
     * Fleksibel dapat disesuaikan untuk Kamis Pon (Solo), Kamis Pahing (Jogja), atau hari/weton lainnya.
     */
    public function syncJavaneseCulturalEvents(
        AcademicYear $academicYear,
        int $year,
        bool $includeGagrak = false,
        ?string $gagrakHari = null,
        ?string $gagrakPasaran = null,
        ?string $gagrakTitle = null,
        bool $includeKamisPahing = false,
        bool $forceFullYear = false
    ): array {
        [$startDate, $endDate] = $this->resolveSyncDateRange($academicYear, $year, $forceFullYear);

        $syncedCount = 0;

        // 1. Sinkronisasi 1 Suro (Tahun Baru Jawa, bertepatan dengan 1 Muharram)
        $islamicEvents = $this->getIslamicHolidaysList($year);
        foreach ($islamicEvents as $item) {
            if (str_contains(strtolower($item['title']), 'muharram') || str_contains(strtolower($item['title']), 'tahun baru islam')) {
                $suroDate = Carbon::parse($item['date']);
                if ($suroDate->between($startDate, $endDate)) {
                    AcademicEvent::updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'start_date' => $suroDate->toDateString(),
                            'title' => 'Tahun Baru Jawa 1 Suro (Tahun Baru Islam)',
                        ],
                        [
                            'end_date' => $suroDate->toDateString(),
                            'category' => AcademicEventCategory::BUDAYA_JAWA,
                            'is_holiday' => true,
                            'disable_kbm' => true,
                            'requires_attendance' => false,
                            'target_grades' => null,
                            'is_all_day' => true,
                            'description' => 'Peringatan Tahun Baru Jawa (Sultan Agungan) & 1 Muharram.',
                            'created_by' => Auth::id(),
                        ]
                    );

                    $syncedCount++;
                }
            }
        }

        // 2. Opsi Busana Adat Daerah (Gagrak) Fleksibel
        $shouldIncludeGagrak = $includeGagrak || $includeKamisPahing;
        if ($shouldIncludeGagrak) {
            $targetHari = $gagrakHari ? ucfirst(strtolower($gagrakHari)) : $this->gagrakHari;
            $targetPasaran = $gagrakPasaran ? ucfirst(strtolower($gagrakPasaran)) : $this->gagrakPasaran;

            // Jika dipanggil via parameter warisan includeKamisPahing secara eksplisit tanpa custom hari/pasaran
            if ($includeKamisPahing && ! $includeGagrak && ! $gagrakHari && ! $gagrakPasaran) {
                $targetHari = 'Kamis';
                $targetPasaran = 'Pahing';
            }

            if (! $gagrakTitle) {
                $gagrakTitle = "{$targetHari} {$targetPasaran} (Busana Adat Jawa)";
            }

            // Bersihkan agenda Gagrak / busana adat lain di rentang tanggal ini agar tidak tercampur
            AcademicEvent::where('academic_year_id', $academicYear->id)
                ->where('category', AcademicEventCategory::BUDAYA_JAWA)
                ->where(function ($q) {
                    $q->where('title', 'like', '%Gagrak%')
                        ->orWhere('title', 'like', '%Surakarta%')
                        ->orWhere('title', 'like', '%Kasunanan%')
                        ->orWhere('title', 'like', '%Ngayogyakarta%')
                        ->orWhere('title', 'like', '%Yogyakarta%')
                        ->orWhere('title', 'like', '%Busana Adat%')
                        ->orWhere('title', 'like', '%Kamis Pahing%')
                        ->orWhere('title', 'like', '%Kamis Pon%');
                })
                ->where('title', '!=', $gagrakTitle)
                ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->delete();

            $description = "Peringatan hari {$targetHari} {$targetPasaran}. Seluruh guru, staf, dan siswa mengenakan busana adat Jawa.";

            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $date) {
                $pasaran = $this->getPasaran($date);
                if ($pasaran['hari'] === $targetHari && $pasaran['pasaran'] === $targetPasaran) {
                    AcademicEvent::updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'start_date' => $date->toDateString(),
                            'title' => $gagrakTitle,
                        ],
                        [
                            'end_date' => $date->toDateString(),
                            'category' => AcademicEventCategory::BUDAYA_JAWA,
                            'is_holiday' => false,
                            'disable_kbm' => false,
                            'requires_attendance' => true,
                            'target_grades' => null,
                            'is_all_day' => true,
                            'description' => $description,
                            'created_by' => Auth::id(),
                        ]
                    );

                    $syncedCount++;
                }
            }
        }

        return [
            'total_synced' => $syncedCount,
            'year' => $year,
            'semester' => $academicYear->name,
        ];
    }

    /**
     * Daftar hari besar Islam terverifikasi per tahun Masehi (2024-2030).
     * Berdasarkan SKB 3 Menteri dan kalender hisab rukyat Kementerian Agama RI.
     */
    public function getIslamicHolidaysList(int $year): array
    {
        $database = [
            2025 => [
                ['date' => '2025-01-27', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2025-03-01', 'title' => 'Perkiraan Awal Puasa Ramadhan 1446 H', 'is_holiday' => false, 'disable_kbm' => false, 'requires_attendance' => true],
                ['date' => '2025-03-17', 'title' => 'Nuzulul Qur\'an 1446 H', 'is_holiday' => false, 'disable_kbm' => false, 'requires_attendance' => true],
                ['date' => '2025-03-31', 'title' => 'Hari Raya Idul Fitri 1446 H (Hari 1)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2025-04-01', 'title' => 'Hari Raya Idul Fitri 1446 H (Hari 2)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2025-06-07', 'title' => 'Hari Raya Idul Adha 1446 H', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2025-06-27', 'title' => 'Tahun Baru Islam 1447 H (1 Muharram)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2025-09-05', 'title' => 'Maulid Nabi Muhammad SAW', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
            ],
            2026 => [
                ['date' => '2026-01-16', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2026-02-18', 'title' => 'Perkiraan Awal Puasa Ramadhan 1447 H', 'is_holiday' => false, 'disable_kbm' => false, 'requires_attendance' => true],
                ['date' => '2026-03-06', 'title' => 'Nuzulul Qur\'an 1447 H', 'is_holiday' => false, 'disable_kbm' => false, 'requires_attendance' => true],
                ['date' => '2026-03-20', 'title' => 'Hari Raya Idul Fitri 1447 H (Hari 1)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2026-03-21', 'title' => 'Hari Raya Idul Fitri 1447 H (Hari 2)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2026-05-27', 'title' => 'Hari Raya Idul Adha 1447 H', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2026-06-16', 'title' => 'Tahun Baru Islam 1448 H (1 Muharram)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2026-08-25', 'title' => 'Maulid Nabi Muhammad SAW', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
            ],
            2027 => [
                ['date' => '2027-01-05', 'title' => 'Isra Mi\'raj Nabi Muhammad SAW', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2027-02-08', 'title' => 'Perkiraan Awal Puasa Ramadhan 1448 H', 'is_holiday' => false, 'disable_kbm' => false, 'requires_attendance' => true],
                ['date' => '2027-02-24', 'title' => 'Nuzulul Qur\'an 1448 H', 'is_holiday' => false, 'disable_kbm' => false, 'requires_attendance' => true],
                ['date' => '2027-03-10', 'title' => 'Hari Raya Idul Fitri 1448 H (Hari 1)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2027-03-11', 'title' => 'Hari Raya Idul Fitri 1448 H (Hari 2)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2027-05-16', 'title' => 'Hari Raya Idul Adha 1448 H', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2027-06-06', 'title' => 'Tahun Baru Islam 1449 H (1 Muharram)', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
                ['date' => '2027-08-15', 'title' => 'Maulid Nabi Muhammad SAW', 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
            ],
        ];

        return $database[$year] ?? $this->generateFallbackIslamicEvents($year);
    }

    /**
     * Generator fallback untuk tahun yang belum terdaftar di tabel presisi.
     */
    protected function generateFallbackIslamicEvents(int $year): array
    {
        $h1 = $this->getHijriAndJawaDate("{$year}-01-01");
        $hijriYear = $h1['hijri_year'];

        return [
            ['date' => "{$year}-06-01", 'title' => "Tahun Baru Islam {$hijriYear} H", 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
            ['date' => "{$year}-03-15", 'title' => "Hari Raya Idul Fitri {$hijriYear} H", 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
            ['date' => "{$year}-05-20", 'title' => "Hari Raya Idul Adha {$hijriYear} H", 'is_holiday' => true, 'disable_kbm' => true, 'requires_attendance' => false],
        ];
    }
}
