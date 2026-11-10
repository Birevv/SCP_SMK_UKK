<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AcademicEventCategory;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KaldikImportService
{
    /**
     * Map nama bulan Indonesia ke angka bulan (1-12).
     */
    protected array $monthMap = [
        'januari' => 1, 'jan' => 1,
        'februari' => 2, 'peb' => 2, 'feb' => 2,
        'maret' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'jun' => 6,
        'juli' => 7, 'jul' => 7,
        'agustus' => 8, 'agt' => 8, 'agu' => 8,
        'september' => 9, 'sept' => 9, 'sep' => 9,
        'oktober' => 10, 'okt' => 10,
        'november' => 11, 'nopember' => 11, 'nov' => 11, 'nop' => 11,
        'desember' => 12, 'des' => 12,
    ];

    /**
     * Parse file Kaldik (CSV, XLSX, XLS, TXT) menjadi array terstruktur.
     *
     * @return array{
     *     school_name: ?string,
     *     academic_year_name: ?string,
     *     start_year: ?int,
     *     end_year: ?int,
     *     events: array<int, array>,
     *     effective_weeks: array<string, array>,
     *     categories_count: array<string, int>,
     *     total_events: int
     * }
     */
    public function parse(string $filePath): array
    {
        $rawRows = $this->loadRowsFromFile($filePath);

        if (empty($rawRows)) {
            return [
                'school_name' => null,
                'academic_year_name' => null,
                'start_year' => null,
                'end_year' => null,
                'events' => [],
                'effective_weeks' => [],
                'categories_count' => [],
                'total_events' => 0,
            ];
        }

        // 1. Ekstrak Header Dokumen (Tahun Ajaran & Sekolah)
        $meta = $this->extractMetadata($rawRows);

        // 2. Cek apakah format tabular standar (misal kolom: start_date, end_date, title)
        if ($this->isTabularFormat($rawRows)) {
            return $this->parseTabularRows($rawRows, $meta);
        }

        // 3. Parse Kalender Grid Kaldik (Format Standar Sekolah: Blok Bulan & List Agenda di Bawahnya)
        return $this->parseKaldikGridRows($rawRows, $meta);
    }

    /**
     * Mengimpor hasil parse ke database dalam satu transaksi.
     *
     * @param  array  $parsedData  Hasil dari parse()
     * @param  int|string|null  $targetAcademicYearId  ID Tahun Ajaran spesifik, atau null/'auto' untuk otomatis cocokkan semester
     * @param  bool  $autoSyncDates  Sinkronkan start_date dan end_date pada AcademicYear jika belum terisi
     * @param  int|null  $userId  ID User pembuat
     * @return array{
     *     success: bool,
     *     total_events: int,
     *     created_count: int,
     *     updated_count: int,
     *     academic_years_affected: array<int, string>,
     *     categories_count: array<string, int>,
     *     effective_weeks_summary: array
     * }
     */
    public function import(
        array $parsedData,
        int|string|null $targetAcademicYearId = null,
        bool $autoSyncDates = true,
        ?int $userId = null
    ): array {
        $events = $parsedData['events'] ?? [];

        if (empty($events)) {
            return [
                'success' => false,
                'total_events' => 0,
                'created_count' => 0,
                'updated_count' => 0,
                'academic_years_affected' => [],
                'categories_count' => [],
                'effective_weeks_summary' => $parsedData['effective_weeks'] ?? [],
            ];
        }

        return DB::transaction(function () use ($parsedData, $events, $targetAcademicYearId, $autoSyncDates, $userId) {
            $createdCount = 0;
            $updatedCount = 0;
            $affectedYears = [];
            $categoriesCount = [];

            // Inisialisasi hitungan kategori
            foreach (AcademicEventCategory::cases() as $cat) {
                $categoriesCount[$cat->value] = 0;
            }

            // Cache mapping tahun ajaran
            $yearCache = [];

            foreach ($events as $event) {
                $yearId = $this->resolveAcademicYearId(
                    $event,
                    $targetAcademicYearId,
                    $parsedData,
                    $autoSyncDates,
                    $yearCache
                );

                if (! $yearId) {
                    continue;
                }

                $yearModel = $yearCache[$yearId] ?? AcademicYear::find($yearId);
                if ($yearModel && ! in_array($yearModel->name, $affectedYears, true)) {
                    $affectedYears[$yearId] = $yearModel->name;
                }

                $categoryValue = $event['category'] instanceof AcademicEventCategory
                    ? $event['category']->value
                    : (string) $event['category'];

                $payload = [
                    'end_date' => $event['end_date'],
                    'category' => $categoryValue,
                    'target_grades' => ! empty($event['target_grades']) ? $event['target_grades'] : null,
                    'is_holiday' => (bool) ($event['is_holiday'] ?? false),
                    'disable_kbm' => (bool) ($event['disable_kbm'] ?? false),
                    'requires_attendance' => (bool) ($event['requires_attendance'] ?? true),
                    'is_all_day' => (bool) ($event['is_all_day'] ?? true),
                    'start_time' => $event['start_time'] ?? null,
                    'end_time' => $event['end_time'] ?? null,
                    'description' => $event['description'] ?? 'Diimpor dari Dokumen Kaldik Resmi',
                    'created_by' => $userId,
                ];

                $existing = AcademicEvent::where('academic_year_id', $yearId)
                    ->whereDate('start_date', $event['start_date'])
                    ->where('title', $event['title'])
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $updatedCount++;
                } else {
                    AcademicEvent::create(array_merge([
                        'academic_year_id' => $yearId,
                        'start_date' => $event['start_date'],
                        'title' => $event['title'],
                    ], $payload));
                    $createdCount++;
                }

                if (isset($categoriesCount[$categoryValue])) {
                    $categoriesCount[$categoryValue]++;
                } else {
                    $categoriesCount[$categoryValue] = 1;
                }
            }

            return [
                'success' => true,
                'total_events' => count($events),
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'academic_years_affected' => array_values($affectedYears),
                'categories_count' => $categoriesCount,
                'effective_weeks_summary' => $parsedData['effective_weeks'] ?? [],
            ];
        });
    }

    /**
     * Membaca baris dari file (PhpSpreadsheet jika ada/didukung, fallback ke CSV native).
     */
    protected function loadRowsFromFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Jika file XLSX atau XLS
        if (in_array($extension, ['xlsx', 'xls', 'xlsm'], true)) {
            try {
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                return $sheet->toArray(null, true, true, false);
            } catch (\Throwable $e) {
                Log::warning('PhpSpreadsheet load error: '.$e->getMessage());
            }
        }

        // CSV atau TXT Parser
        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Hapus BOM jika ada
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        // Deteksi delimiter
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $bestDelimiter, '"', '\\');
        }

        return $rows;
    }

    /**
     * Mengekstrak judul Tahun Ajaran dan nama Sekolah dari baris awal.
     */
    protected function extractMetadata(array $rows): array
    {
        $schoolName = null;
        $academicYearName = null;
        $startYear = null;
        $endYear = null;

        // Periksa 5 baris pertama
        for ($i = 0; $i < min(5, count($rows)); $i++) {
            $rowStr = implode(' ', array_filter($rows[$i], fn ($c) => trim((string) $c) !== ''));

            if (preg_match('/TAHUN\s+(?:AJARAN|PELAJARAN)\s+(\d{4})[\/\-](\d{4})/i', $rowStr, $m)) {
                $startYear = (int) $m[1];
                $endYear = (int) $m[2];
                $academicYearName = "{$startYear}/{$endYear}";
            }

            if (preg_match('/(SMKN?\s+[0-9A-Z\s]+|SMK\s+[0-9A-Z\s]+|SMA\s+[0-9A-Z\s]+|SMP\s+[0-9A-Z\s]+)/i', $rowStr, $m)) {
                $schoolName = trim($m[1]);
            }
        }

        return [
            'school_name' => $schoolName,
            'academic_year_name' => $academicYearName,
            'start_year' => $startYear,
            'end_year' => $endYear,
        ];
    }

    /**
     * Memeriksa apakah baris file memiliki format tabular standar (header kolom).
     */
    protected function isTabularFormat(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        foreach (array_slice($rows, 0, 3) as $row) {
            $rowStr = strtolower(implode(',', $row));
            if (str_contains($rowStr, 'start_date') || str_contains($rowStr, 'tanggal mulai')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parser jika file berbentuk tabel kolom biasa.
     */
    protected function parseTabularRows(array $rows, array $meta): array
    {
        $headers = null;
        $events = [];
        $categoriesCount = [];

        foreach ($rows as $row) {
            $cleanRow = array_map('trim', $row);
            if ($headers === null) {
                if (count(array_filter($cleanRow)) >= 2) {
                    $headers = array_map('strtolower', $cleanRow);
                }

                continue;
            }

            $mapped = [];
            foreach ($headers as $idx => $h) {
                $mapped[$h] = $cleanRow[$idx] ?? '';
            }

            $title = $mapped['title'] ?? $mapped['nama agenda'] ?? $mapped['agenda'] ?? '';
            $startDate = $mapped['start_date'] ?? $mapped['tanggal mulai'] ?? '';
            $endDate = $mapped['end_date'] ?? $mapped['tanggal selesai'] ?? $startDate;

            if ($title && $startDate) {
                $parsed = $this->determineEventAttributes($title, $startDate, $endDate);
                $events[] = $parsed;
                $cat = $parsed['category'];
                $categoriesCount[$cat] = ($categoriesCount[$cat] ?? 0) + 1;
            }
        }

        return [
            'school_name' => $meta['school_name'],
            'academic_year_name' => $meta['academic_year_name'],
            'start_year' => $meta['start_year'],
            'end_year' => $meta['end_year'],
            'events' => $events,
            'effective_weeks' => [],
            'categories_count' => $categoriesCount,
            'total_events' => count($events),
        ];
    }

    /**
     * Parser utama untuk format Kalender Pendidikan Blok Bulan & List Agenda di Bawahnya.
     */
    protected function parseKaldikGridRows(array $rawRows, array $meta): array
    {
        $currentBlocks = [];
        $rawEventList = [];
        $effectiveWeeksRows = [];
        $inEffectiveWeeksSection = false;

        $totalRows = count($rawRows);

        for ($r = 0; $r < $totalRows; $r++) {
            $row = $rawRows[$r];
            $rowStr = implode(' ', array_filter($row, fn ($c) => trim((string) $c) !== ''));

            // Cek apakah memasuki bagian PERHITUNGAN MINGGU EFEKTIF
            if (stripos($rowStr, 'PERHITUNGAN MINGGU EFEKTIF') !== false) {
                $inEffectiveWeeksSection = true;
            }

            if ($inEffectiveWeeksSection) {
                $effectiveWeeksRows[] = $row;

                continue;
            }

            // Cek apakah baris ini memuat Header Bulan (misal: "JULI 2026", "AGUSTUS 2026", "SEPTEMBER 2026")
            $rowMonths = [];
            foreach ($row as $colIdx => $val) {
                $valTrim = trim((string) $val);
                if (preg_match('/^(JANUARI|FEBRUARI|MARET|APRIL|MEI|JUNI|JULI|AGUSTUS|SEPTEMBER|OKTOBER|NOVEMBER|NOPEMBER|DESEMBER)\s+(\d{4})$/i', $valTrim, $m)) {
                    $monthNum = $this->monthMap[strtolower($m[1])] ?? 1;
                    $yearNum = (int) $m[2];
                    $rowMonths[] = [
                        'col' => $colIdx,
                        'month_name' => strtoupper($m[1]),
                        'month' => $monthNum,
                        'year' => $yearNum,
                    ];
                }
            }

            if (! empty($rowMonths)) {
                $currentBlocks = $rowMonths;

                continue;
            }

            // Jika dalam blok bulan aktif, cari cell-cell deskripsi agenda
            if (! empty($currentBlocks)) {
                foreach ($row as $colIdx => $val) {
                    $valTrim = trim((string) $val);
                    if ($valTrim === '') {
                        continue;
                    }

                    // Abaikan baris hari kalender (AHAD, SENIN, ...) dan angka tanggal murni
                    if (preg_match('/^(AHAD|SENIN|SELASA|RABU|KAMIS|JUM[ÁA]T|SABTU|\d{1,2})$/ui', $valTrim)) {
                        continue;
                    }

                    // Abaikan angka acak (misal koordinat pixel atau cell angka)
                    if (is_numeric($valTrim)) {
                        continue;
                    }

                    // Abaikan tanda tangan kepala sekolah
                    if (stripos($valTrim, 'Kepala Sekolah') !== false || stripos($valTrim, 'Bantul,') !== false || stripos($valTrim, 'NIP ') !== false) {
                        continue;
                    }

                    // Tentukan blok bulan mana yang menjadi wadah kolom ini
                    $matchedBlock = null;
                    foreach ($currentBlocks as $b) {
                        if ($colIdx >= $b['col']) {
                            $matchedBlock = $b;
                        }
                    }
                    if (! $matchedBlock) {
                        $matchedBlock = $currentBlocks[0];
                    }

                    $rawEventList[] = [
                        'row' => $r,
                        'col' => $colIdx,
                        'block_month' => $matchedBlock['month'],
                        'block_year' => $matchedBlock['year'],
                        'text' => $valTrim,
                    ];
                }
            }
        }

        // 4. Proses dan Gabungkan Kelanjutan Teks (misal baris berikut diawali "&" seperti "& PSAS Ganjil kelas XII")
        $consolidatedEvents = $this->consolidateEventEntries($rawEventList);

        // 5. Ubah setiap entri teks agenda menjadi entri tanggal & atribut lengkap
        $events = [];
        $categoriesCount = [];
        foreach (AcademicEventCategory::cases() as $cat) {
            $categoriesCount[$cat->value] = 0;
        }

        foreach ($consolidatedEvents as $entry) {
            $parsed = $this->parseEventEntry($entry['text'], $entry['block_month'], $entry['block_year'], $meta);
            if ($parsed) {
                // Tentukan semester
                $startDate = Carbon::parse($parsed['start_date']);
                $parsed['semester'] = ($startDate->month >= 7 && $startDate->month <= 12) ? 'ganjil' : 'genap';
                $parsed['academic_year'] = $meta['academic_year_name'] ?? sprintf('%04d/%04d', $meta['start_year'] ?? $startDate->year, ($meta['start_year'] ?? $startDate->year) + 1);

                $events[] = $parsed;
                $catVal = $parsed['category'];
                if (isset($categoriesCount[$catVal])) {
                    $categoriesCount[$catVal]++;
                } else {
                    $categoriesCount[$catVal] = 1;
                }
            }
        }

        // 6. Parse Bagian Perhitungan Minggu Efektif
        $effectiveWeeks = $this->parseEffectiveWeeksSection($effectiveWeeksRows);

        return [
            'school_name' => $meta['school_name'],
            'academic_year_name' => $meta['academic_year_name'],
            'start_year' => $meta['start_year'],
            'end_year' => $meta['end_year'],
            'events' => $events,
            'effective_weeks' => $effectiveWeeks,
            'categories_count' => $categoriesCount,
            'total_events' => count($events),
        ];
    }

    /**
     * Menggabungkan sel kelanjutan (misal yang diawali '&' atau menyambung kalimat agenda sebelumnya).
     */
    protected function consolidateEventEntries(array $rawEventList): array
    {
        $consolidated = [];
        $lastByCol = [];

        foreach ($rawEventList as $entry) {
            $colKey = $entry['col'];
            $text = $entry['text'];

            // Jika teks diawali tanda ampersand '&' atau tidak memiliki tanda titik dua ':'
            // dan memiliki entri sebelumnya di kolom yang sama dengan selisih baris <= 2
            if (
                str_starts_with($text, '&') &&
                isset($lastByCol[$colKey]) &&
                ($entry['row'] - $lastByCol[$colKey]['row']) <= 2
            ) {
                $prevIndex = $lastByCol[$colKey]['index'];
                $consolidated[$prevIndex]['text'] .= ' '.$text;
                $lastByCol[$colKey]['row'] = $entry['row'];

                continue;
            }

            $newIndex = count($consolidated);
            $consolidated[] = $entry;
            $lastByCol[$colKey] = [
                'index' => $newIndex,
                'row' => $entry['row'],
            ];
        }

        return $consolidated;
    }

    /**
     * Parsing satu string baris agenda menjadi entri tanggal, nama, kategori, target tingkat, dll.
     */
    protected function parseEventEntry(string $text, int $blockMonth, int $blockYear, array $meta): ?array
    {
        $text = trim($text);

        if (! str_contains($text, ':')) {
            return null;
        }

        $parts = explode(':', $text, 2);
        $datePart = trim($parts[0]);
        $title = trim($parts[1]);

        if ($title === '') {
            return null;
        }

        // Regex nama bulan Indonesia
        $monthRegex = '(januari|februari|peb|feb|maret|mar|april|apr|mei|may|juni|jun|juli|jul|agustus|agt|agu|september|sept|sep|oktober|okt|november|nopember|nov|nop|desember|des)\.?';

        $startDay = null;
        $endDay = null;
        $month = $blockMonth;
        $year = $blockYear;

        // Pola 1: Rentang hari "14 s.d 18 Sept. 2026" / "1 - 10 Juli 2025" / "27-31 Juli 2026"
        if (preg_match('/^(\d{1,2})\s*(?:s\.?d\.?|s\/d|-)\s*(\d{1,2})\s*'.$monthRegex.'(?:\s+(\d{4}))?$/i', $datePart, $m)) {
            $startDay = (int) $m[1];
            $endDay = (int) $m[2];
            $month = $this->monthMap[strtolower(rtrim($m[3], '.'))] ?? $blockMonth;
            // Gunakan tahun dari blok bulan kalender untuk mengeliminasi typo tahun file lama
            $year = $blockYear;
        }
        // Pola 2: Daftar tanggal dengan koma "26, 28 Maret"
        elseif (preg_match('/^(\d{1,2})\s*,\s*(\d{1,2})\s*'.$monthRegex.'(?:\s+(\d{4}))?$/i', $datePart, $m)) {
            $startDay = (int) $m[1];
            $endDay = (int) $m[2];
            $month = $this->monthMap[strtolower(rtrim($m[3], '.'))] ?? $blockMonth;
            $year = $blockYear;
        }
        // Pola 3: Tanggal tunggal "13 Juli 2026" / "1 Mei 2027"
        elseif (preg_match('/^(\d{1,2})\s*'.$monthRegex.'(?:\s+(\d{4}))?$/i', $datePart, $m)) {
            $startDay = (int) $m[1];
            $endDay = $startDay;
            $month = $this->monthMap[strtolower(rtrim($m[2], '.'))] ?? $blockMonth;
            $year = $blockYear;
        }
        // Pola 4: Angka rentang tanpa nama bulan eksplisit "1-4"
        elseif (preg_match('/^(\d{1,2})\s*(?:-|s\.?d\.?)\s*(\d{1,2})$/i', $datePart, $m)) {
            $startDay = (int) $m[1];
            $endDay = (int) $m[2];
            $month = $blockMonth;
            $year = $blockYear;
        }
        // Pola 5: Angka tunggal tanpa nama bulan "13"
        elseif (preg_match('/^(\d{1,2})$/', $datePart, $m)) {
            $startDay = (int) $m[1];
            $endDay = $startDay;
            $month = $blockMonth;
            $year = $blockYear;
        }

        if (! $startDay) {
            return null;
        }

        $startDateStr = sprintf('%04d-%02d-%02d', $year, $month, $startDay);
        $endDateStr = sprintf('%04d-%02d-%02d', $year, $month, $endDay);

        return $this->determineEventAttributes($title, $startDateStr, $endDateStr, $text);
    }

    /**
     * Menentukan kategori, target tingkat kelas, dan flags presensi/KBM dari judul agenda.
     */
    protected function determineEventAttributes(
        string $title,
        string $startDateStr,
        string $endDateStr,
        ?string $rawText = null
    ): array {
        $lowerTitle = strtolower($title);
        $category = AcademicEventCategory::LAINNYA->value;
        $targetGrades = null;
        $isHoliday = false;
        $disableKbm = false;
        $requiresAttendance = false;

        // 1. Deteksi Target Tingkat Kelas
        if (preg_match('/kelas\s+x(?:[,\s]+|(?:\s*&\s*))xi\b/i', $title) || preg_match('/kls\s+x(?:[,\s]+|(?:\s*&\s*))xi\b/i', $title)) {
            // Jika memuat juga kelas XII (seperti pada event PSTS Ganjil kls X, XI & PSAS XII), berlaku untuk seluruh sekolah
            if (stripos($title, 'xii') !== false) {
                $targetGrades = null;
            } else {
                $targetGrades = ['X', 'XI'];
            }
        } elseif (preg_match('/kelas\s+xii\b/i', $title) || preg_match('/kls\s+xii\b/i', $title)) {
            $targetGrades = ['XII'];
        } elseif (preg_match('/kelas\s+xi\b/i', $title) || preg_match('/kls\s+xi\b/i', $title)) {
            $targetGrades = ['XI'];
        } elseif (preg_match('/kelas\s+x\b/i', $title) || preg_match('/kls\s+x\b/i', $title)) {
            $targetGrades = ['X'];
        }

        // 2. Deteksi Kategori Agenda & Pengaturan Flag
        // A. Libur Nasional
        if (preg_match('/(hari kemerdekaan|maulid nabi|tahun baru|imlek|nyepi|idul fitri|hari jadi diy|wafat.*yesus|kebangkitan.*yesus|hari buruh|kenaikan.*almasih|idul adha|waisak|hari lahir pancasila|natal|cuti bersama|isra.*mi[\'’]?raj)/i', $lowerTitle)) {
            $category = AcademicEventCategory::LIBUR_NASIONAL->value;
            $isHoliday = true;
            $disableKbm = true;
            $requiresAttendance = false;

            // Pengecualian khusus: Upacara Hari Kemerdekaan 17 Agustus wajib presensi
            if (str_contains($lowerTitle, 'kemerdekaan')) {
                $requiresAttendance = true;
            }
        }
        // B. Libur Sekolah / Semester
        elseif (preg_match('/(libur akhir|libur awal ramadhan|libur akhir puasa|libur akhir semester|libur semester)/i', $lowerTitle)) {
            $category = AcademicEventCategory::LIBUR_SEKOLAH->value;
            $isHoliday = true;
            $disableKbm = true;
            $requiresAttendance = false;
        }
        // C. Upacara Bendera & Hari Guru
        elseif (preg_match('/(upacara|hari guru)/i', $lowerTitle)) {
            $category = AcademicEventCategory::UPACARA->value;
            $isHoliday = false;
            $disableKbm = true;
            $requiresAttendance = true;
        }
        // D. UKK (Uji Kompetensi Keahlian)
        elseif (preg_match('/(ukk|uji kompetensi)/i', $lowerTitle)) {
            $category = AcademicEventCategory::UKK->value;
            $targetGrades = $targetGrades ?? ['XII'];
            $isHoliday = false;
            $disableKbm = true;
            $requiresAttendance = true;
        }
        // E. PKL (Praktik Kerja Lapangan)
        elseif (preg_match('/(\bpkl\b|praktik kerja|penerjunan.*pkl|penarikan.*pkl)/i', $lowerTitle)) {
            $category = AcademicEventCategory::PKL->value;
            $targetGrades = $targetGrades ?? ['XI', 'XII'];
            $isHoliday = false;
            $disableKbm = true;
            $requiresAttendance = false;
        }
        // F. Ujian & Asesmen
        elseif (preg_match('/(psts|psas|usek|tka|ujian|asesmen|toeic|simulasi|gladi bersih|tes susulan)/i', $lowerTitle)) {
            $category = AcademicEventCategory::UJIAN->value;
            $isHoliday = false;
            $disableKbm = true;
            $requiresAttendance = true;
        }
        // G. Kegiatan Siswa & Sekolah
        elseif (preg_match('/(mpls|masuk sekolah|rapor|kelulusan|p5|hari pertama|awal masuk)/i', $lowerTitle)) {
            $category = AcademicEventCategory::KEGIATAN_SISWA->value;
            $isHoliday = false;
            $disableKbm = false;
            $requiresAttendance = true;
        }
        // H. Rapat
        elseif (preg_match('/(rapat|pleno|dinas)/i', $lowerTitle)) {
            $category = AcademicEventCategory::RAPAT->value;
            $isHoliday = false;
            $disableKbm = false;
            $requiresAttendance = true;
        }

        return [
            'title' => $title,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'category' => $category,
            'target_grades' => $targetGrades,
            'is_holiday' => $isHoliday,
            'disable_kbm' => $disableKbm,
            'requires_attendance' => $requiresAttendance,
            'is_all_day' => true,
            'description' => 'Import Kaldik Resmi',
            'raw_text' => $rawText,
        ];
    }

    /**
     * Parsing tabel Perhitungan Minggu Efektif (Kelas X, XI, XII, Semester Ganjil & Genap).
     */
    protected function parseEffectiveWeeksSection(array $rows): array
    {
        $result = [];
        $currentGrade = null;
        $currentSemester = null;

        foreach ($rows as $row) {
            $rowStr = implode(' ', array_filter($row, fn ($c) => trim((string) $c) !== ''));

            if (preg_match('/PERHITUNGAN MINGGU EFEKTIF\s+(KELAS\s+[X|V|I]+)/i', $rowStr, $m)) {
                $currentGrade = strtoupper(trim($m[1]));
                $result[$currentGrade] = [
                    'ganjil' => [],
                    'genap' => [],
                    'summary' => [],
                ];

                continue;
            }

            if (preg_match('/Semester\s+Ganjil/i', $rowStr)) {
                $currentSemester = 'ganjil';

                continue;
            }

            if (preg_match('/Semester\s+Genap/i', $rowStr)) {
                $currentSemester = 'genap';

                continue;
            }

            if ($currentGrade && $currentSemester) {
                $cols = array_values(array_filter($row, fn ($c) => trim((string) $c) !== ''));
                if (count($cols) >= 3 && is_numeric($cols[0])) {
                    $result[$currentGrade][$currentSemester][] = [
                        'no' => (int) $cols[0],
                        'bulan' => $cols[1] ?? '',
                        'total_minggu' => (int) ($cols[2] ?? 0),
                        'efektif' => (int) ($cols[3] ?? 0),
                        'tidak_efektif' => (int) ($cols[4] ?? 0),
                        'keterangan' => $cols[5] ?? '',
                    ];
                }

                if (preg_match('/Jumlah minggu efektif|Jumlah jam pelajaran/i', $rowStr)) {
                    $cleanSummary = trim($rowStr, " ,\t\n\r\0\x0B·");
                    if ($cleanSummary !== '') {
                        $result[$currentGrade]['summary'][] = $cleanSummary;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Resolusi ID AcademicYear untuk sebuah event.
     */
    protected function resolveAcademicYearId(
        array $event,
        int|string|null $targetAcademicYearId,
        array $parsedData,
        bool $autoSyncDates,
        array &$yearCache
    ): ?int {
        // Jika user memilih AcademicYear spesifik
        if (is_numeric($targetAcademicYearId) && (int) $targetAcademicYearId > 0) {
            $id = (int) $targetAcademicYearId;
            if (! isset($yearCache[$id])) {
                $yearCache[$id] = AcademicYear::find($id);
            }

            return $id;
        }

        // Mode Otomatis Sesuai Semester (Ganjil & Genap)
        $eventDate = Carbon::parse($event['start_date']);
        $isGanjil = ($eventDate->month >= 7 && $eventDate->month <= 12);
        $semesterLabel = $isGanjil ? 'Ganjil' : 'Genap';

        // Tentukan tahun ajaran dari metadata atau tanggal event
        $startYear = $parsedData['start_year'] ?? ($isGanjil ? $eventDate->year : $eventDate->year - 1);
        $endYear = $parsedData['end_year'] ?? ($startYear + 1);

        $expectedNameSlash = "{$startYear}/{$endYear} - {$semesterLabel}";
        $expectedNameHyphen = "{$startYear}-{$endYear} - {$semesterLabel}";

        // Cari di cache
        foreach ($yearCache as $id => $cachedYear) {
            if ($cachedYear && ($cachedYear->name === $expectedNameSlash || $cachedYear->name === $expectedNameHyphen)) {
                return $id;
            }
        }

        // 1. Cek Exact Match terlebih dahulu
        $academicYear = AcademicYear::where('name', $expectedNameSlash)
            ->orWhere('name', $expectedNameHyphen)
            ->first();

        // 2. Cek apakah ada nama yang diawali tahun mulai ($startYear) dan memuat semester
        if (! $academicYear) {
            $academicYear = AcademicYear::where(function ($q) use ($startYear, $semesterLabel) {
                $q->where('name', 'like', "{$startYear}%")
                    ->where('name', 'like', "%{$semesterLabel}%");
            })->first();
        }

        // Tentukan batas tanggal semester standar
        $startDate = $isGanjil ? "{$startYear}-07-01" : "{$endYear}-01-01";
        $endDate = $isGanjil ? "{$startYear}-12-31" : "{$endYear}-07-10";

        if (! $academicYear) {
            $academicYear = AcademicYear::create([
                'name' => $expectedNameSlash,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => false,
            ]);
        } elseif ($autoSyncDates && (! $academicYear->start_date || ! $academicYear->end_date)) {
            $academicYear->update([
                'start_date' => $academicYear->start_date ?? $startDate,
                'end_date' => $academicYear->end_date ?? $endDate,
            ]);
        }

        $yearCache[$academicYear->id] = $academicYear;

        return $academicYear->id;
    }
}
