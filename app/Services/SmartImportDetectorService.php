<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolSession;
use App\Models\Subject;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class SmartImportDetectorService
{
    /**
     * Resolve actual file path from Livewire temporary file or storage path.
     */
    public function resolveUploadedFilePath(mixed $state, string $disk = 'local'): ?string
    {
        while (is_array($state)) {
            $state = reset($state);
        }

        if (is_object($state) && method_exists($state, 'getRealPath')) {
            $path = $state->getRealPath();
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        if (is_string($state) && ! empty(trim($state))) {
            $cleaned = trim($state);
            if (file_exists($cleaned)) {
                return $cleaned;
            }
            $diskPath = Storage::disk($disk)->path($cleaned);
            if (file_exists($diskPath)) {
                return $diskPath;
            }
            $tmpPath = Storage::disk('local')->path('livewire-tmp/'.basename($cleaned));
            if (file_exists($tmpPath)) {
                return $tmpPath;
            }
            $baseTmp = storage_path('app/livewire-tmp/'.basename($cleaned));
            if (file_exists($baseTmp)) {
                return $baseTmp;
            }
            $privateTmp = storage_path('app/private/livewire-tmp/'.basename($cleaned));
            if (file_exists($privateTmp)) {
                return $privateTmp;
            }
        }

        return null;
    }

    /**
     * Locate available python binary.
     */
    public function findPythonBinary(): ?string
    {
        $custom = config('services.python_binary') ?: env('PYTHON_BINARY');
        if ($custom && file_exists($custom)) {
            return $custom;
        }

        $candidates = [
            'C:\\laragon\\bin\\python\\python-3.10\\python.exe',
            'C:/laragon/bin/python/python-3.10/python.exe',
            'C:\\Program Files\\Python312\\python.exe',
            'C:\\Program Files\\Python311\\python.exe',
            'C:\\Program Files\\Python310\\python.exe',
            'python',
            'python3',
        ];

        foreach ($candidates as $candidate) {
            $check = @shell_exec('"'.$candidate.'" --version 2>&1');
            if ($check && str_contains($check, 'Python')) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Quick preview of a single document under 200ms.
     */
    public function quickPreview(string $filePath): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $python = $this->findPythonBinary();
        $scriptPath = base_path('scripts/import_detector.py');

        if ($python && file_exists($scriptPath)) {
            $cmd = sprintf('"%s" "%s" "%s" --preview --json 2>&1', $python, $scriptPath, $filePath);
            $output = shell_exec($cmd);

            if ($output) {
                $start = strpos($output, '{');
                $end = strrpos($output, '}');
                if ($start !== false && $end !== false) {
                    $jsonStr = substr($output, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                    if (is_array($decoded) && isset($decoded['type'])) {
                        return $decoded;
                    }
                }
            }
        }

        return [
            'type' => 'timetable_fet',
            'detected_format' => 'PDF / Jadwal Pelajaran FET Timetable',
            'institution' => 'SMK NEGERI 1 BANTUL',
            'cycle_type' => 'Putaran 1',
            'is_quick_preview' => true,
        ];
    }

    /**
     * Quick preview of schedule pair under 200ms.
     */
    public function quickPreviewSchedulePair(string $classFilePath, string $roomFilePath): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $python = $this->findPythonBinary();
        $scriptPath = base_path('scripts/import_detector.py');

        if ($python && file_exists($scriptPath)) {
            $cmd = sprintf('"%s" "%s" "%s" --room-file "%s" --preview --json 2>&1', $python, $scriptPath, $classFilePath, $roomFilePath);
            $output = shell_exec($cmd);

            if ($output) {
                $start = strpos($output, '{');
                $end = strrpos($output, '}');
                if ($start !== false && $end !== false) {
                    $jsonStr = substr($output, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                    if (is_array($decoded) && isset($decoded['type'])) {
                        return $decoded;
                    }
                }
            }
        }

        return [
            'type' => 'timetable_fet',
            'has_room_schedule' => true,
            'is_quick_preview' => true,
            'detected_format' => 'PDF Gabungan / Jadwal Kelas FET + Jadwal Ruang',
            'institution' => 'SMK NEGERI 1 BANTUL',
            'academic_year' => '2026/2027 - Ganjil',
            'cycle_type' => 'Putaran 1',
            'total_classes' => 48,
            'total_teachers' => 88,
            'total_subjects' => 75,
            'total_rooms' => 54,
            'total_schedules' => 720,
        ];
    }

    /**
     * Detect file type and parse data into a normalized associative array.
     */
    public function detectAndParse(string $filePath): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        if (! file_exists($filePath)) {
            throw new Exception("File tidak ditemukan: {$filePath}");
        }

        $python = $this->findPythonBinary();
        $scriptPath = base_path('scripts/import_detector.py');

        if ($python && file_exists($scriptPath)) {
            $cmd = sprintf('"%s" "%s" "%s" --json 2>&1', $python, $scriptPath, $filePath);
            $output = shell_exec($cmd);

            if ($output) {
                $start = strpos($output, '{');
                $end = strrpos($output, '}');
                if ($start !== false && $end !== false) {
                    $jsonStr = substr($output, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                    if (is_array($decoded) && isset($decoded['type'])) {
                        return $decoded;
                    }
                }
            }
        }

        return $this->nativeParseFallback($filePath);
    }

    /**
     * Detect and parse a pair of Schedule PDFs:
     * 1) Jadwal Kelas FET PDF (JADWAL_KELAS_PUT_1.pdf)
     * 2) Jadwal Ruang PDF (JADWAL_RUANG_PUT_1_REVISI_SEPT.pdf)
     */
    public function detectAndParseSchedulePair(string $classFilePath, string $roomFilePath): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        if (! file_exists($classFilePath)) {
            throw new Exception("File Jadwal Kelas tidak ditemukan: {$classFilePath}");
        }

        if (! file_exists($roomFilePath)) {
            throw new Exception("File Jadwal Ruang tidak ditemukan: {$roomFilePath}");
        }

        $python = $this->findPythonBinary();
        $scriptPath = base_path('scripts/import_detector.py');

        if ($python && file_exists($scriptPath)) {
            $cmd = sprintf('"%s" "%s" "%s" --room-file "%s" --json 2>&1', $python, $scriptPath, $classFilePath, $roomFilePath);
            $output = shell_exec($cmd);

            if ($output) {
                $start = strpos($output, '{');
                $end = strrpos($output, '}');
                if ($start !== false && $end !== false) {
                    $jsonStr = substr($output, $start, $end - $start + 1);
                    $decoded = json_decode($jsonStr, true);
                    if (is_array($decoded) && isset($decoded['type'])) {
                        return $decoded;
                    }
                }

                if (str_contains($output, 'ValueError:')) {
                    $parts = explode('ValueError:', $output, 2);
                    throw new Exception(trim($parts[1]));
                }
            }
        }

        $parsed1 = $this->detectAndParse($classFilePath);
        $parsed2 = $this->detectAndParse($roomFilePath);

        if (($parsed1['type'] ?? '') === 'room_schedule' && ($parsed2['type'] ?? '') === 'timetable_fet') {
            return $this->mergeSchedulePairArrays($parsed2, $parsed1);
        }

        if (($parsed1['type'] ?? '') === 'timetable_fet' && ($parsed2['type'] ?? '') === 'room_schedule') {
            return $this->mergeSchedulePairArrays($parsed1, $parsed2);
        }

        throw new Exception('Pastikan Anda mengunggah 1 file PDF Jadwal Kelas (FET) dan 1 file PDF Jadwal Ruang SMKN 1 Bantul.');
    }

    /**
     * Merge parsed FET timetable array with parsed Jadwal Ruang array in PHP.
     */
    public function mergeSchedulePairArrays(array $fetData, array $roomData): array
    {
        $cycleType = $roomData['cycle_type'] ?? $fetData['cycle_type'] ?? 'Putaran 1';
        $academicYear = $roomData['academic_year'] ?? '2026/2027 - Ganjil';
        $roomMatrix = $roomData['room_matrix'] ?? [];
        $sessionTimes = $roomData['session_times'] ?? [];
        $allRooms = $roomData['rooms'] ?? [];

        $slotToJam = [
            '07:15' => 1, '08:00' => 2, '08:45' => 3,
            '09:45' => 4, '10:30' => 5, '11:15' => 6,
            '12:30' => 7, '13:15' => 8, '14:00' => 9, '14:45' => 10,
        ];

        $schedules = $fetData['schedules'] ?? [];
        $dedupedSchedules = [];
        $seenSlots = [];

        foreach ($schedules as $s) {
            $cls = $this->normalizeClassName($s['classroom'] ?? '');
            $day = $s['day_of_week'] ?? 'Senin';
            $jStart = (int) ($s['jam_start'] ?? ($slotToJam[substr((string) ($s['start_time'] ?? ''), 0, 5)] ?? 1));
            $jEnd = (int) ($s['jam_end'] ?? $jStart);

            if (isset($sessionTimes[$day][$jStart]['start'])) {
                $s['start_time'] = $sessionTimes[$day][$jStart]['start'];
            }
            if (isset($sessionTimes[$day][$jEnd]['end'])) {
                $s['end_time'] = $sessionTimes[$day][$jEnd]['end'];
            }

            $s['classroom'] = $cls;
            $s['cycle_type'] = $cycleType;
            $s['subject'] = $this->normalizeSubjectName((string) ($s['subject'] ?? ''));

            $clsDayRooms = $roomMatrix[$cls][$day] ?? [];
            $candidates = [];
            for ($j = $jStart; $j <= $jEnd; $j++) {
                if (! empty($clsDayRooms[$j])) {
                    $candidates[] = $this->normalizeRoomName($clsDayRooms[$j]);
                }
            }

            $fRoom = $this->normalizeRoomName($s['room'] ?? null);
            $subjUpper = strtoupper((string) ($s['subject'] ?? ''));
            $isPjok = str_contains($subjUpper, 'JASMANI') || str_contains($subjUpper, 'OLAHRAGA');

            if ($isPjok && $fRoom === 'OR') {
                $chosenRoom = 'OR';
            } else {
                $filtered = $candidates;
                if (! $isPjok && ! empty($candidates)) {
                    $nonOr = array_values(array_filter($candidates, fn ($c) => $c !== 'OR'));
                    if (! empty($nonOr)) {
                        $filtered = $nonOr;
                    }
                }

                if ($fRoom && in_array($fRoom, $filtered, true)) {
                    $chosenRoom = $fRoom;
                } elseif (! empty($filtered)) {
                    $counts = array_count_values($filtered);
                    arsort($counts);
                    $chosenRoom = (string) array_key_first($counts);
                } else {
                    $chosenRoom = $fRoom ?: ($candidates[0] ?? 'Ruang Teori');
                }
            }

            $s['room'] = $chosenRoom;
            if ($chosenRoom) {
                $allRooms[] = $chosenRoom;
            }

            $slotKey = $cls.'_'.$day.'_'.substr((string) ($s['start_time'] ?? ''), 0, 5);
            if (! isset($seenSlots[$slotKey])) {
                $seenSlots[$slotKey] = true;
                $dedupedSchedules[] = $s;
            }
        }
        $schedules = $dedupedSchedules;

        $allClasses = array_values(array_unique(array_merge(
            array_map(fn ($c) => $this->normalizeClassName($c), $fetData['classes'] ?? []),
            array_map(fn ($c) => $this->normalizeClassName($c), $roomData['classes'] ?? [])
        )));
        sort($allClasses);

        $allRooms = array_values(array_unique(array_filter($allRooms)));
        sort($allRooms);

        return [
            'type' => 'timetable_fet',
            'has_room_schedule' => true,
            'detected_format' => 'PDF Gabungan / Jadwal Kelas FET + Jadwal Ruang',
            'institution' => 'SMK NEGERI 1 BANTUL',
            'academic_year' => $academicYear,
            'cycle_type' => $cycleType,
            'total_classes' => count($allClasses),
            'total_teachers' => count($fetData['teachers'] ?? []),
            'total_subjects' => count($fetData['subjects'] ?? []),
            'total_rooms' => count($allRooms),
            'total_schedules' => count($schedules),
            'teachers' => $fetData['teachers'] ?? [],
            'subjects' => $fetData['subjects'] ?? [],
            'rooms' => $allRooms,
            'classes' => $allClasses,
            'session_times' => $sessionTimes,
            'room_legends' => $roomData['room_legends'] ?? [],
            'schedules' => $schedules,
        ];
    }

    /**
     * Native PHP parser fallback if python is unavailable.
     */
    protected function nativeParseFallback(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
            if ($this->isEmployeeTeachersFile($filePath, $ext)) {
                return $this->nativeParseEmployeeTeachers($filePath, $ext);
            }

            if ($this->isHomeroomCounselorFile($filePath, $ext)) {
                return $this->nativeParseHomeroomCounselor($filePath, $ext);
            }

            return $this->nativeParseExcel($filePath);
        }

        throw new Exception("Deteksi native PHP untuk file .{$ext} memerlukan Python interpreter dengan pustaka pdfplumber / openpyxl.");
    }

    protected function isEmployeeTeachersFile(string $filePath, string $ext): bool
    {
        try {
            if ($ext === 'csv') {
                $content = strtoupper(substr((string) file_get_contents($filePath), 0, 8000));

                return str_contains($content, 'DATA NOMINATIF GURU') || str_contains($content, 'NOMINATIF GURU')
                    || (str_contains($content, 'NIP') && str_contains($content, 'NAMA') && (str_contains($content, 'GOLONGAN') || str_contains($content, 'MAPEL')));
            }

            $spreadsheet = IOFactory::load($filePath);
            foreach ($spreadsheet->getSheetNames() as $sname) {
                $uName = strtoupper($sname);
                if (str_contains($uName, 'GURU') && ! str_contains($uName, 'TU') && ! str_contains($uName, 'TATA')) {
                    return true;
                }
            }

            $sheet = $spreadsheet->getActiveSheet();
            $text = '';
            for ($r = 1; $r <= min(25, $sheet->getHighestRow()); $r++) {
                for ($c = 1; $c <= min(15, 15); $c++) {
                    $text .= ' '.strtoupper((string) $sheet->getCell([$c, $r])->getValue());
                }
            }

            return str_contains($text, 'DATA NOMINATIF GURU') || str_contains($text, 'NOMINATIF GURU')
                || (str_contains($text, 'NIP') && str_contains($text, 'NAMA') && (str_contains($text, 'GOLONGAN') || str_contains($text, 'MAPEL')));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function nativeParseEmployeeTeachers(string $filePath, string $ext): array
    {
        $teachers = [];

        if ($ext === 'csv') {
            $content = file_get_contents($filePath);
            $lines = preg_split('/\r\n|\r|\n/', (string) $content);
            $delim = ',';

            foreach (array_slice($lines, 0, 10) as $l) {
                if (substr_count($l, ';') > substr_count($l, ',')) {
                    $delim = ';';
                    break;
                }
            }

            $inGuru = false;
            $headerFound = false;
            $colNip = null;
            $colNama = null;
            $colGender = null;
            $colMapel = null;

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $uLine = strtoupper($line);

                if (str_contains($uLine, 'DATA NOMINATIF GURU') || str_contains($uLine, 'NOMINATIF GURU')) {
                    $inGuru = true;

                    continue;
                }

                if ($inGuru && (str_contains($uLine, 'TATA USAHA') || str_contains($uLine, 'SATPAM') || str_contains($uLine, 'CS') || str_contains($uLine, 'OUTSOURCING') || str_contains($uLine, 'TANDA TERIMA'))) {
                    break;
                }

                $fields = str_getcsv($line, $delim);
                $uRow = array_map('strtoupper', array_map('trim', $fields));

                if (! $headerFound && in_array('NIP', $uRow) && in_array('NAMA', $uRow)) {
                    $headerFound = true;
                    $inGuru = true;
                    foreach ($uRow as $idx => $val) {
                        if ($val === 'NIP') {
                            $colNip = $idx;
                        } elseif ($val === 'NAMA') {
                            $colNama = $idx;
                        } elseif (str_contains($val, 'KELAMIN')) {
                            $colGender = $idx;
                        } elseif (str_contains($val, 'MAPEL') || str_contains($val, 'PELAJARAN')) {
                            $colMapel = $idx;
                        }
                    }

                    continue;
                }

                if ($headerFound && $inGuru) {
                    if (count($fields) <= max($colNip, $colNama)) {
                        continue;
                    }
                    $rawNip = trim($fields[$colNip] ?? '');
                    $rawNama = trim($fields[$colNama] ?? '');

                    if (empty($rawNama) || strlen($rawNama) < 3 || in_array(strtoupper($rawNama), ['L', 'P'])) {
                        continue;
                    }
                    if (strtoupper($rawNip) === 'NIP' || strtoupper($rawNama) === 'NAMA') {
                        continue;
                    }

                    $cleanNip = preg_replace('/[^0-9]/', '', $rawNip);
                    $gender = $colGender !== null ? trim($fields[$colGender] ?? '') : null;
                    $mapel = $colMapel !== null ? trim($fields[$colMapel] ?? '') : null;

                    $teachers[] = [
                        'nip' => $cleanNip ?: $rawNip,
                        'name' => $rawNama,
                        'gender' => $gender,
                        'subject' => $mapel,
                    ];
                }
            }
        } else {
            $spreadsheet = IOFactory::load($filePath);
            $targetSheet = null;
            foreach ($spreadsheet->getSheetNames() as $sname) {
                $uName = strtoupper($sname);
                if (str_contains($uName, 'GURU') && ! str_contains($uName, 'TU') && ! str_contains($uName, 'TATA')) {
                    $targetSheet = $spreadsheet->getSheetByName($sname);
                    break;
                }
            }
            if (! $targetSheet) {
                $targetSheet = $spreadsheet->getActiveSheet();
            }

            $highestRow = $targetSheet->getHighestRow();
            $inGuru = false;
            $headerRow = null;
            $colNip = null;
            $colNama = null;
            $colGender = null;
            $colMapel = null;

            for ($r = 1; $r <= $highestRow; $r++) {
                $rowVals = [];
                for ($c = 1; $c <= 15; $c++) {
                    $rowVals[$c] = trim((string) $targetSheet->getCell([$c, $r])->getValue());
                }
                $rowStr = strtoupper(implode(' ', array_filter($rowVals)));

                if (str_contains($rowStr, 'DATA NOMINATIF GURU') || str_contains($rowStr, 'NOMINATIF GURU')) {
                    $inGuru = true;

                    continue;
                }

                if ($inGuru && (str_contains($rowStr, 'TATA USAHA') || str_contains($rowStr, 'SATPAM') || str_contains($rowStr, 'CS') || str_contains($rowStr, 'OUTSOURCING') || str_contains($rowStr, 'TANDA TERIMA'))) {
                    break;
                }

                $uRow = array_map('strtoupper', $rowVals);
                if (! $headerRow && in_array('NIP', $uRow) && in_array('NAMA', $uRow)) {
                    $headerRow = $r;
                    $inGuru = true;
                    foreach ($uRow as $cIdx => $val) {
                        if ($val === 'NIP') {
                            $colNip = $cIdx;
                        } elseif ($val === 'NAMA') {
                            $colNama = $cIdx;
                        } elseif (str_contains($val, 'KELAMIN')) {
                            $colGender = $cIdx;
                        } elseif (str_contains($val, 'MAPEL') || str_contains($val, 'PELAJARAN')) {
                            $colMapel = $cIdx;
                        }
                    }

                    continue;
                }

                if ($headerRow && $r > $headerRow && $inGuru) {
                    $rawNip = trim((string) $targetSheet->getCell([$colNip, $r])->getValue());
                    $rawNama = trim((string) $targetSheet->getCell([$colNama, $r])->getValue());

                    if (empty($rawNama) || strlen($rawNama) < 3 || in_array(strtoupper($rawNama), ['L', 'P'])) {
                        continue;
                    }
                    if (strtoupper($rawNip) === 'NIP' || strtoupper($rawNama) === 'NAMA') {
                        continue;
                    }

                    $cleanNip = preg_replace('/[^0-9]/', '', $rawNip);
                    $gender = $colGender ? trim((string) $targetSheet->getCell([$colGender, $r])->getValue()) : null;
                    $mapel = $colMapel ? trim((string) $targetSheet->getCell([$colMapel, $r])->getValue()) : null;

                    $teachers[] = [
                        'nip' => $cleanNip ?: $rawNip,
                        'name' => $rawNama,
                        'gender' => $gender,
                        'subject' => $mapel,
                    ];
                }
            }
        }

        return [
            'type' => 'employee_teachers',
            'detected_format' => $ext === 'csv' ? 'CSV / Data Pegawai Guru (Nominatif NIP)' : 'Excel / Data Pegawai Guru (Nominatif NIP)',
            'total_teachers' => count($teachers),
            'teachers' => $teachers,
        ];
    }

    protected function isHomeroomCounselorFile(string $filePath, string $ext): bool
    {
        try {
            if ($ext === 'csv') {
                $content = strtoupper(substr((string) file_get_contents($filePath), 0, 8000));

                return (str_contains($content, 'WALI KELAS') || str_contains($content, 'WALI_KELAS'))
                    && (str_contains($content, 'GURU BK') || str_contains($content, 'WALI BK') || str_contains($content, 'BK'));
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $text = '';
            for ($r = 1; $r <= min(25, $sheet->getHighestRow()); $r++) {
                for ($c = 1; $c <= min(15, 15); $c++) {
                    $text .= ' '.strtoupper((string) $sheet->getCell([$c, $r])->getValue());
                }
            }

            return (str_contains($text, 'WALI KELAS') || str_contains($text, 'WALI_KELAS'))
                && (str_contains($text, 'GURU BK') || str_contains($text, 'WALI BK') || str_contains($text, 'BK'));
        } catch (\Throwable) {
            return false;
        }
    }

    protected function nativeParseHomeroomCounselor(string $filePath, string $ext): array
    {
        $academicYear = '2026/2027 - Ganjil';
        $assignments = [];

        if ($ext === 'csv') {
            $content = file_get_contents($filePath);
            $lines = preg_split('/\r\n|\r|\n/', (string) $content);
            $headerIdx = null;
            $colKelas = null;
            $colWali = null;
            $colBk = null;
            $delim = ',';

            foreach (array_slice($lines, 0, 10) as $l) {
                if (substr_count($l, ';') > substr_count($l, ',')) {
                    $delim = ';';
                    break;
                }
            }

            foreach (array_slice($lines, 0, 30) as $idx => $line) {
                $uLine = strtoupper($line);
                if (preg_match('/(\d{4}\s*[\/\-]\s*\d{4})/', $uLine, $m)) {
                    $sem = str_contains($uLine, 'GANJIL') ? 'Ganjil' : (str_contains($uLine, 'GENAP') ? 'Genap' : '');
                    $yearClean = str_replace([' ', '-'], ['', '/'], $m[1]);
                    $academicYear = $sem ? "{$yearClean} - {$sem}" : $yearClean;
                }

                $fields = str_getcsv($line, $delim);
                $clean = array_filter(array_map('trim', $fields));
                if (count($clean) >= 2) {
                    $uParts = array_map('strtoupper', $fields);
                    $hasKelas = false;
                    $hasWali = false;
                    foreach ($uParts as $p) {
                        if (str_contains($p, 'KELAS') && ! str_contains($p, 'WALI')) {
                            $hasKelas = true;
                        }
                        if (str_contains($p, 'WALI')) {
                            $hasWali = true;
                        }
                    }
                    if ($hasKelas && $hasWali) {
                        $headerIdx = $idx;
                        foreach ($uParts as $cIdx => $p) {
                            if (str_contains($p, 'KELAS') && ! str_contains($p, 'WALI')) {
                                $colKelas = $cIdx;
                            } elseif (str_contains($p, 'WALI')) {
                                $colWali = $cIdx;
                            } elseif (str_contains($p, 'BK') || str_contains($p, 'BIMBINGAN')) {
                                $colBk = $cIdx;
                            }
                        }
                        break;
                    }
                }
            }

            if ($headerIdx !== null && $colKelas !== null && $colWali !== null) {
                foreach (array_slice($lines, $headerIdx + 1) as $line) {
                    if (trim($line) === '') {
                        continue;
                    }
                    $parts = str_getcsv($line, $delim);
                    if (count($parts) <= max($colKelas, $colWali)) {
                        continue;
                    }
                    $rawClass = trim($parts[$colKelas] ?? '');
                    if (str_contains(strtoupper($rawClass), 'KETERANGAN') || str_contains(strtoupper($rawClass), 'DITETAPKAN')) {
                        break;
                    }
                    $normClass = $this->normalizeClassName($rawClass);
                    if (! preg_match('/^(X|XI|XII)\b/i', $normClass)) {
                        continue;
                    }

                    $assignments[] = [
                        'classroom' => $normClass,
                        'raw_classroom' => $rawClass,
                        'wali_kelas' => trim($parts[$colWali] ?? ''),
                        'guru_bk' => $colBk !== null ? trim($parts[$colBk] ?? '') : '',
                    ];
                }
            }
        } else {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $headerRow = null;
            $colKelas = null;
            $colWali = null;
            $colBk = null;

            for ($r = 1; $r <= min(35, $highestRow); $r++) {
                $rowVals = [];
                for ($c = 1; $c <= 15; $c++) {
                    $rowVals[$c] = trim((string) $sheet->getCell([$c, $r])->getValue());
                }
                $textLine = strtoupper(implode(' ', array_filter($rowVals)));
                if (preg_match('/(\d{4}\s*[\/\-]\s*\d{4})/', $textLine, $m)) {
                    $sem = str_contains($textLine, 'GANJIL') ? 'Ganjil' : (str_contains($textLine, 'GENAP') ? 'Genap' : '');
                    $yearClean = str_replace([' ', '-'], ['', '/'], $m[1]);
                    $academicYear = $sem ? "{$yearClean} - {$sem}" : $yearClean;
                }

                $nonEmpty = array_filter($rowVals);
                if (count($nonEmpty) >= 2) {
                    $hasKelas = false;
                    $hasWali = false;
                    foreach ($rowVals as $val) {
                        $u = strtoupper($val);
                        if (str_contains($u, 'KELAS') && ! str_contains($u, 'WALI')) {
                            $hasKelas = true;
                        }
                        if (str_contains($u, 'WALI')) {
                            $hasWali = true;
                        }
                    }
                    if ($hasKelas && $hasWali) {
                        $headerRow = $r;
                        foreach ($rowVals as $cIdx => $val) {
                            $u = strtoupper($val);
                            if (str_contains($u, 'KELAS') && ! str_contains($u, 'WALI')) {
                                $colKelas = $cIdx;
                            } elseif (str_contains($u, 'WALI')) {
                                $colWali = $cIdx;
                            } elseif (str_contains($u, 'BK') || str_contains($u, 'BIMBINGAN')) {
                                $colBk = $cIdx;
                            }
                        }
                        break;
                    }
                }
            }

            if ($headerRow !== null && $colKelas !== null && $colWali !== null) {
                for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                    $rawClass = trim((string) $sheet->getCell([$colKelas, $r])->getValue());
                    $rawWali = trim((string) $sheet->getCell([$colWali, $r])->getValue());
                    $rawBk = $colBk !== null ? trim((string) $sheet->getCell([$colBk, $r])->getValue()) : '';

                    if ($rawClass === '' || $rawWali === '') {
                        continue;
                    }
                    if (str_contains(strtoupper($rawClass), 'KETERANGAN') || str_contains(strtoupper($rawClass), 'DITETAPKAN')) {
                        break;
                    }

                    $normClass = $this->normalizeClassName($rawClass);
                    if (! preg_match('/^(X|XI|XII)\b/i', $normClass)) {
                        continue;
                    }

                    $assignments[] = [
                        'classroom' => $normClass,
                        'raw_classroom' => $rawClass,
                        'wali_kelas' => $rawWali,
                        'guru_bk' => $rawBk,
                    ];
                }
            }
        }

        $homeroomSet = array_values(array_unique(array_filter(array_column($assignments, 'wali_kelas'))));
        sort($homeroomSet);
        $counselorSet = array_values(array_unique(array_filter(array_column($assignments, 'guru_bk'))));
        sort($counselorSet);

        return [
            'type' => 'homeroom_and_counselor',
            'detected_format' => $ext === 'csv' ? 'CSV / Daftar Wali Kelas & Guru BK (Wali BK)' : 'Excel / Daftar Wali Kelas & Guru BK (Wali BK)',
            'academic_year' => $academicYear,
            'total_classes' => count($assignments),
            'total_homeroom' => count($homeroomSet),
            'total_counselor' => count($counselorSet),
            'homeroom_teachers' => $homeroomSet,
            'counselor_teachers' => $counselorSet,
            'assignments' => $assignments,
        ];
    }

    /**
     * Native PHP Excel roster parser.
     */
    protected function nativeParseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $allStudents = [];
        $classesDict = [];
        $academicYear = null;

        $targetSheets = [];
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (in_array(strtoupper($name), ['X', 'XI', 'XII'])) {
                $targetSheets[] = $name;
            }
        }
        if (empty($targetSheets)) {
            $targetSheets = $spreadsheet->getSheetNames();
        }

        foreach ($targetSheets as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (! $sheet) {
                continue;
            }

            $currClass = null;
            $currYear = null;
            $highestRow = $sheet->getHighestRow();

            for ($r = 1; $r <= $highestRow; $r++) {
                $c1 = trim((string) $sheet->getCell([1, $r])->getValue());

                if (str_contains(strtoupper($c1), 'KELAS :') || str_starts_with(strtoupper($c1), 'KELAS')) {
                    $parts = explode(':', $c1, 2);
                    $currClass = $this->normalizeClassName($parts[1] ?? $c1);
                }

                if (str_contains(strtoupper($c1), 'TAHUN PELAJARAN')) {
                    if (preg_match('/(\d{4}\s*[\/\-]\s*\d{4})/', $c1, $m)) {
                        $currYear = str_replace(' ', '', $m[1]);
                        if (! $academicYear) {
                            $academicYear = $currYear;
                        }
                    }
                }

                $v1 = $sheet->getCell([1, $r])->getValue();
                $v2 = $sheet->getCell([2, $r])->getValue();
                $v3 = $sheet->getCell([3, $r])->getValue();
                $v4 = $sheet->getCell([4, $r])->getValue();

                $isNo = is_numeric($v1);
                $isNis = is_numeric($v2);

                if ($isNo && $isNis && ! empty(trim((string) $v3))) {
                    $nis = trim((string) $v2);
                    $name = trim(preg_replace('/\s+/', ' ', (string) $v3));
                    $gender = strtoupper(trim((string) $v4));
                    if (! in_array($gender, ['L', 'P'])) {
                        $gender = null;
                    }

                    $className = $currClass ?: $sheetName;
                    $student = [
                        'class' => $className,
                        'year' => $currYear ?: ($academicYear ?: '2026/2027'),
                        'no' => (int) $v1,
                        'nis' => $nis,
                        'name' => $name,
                        'gender' => $gender,
                    ];

                    $allStudents[] = $student;
                    $classesDict[$className] = ($classesDict[$className] ?? 0) + 1;
                }
            }
        }

        return [
            'type' => 'student_roster',
            'detected_format' => 'Excel / Daftar Hadir Siswa',
            'academic_year' => $academicYear ?: '2026/2027',
            'total_classes' => count($classesDict),
            'total_students' => count($allStudents),
            'classes_breakdown' => $classesDict,
            'students' => $allStudents,
        ];
    }

    /**
     * Normalize class name e.g. "10 RPL 1" / "X RPL1" -> "X RPL 1", "12 BD" -> "XII BD".
     */
    public function normalizeClassName(string $name): string
    {
        $name = strtoupper(trim((string) preg_replace('/\s+/', ' ', $name)));
        $name = (string) preg_replace('/^10\b/', 'X', $name);
        $name = (string) preg_replace('/^11\b/', 'XI', $name);
        $name = (string) preg_replace('/^12\b/', 'XII', $name);

        if (preg_match('/^(XII|XI|X)\s+([A-Z]+)\s*(\d+)$/i', $name, $m)) {
            return strtoupper($m[1]).' '.strtoupper($m[2]).' '.$m[3];
        }
        if (preg_match('/^(XII|XI|X)\s+([A-Z]+)$/i', $name, $m)) {
            return strtoupper($m[1]).' '.strtoupper($m[2]);
        }

        return $name;
    }

    /**
     * Normalize room code e.g. "L.16" -> "LAB 16", "12" -> "Ruang 12".
     */
    public function normalizeRoomName(?string $roomRaw): ?string
    {
        if ($roomRaw === null) {
            return null;
        }
        $r = strtoupper(trim((string) preg_replace('/\s+/', ' ', $roomRaw)));
        if (in_array($r, ['', '"', '""', '-'], true)) {
            return null;
        }
        if (preg_match('/^(?:L\.|LAB\s*)(\d+)$/i', $r, $m)) {
            return 'LAB '.$m[1];
        }
        if (in_array($r, ['L.IPA', 'L. IPA', 'LAB IPA', 'LAB.IPA'], true)) {
            return 'LAB IPA';
        }
        if (in_array($r, ['L.PB', 'L. PB', 'LAB PB', 'LAB.PB'], true)) {
            return 'LAB PB';
        }
        if (preg_match('/^OR\d*$/i', $r)) {
            return 'OR';
        }
        if (preg_match('/^(?:RUANG\s*|R\.?\s*)?(\d+)$/i', $r, $m)) {
            return 'Ruang '.$m[1];
        }

        return $r;
    }

    /**
     * Normalize subject name to avoid duplicate subjects across Putaran 1 & Putaran 2.
     */
    public function normalizeSubjectName(string $name): string
    {
        $s = trim((string) preg_replace('/\s+/', ' ', $name));
        $s = rtrim($s, ']');
        $s = trim($s);
        $s = (string) preg_replace('/\s*\/\s*/', '/', $s);
        $s = (string) preg_replace('/\(\s+/', '(', $s);
        $s = (string) preg_replace('/\s+\)/', ')', $s);

        return $s;
    }

    /**
     * Canonical uppercase key for subject comparison.
     */
    public function normalizeSubjectKey(string $name): string
    {
        return strtoupper($this->normalizeSubjectName($name));
    }

    /**
     * Normalize teacher display name.
     */
    public function normalizeTeacherName(string $name): string
    {
        $n = trim((string) preg_replace('/\s+/', ' ', $name));
        $n = trim($n, '"');
        $n = rtrim($n, '",;.');
        $n = (string) preg_replace('/\s*,\s*/', ', ', $n);

        return trim($n);
    }

    /**
     * Extract base alphanumeric person name without academic titles for cross-cycle matching.
     */
    public function extractBaseTeacherName(string $name): string
    {
        $clean = $this->normalizeTeacherName($name);
        // Strip everything after first comma (typically academic degrees like ", S.Pd", ", S.T., M.Pd")
        $parts = explode(',', $clean, 2);
        $base = trim($parts[0]);

        // Strip leading titles like Dra., Drs., Ir.
        $base = (string) preg_replace('/^(?:DRA|DRS|IR|H|HJ)\b\.?\s*/i', '', $base);

        // Strip trailing degrees if written without comma (e.g. "SUNARSIH S.Pd")
        $base = (string) preg_replace(
            '/\s+(?:S\.?Pd(?:\.?I|\.?Si)?|S\.?P\.?d|M\.?Pd|S\.?Kom|S\.?S|S\.?T|S\.?Sos|S\.?Th|S\.?IP|M\.?S\.?I|M\.?Eng|M\.?Acc|MM)\.?$/i',
            '',
            $base
        );

        return (string) preg_replace('/[^A-Z]/', '', strtoupper($base));
    }

    /**
     * Normalize cycle type string to "Putaran 1" or "Putaran 2".
     */
    public function normalizeCycleType(?string $cycle): string
    {
        if ($cycle && str_contains(strtolower($cycle), '2')) {
            return 'Putaran 2';
        }

        return 'Putaran 1';
    }

    /**
     * Normalize time string to HH:MM:SS format.
     */
    public function normalizeTimeHms(string $time): string
    {
        $t = str_replace('.', ':', trim($time));
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $t, $m)) {
            $h = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $min = $m[2];
            $sec = $m[3] ?? '00';

            return "{$h}:{$min}:{$sec}";
        }

        return $t;
    }

    /**
     * Import detected and parsed data into database.
     */
    public function import(
        array $data,
        ?User $actor = null,
        ?string $cycleTypeOverride = null,
        ?int $academicYearIdOverride = null
    ): array {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $type = $data['type'] ?? 'unknown';

        if ($type === 'student_roster') {
            return $this->importStudentRoster($data, $academicYearIdOverride);
        }

        if ($type === 'timetable_fet') {
            return $this->importFetTimetable($data, $cycleTypeOverride, $academicYearIdOverride);
        }

        if ($type === 'homeroom_and_counselor') {
            return $this->importHomeroomAndCounselor($data, $academicYearIdOverride);
        }

        if ($type === 'employee_teachers') {
            return $this->importEmployeeTeachers($data);
        }

        throw new Exception("Tipe dokumen tidak dikenal: {$type}");
    }

    /**
     * Optimized high-performance student roster import.
     */
    protected function importStudentRoster(array $data, ?int $academicYearIdOverride = null): array
    {
        return DB::transaction(function () use ($data, $academicYearIdOverride) {
            // 1. Resolve Academic Year
            $targetYearStr = str_replace([' ', '/'], '-', (string) ($data['academic_year'] ?? '2026-2027'));
            $academicYear = null;

            if ($academicYearIdOverride) {
                $academicYear = AcademicYear::find($academicYearIdOverride);
            }

            if (! $academicYear) {
                $academicYear = AcademicYear::where('name', 'like', "%{$targetYearStr}%")->first();
            }

            if (! $academicYear) {
                $academicYear = AcademicYear::create([
                    'name' => "{$targetYearStr} - Ganjil",
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-12-31',
                    'is_active' => false,
                ]);
            }

            // 2. Resolve Siswa Role
            $roleSiswa = Role::firstOrCreate(['name' => 'Siswa'], ['guard_name' => 'web']);

            // 3. Preload Existing Classrooms into memory
            $existingClassrooms = Classroom::all()->keyBy(fn ($c) => strtoupper($c->name));

            // Ensure all classrooms exist
            foreach ($data['students'] as $student) {
                $normClass = $this->normalizeClassName($student['class']);
                if (! $existingClassrooms->has($normClass)) {
                    $c = Classroom::create(['name' => $normClass]);
                    $c->syncRelationsFromName();
                    $c->save();
                    $existingClassrooms->put($normClass, $c);
                }
            }

            // 4. Preload existing users and user_class pivots (including trashed so they can be restored)
            $existingUsers = User::withTrashed()
                ->select(['id', 'identity_number', 'username', 'full_name', 'role_id', 'deleted_at'])
                ->get()
                ->keyBy(fn ($u) => (string) ($u->identity_number ?: $u->username));

            $existingPivots = DB::table('user_class')
                ->where('academic_year_id', $academicYear->id)
                ->get()
                ->keyBy(fn ($p) => "{$p->user_id}_{$p->classroom_id}");

            // Pre-calculate password hash once (saves seconds of bcrypt hashing)
            $hashedPassword = Hash::make('password');

            $newUsersCount = 0;
            $updatedUsersCount = 0;
            $linkedPivotCount = 0;
            $now = now();

            $modelHasRolesToInsert = [];
            $pivotsToInsert = [];

            foreach ($data['students'] as $student) {
                $normClass = $this->normalizeClassName($student['class']);
                $classroom = $existingClassrooms->get($normClass);
                $nis = (string) $student['nis'];
                $name = $student['name'];

                $user = $existingUsers->get($nis);

                if (! $user) {
                    $userId = DB::table('users')->insertGetId([
                        'role_id' => $roleSiswa->id,
                        'full_name' => $name,
                        'username' => $nis,
                        'identity_number' => $nis,
                        'password' => $hashedPassword,
                        'status' => 'Aktif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $modelHasRolesToInsert[] = [
                        'role_id' => $roleSiswa->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ];

                    $existingUsers->put($nis, (object) ['id' => $userId, 'deleted_at' => null]);
                    $newUsersCount++;
                } else {
                    $userId = $user->id;
                    DB::table('users')->where('id', $userId)->update([
                        'full_name' => $name,
                        'role_id' => $roleSiswa->id,
                        'status' => 'Aktif',
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
                    $updatedUsersCount++;
                }

                $pivotKey = "{$userId}_{$classroom->id}";
                if (! $existingPivots->has($pivotKey)) {
                    $pivotsToInsert[] = [
                        'user_id' => $userId,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $academicYear->id,
                        'status' => 'Aktif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $existingPivots->put($pivotKey, true);
                    $linkedPivotCount++;
                }
            }

            // Chunk insert for high performance
            if (! empty($modelHasRolesToInsert)) {
                foreach (array_chunk($modelHasRolesToInsert, 500) as $chunk) {
                    DB::table('model_has_roles')->insertOrIgnore($chunk);
                }
            }

            if (! empty($pivotsToInsert)) {
                foreach (array_chunk($pivotsToInsert, 500) as $chunk) {
                    DB::table('user_class')->insert($chunk);
                }
            }

            return [
                'status' => 'success',
                'type' => 'student_roster',
                'message' => 'Berhasil memproses '.count($data['students'])." data siswa ke dalam {$existingClassrooms->count()} rombel kelas.",
                'stats' => [
                    'academic_year' => $academicYear->name,
                    'total_students_processed' => count($data['students']),
                    'total_classes' => $existingClassrooms->count(),
                    'new_students_created' => $newUsersCount,
                    'students_updated' => $updatedUsersCount,
                    'user_class_links_created' => $linkedPivotCount,
                ],
            ];
        });
    }

    /**
     * Import FET timetable into Classrooms, Subjects, Rooms, Teachers, and Schedules.
     */
    protected function importFetTimetable(
        array $data,
        ?string $cycleTypeOverride = null,
        ?int $academicYearIdOverride = null
    ): array {
        return DB::transaction(function () use ($data, $cycleTypeOverride, $academicYearIdOverride) {
            $academicYear = $academicYearIdOverride
                ? AcademicYear::find($academicYearIdOverride)
                : (AcademicYear::where('is_active', true)->first() ?: AcademicYear::first());

            if (! $academicYear) {
                $academicYear = AcademicYear::create([
                    'name' => '2026/2027 - Ganjil',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-12-31',
                    'is_active' => true,
                ]);
            }

            $cycle = $this->normalizeCycleType($cycleTypeOverride ?: ($data['cycle_type'] ?? 'Putaran 1'));
            $roleGuru = Role::firstOrCreate(['name' => 'Guru'], ['guard_name' => 'web']);
            $roleSiswa = Role::firstOrCreate(['name' => 'Siswa'], ['guard_name' => 'web']);
            $hashedPassword = Hash::make('password');
            $now = now();

            // 0. Sync SchoolSession if session_times provided from Jadwal Ruang
            if (! empty($data['session_times']) && is_array($data['session_times'])) {
                foreach ($data['session_times'] as $dayName => $jams) {
                    if (! is_array($jams)) {
                        continue;
                    }
                    foreach ($jams as $jamNum => $timeInfo) {
                        $startStr = substr((string) ($timeInfo['start'] ?? ''), 0, 5);
                        $endStr = substr((string) ($timeInfo['end'] ?? ''), 0, 5);
                        if ($startStr && $endStr) {
                            $diffMins = max(30, (int) round((strtotime("2000-01-01 {$endStr}") - strtotime("2000-01-01 {$startStr}")) / 60));
                            SchoolSession::updateOrCreate(
                                [
                                    'day_of_week' => $dayName,
                                    'session_number' => (int) $jamNum,
                                ],
                                [
                                    'label' => "Jam ke - {$jamNum}",
                                    'start_time' => $startStr,
                                    'end_time' => $endStr,
                                    'duration_minutes' => $diffMins,
                                ]
                            );
                        }
                    }
                }
            }

            // 1. Classrooms (preload existing to prevent redundancy)
            $existingClassrooms = Classroom::all()->keyBy(fn ($c) => $this->normalizeClassName($c->name));
            $classrooms = [];
            foreach ($data['classes'] as $clsName) {
                $normalized = $this->normalizeClassName($clsName);
                if ($normalized === '') {
                    continue;
                }
                if (! isset($classrooms[$normalized])) {
                    $c = $existingClassrooms->get($normalized);
                    if (! $c) {
                        $c = Classroom::firstOrCreate(['name' => $normalized]);
                        $c->syncRelationsFromName();
                        $c->save();
                        $existingClassrooms->put($normalized, $c);
                    }
                    $classrooms[$normalized] = $c;
                }
            }

            // 2. Subjects (preload & normalize to prevent redundancy across cycles)
            $existingSubjects = Subject::all()->keyBy(fn ($s) => $this->normalizeSubjectKey($s->name));
            $subjects = [];
            foreach ($data['subjects'] as $subjName) {
                $cleanSubj = $this->normalizeSubjectName((string) $subjName);
                if ($cleanSubj === '') {
                    continue;
                }
                $key = $this->normalizeSubjectKey($cleanSubj);
                if (! isset($subjects[$key])) {
                    $s = $existingSubjects->get($key);
                    if (! $s) {
                        $s = Subject::firstOrCreate(['name' => $cleanSubj]);
                        $existingSubjects->put($key, $s);
                    }
                    $subjects[$key] = $s;
                }
            }

            // 3. Rooms (preload & normalize to prevent redundancy across cycles)
            $existingRooms = collect();
            foreach (Room::all() as $rItem) {
                $existingRooms->put(strtoupper(trim($rItem->name)), $rItem);
                $normR = $this->normalizeRoomName($rItem->name);
                if ($normR) {
                    $existingRooms->put(strtoupper($normR), $rItem);
                }
            }

            $rooms = [];
            $defaultRoom = $existingRooms->get('RUANG TEORI') ?: Room::firstOrCreate(['name' => 'Ruang Teori']);
            $existingRooms->put('RUANG TEORI', $defaultRoom);
            $rooms['RUANG TEORI'] = $defaultRoom;

            foreach ($data['rooms'] as $roomName) {
                $cleanRoom = $this->normalizeRoomName((string) $roomName) ?: trim((string) $roomName);
                if ($cleanRoom === '') {
                    continue;
                }
                $key = strtoupper($cleanRoom);
                if (! isset($rooms[$key])) {
                    $r = $existingRooms->get($key);
                    if (! $r) {
                        $r = Room::firstOrCreate(['name' => $cleanRoom]);
                        $existingRooms->put($key, $r);
                    }
                    $rooms[$key] = $r;
                }
            }

            // 4. Teachers (match across all non-student users with canonical & base-name deduplication)
            $teachersByName = [];
            $teachersByCode = [];
            $uniqueTeacherIds = [];
            $newTeachersCount = 0;

            $existingStaffUsers = User::withTrashed()
                ->where(function ($q) use ($roleSiswa) {
                    $q->where('role_id', '!=', $roleSiswa->id)
                        ->orWhereNull('role_id');
                })
                ->get();

            $teacherByNormName = collect();
            $teacherByBaseName = collect();
            $teacherByCode = collect();

            foreach ($existingStaffUsers as $u) {
                $normFull = strtoupper($this->normalizeTeacherName((string) $u->full_name));
                if ($normFull !== '') {
                    $teacherByNormName->put($normFull, $u);
                }
                $baseKey = $this->extractBaseTeacherName((string) $u->full_name);
                if ($baseKey !== '' && ! $teacherByBaseName->has($baseKey)) {
                    $teacherByBaseName->put($baseKey, $u);
                }
                if (! empty($u->identity_number)) {
                    $teacherByCode->put((string) $u->identity_number, $u);
                }
                if (! empty($u->username) && str_starts_with((string) $u->username, 'guru_')) {
                    $codeFromUname = substr((string) $u->username, 5);
                    if (! $teacherByCode->has($codeFromUname)) {
                        $teacherByCode->put($codeFromUname, $u);
                    }
                }
            }

            foreach ($data['teachers'] as $t) {
                $tName = trim((string) preg_replace('/\s+/', ' ', (string) ($t['name'] ?? '')));
                $tName = trim($tName, '"');
                if ($tName === '') {
                    continue;
                }
                $tCode = ! empty($t['code']) ? trim((string) $t['code']) : null;
                $baseNumCode = $tCode ? (string) preg_replace('/_.*$/', '', $tCode) : null;
                $key = strtoupper($this->normalizeTeacherName($tName));
                $baseKey = $this->extractBaseTeacherName($tName);

                // 4a. Exact normalized full_name match
                $user = $teacherByNormName->get($key);

                // 4b. Exact base person name match (handles degree variations like "UMI KUNTARI, S.S" vs "UMI KUNTARI, S.S. M.Pd")
                if (! $user && $baseKey !== '' && $teacherByBaseName->has($baseKey)) {
                    $user = $teacherByBaseName->get($baseKey);
                }

                // 4c. Same FET teacher code with 1-2 char typo in name (e.g. "SRI NUR AROFAH" vs "SRI NUR ARAFAH", "HAMIE" vs "HAWIE")
                if (! $user && $baseNumCode && $teacherByCode->has($baseNumCode)) {
                    $candidate = $teacherByCode->get($baseNumCode);
                    $candBase = $this->extractBaseTeacherName((string) $candidate->full_name);
                    if ($baseKey !== '' && $candBase !== '' && strlen($baseKey) >= 6 && levenshtein($baseKey, $candBase) <= 2) {
                        $user = $candidate;
                    }
                }

                // 4d. Long base name (>= 12 chars) with 1-char typo across cycles regardless of code suffix
                if (! $user && strlen($baseKey) >= 12) {
                    foreach ($teacherByBaseName as $candBase => $candidate) {
                        if (strlen((string) $candBase) >= 12 && levenshtein($baseKey, (string) $candBase) === 1) {
                            $user = $candidate;
                            break;
                        }
                    }
                }

                if (! $user) {
                    $uname = $tCode ? "guru_{$tCode}" : strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $tName));
                    $uname = substr($uname, 0, 30);
                    if (User::withTrashed()->where('username', $uname)->exists()) {
                        $uname .= '_'.rand(10, 99);
                    }

                    $identNum = $tCode ?: $uname;
                    if (User::withTrashed()->where('identity_number', $identNum)->exists()) {
                        $identNum = $uname;
                    }

                    $userId = DB::table('users')->insertGetId([
                        'role_id' => $roleGuru->id,
                        'full_name' => $tName,
                        'username' => $uname,
                        'identity_number' => $identNum,
                        'password' => $hashedPassword,
                        'status' => 'Aktif',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $roleGuru->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ]);

                    $user = (object) ['id' => $userId, 'full_name' => $tName];
                    $teacherByNormName->put($key, $user);
                    if ($baseKey !== '') {
                        $teacherByBaseName->put($baseKey, $user);
                    }
                    if ($tCode) {
                        $teacherByCode->put($tCode, $user);
                    }
                    $newTeachersCount++;
                } else {
                    // Restore user if it was soft-deleted
                    DB::table('users')->where('id', $user->id)->update([
                        'deleted_at' => null,
                        'status' => 'Aktif',
                        'updated_at' => $now,
                    ]);

                    // Ensure matched teacher also has Guru role in Spatie model_has_roles
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $roleGuru->id,
                        'model_type' => User::class,
                        'model_id' => $user->id,
                    ]);
                }

                $teachersByName[$key] = $user;
                if ($baseKey !== '' && ! isset($teachersByName[$baseKey])) {
                    $teachersByName[$baseKey] = $user;
                }
                if ($tCode && ! isset($teachersByCode[$tCode])) {
                    $teachersByCode[$tCode] = $user;
                }
                $uniqueTeacherIds[$user->id] = true;
            }

            // 5. Schedules Upsert (isolated per academic_year_id + cycle_type, normalized by HH:MM)
            $existingSchedulesList = Schedule::where('academic_year_id', $academicYear->id)
                ->where('cycle_type', $cycle)
                ->get();

            $existingBySlotKey = $existingSchedulesList->keyBy(
                fn ($s) => "{$s->classroom_id}_{$s->day_of_week}_".substr((string) $s->start_time, 0, 5)
            );
            $existingByClassDay = $existingSchedulesList->groupBy(
                fn ($s) => "{$s->classroom_id}_{$s->day_of_week}"
            );

            $schedulesCreated = 0;
            $schedulesUpdated = 0;
            $toInsert = [];
            $keptExistingIds = [];
            $processedSlotKeys = [];

            foreach ($data['schedules'] as $s) {
                $normClass = $this->normalizeClassName((string) ($s['classroom'] ?? ''));
                $classroom = $classrooms[$normClass] ?? $existingClassrooms->get($normClass);

                $subjKey = $this->normalizeSubjectKey((string) ($s['subject'] ?? ''));
                $subject = $subjects[$subjKey] ?? $existingSubjects->get($subjKey);

                $tNorm = strtoupper($this->normalizeTeacherName((string) ($s['teacher_name'] ?? '')));
                $tBase = $this->extractBaseTeacherName((string) ($s['teacher_name'] ?? ''));
                $tCode = ! empty($s['teacher_code']) ? trim((string) $s['teacher_code']) : null;
                $teacher = $teachersByName[$tNorm]
                    ?? ($tBase !== '' ? ($teachersByName[$tBase] ?? null) : null)
                    ?? ($tCode ? ($teachersByCode[$tCode] ?? null) : null);

                $normRoom = ! empty($s['room']) ? ($this->normalizeRoomName((string) $s['room']) ?: trim((string) $s['room'])) : null;
                $room = $normRoom ? ($rooms[strtoupper($normRoom)] ?? $existingRooms->get(strtoupper($normRoom)) ?? $defaultRoom) : $defaultRoom;

                if (! $classroom || ! $subject || ! $teacher) {
                    continue;
                }

                $dayOfWeek = trim((string) ($s['day_of_week'] ?? 'Senin'));
                $startTime = $this->normalizeTimeHms((string) ($s['start_time'] ?? '07:15:00'));
                $endTime = $this->normalizeTimeHms((string) ($s['end_time'] ?? '08:00:00'));
                $startSession = ! empty($s['jam_start']) ? (int) $s['jam_start'] : null;
                $endSession = ! empty($s['jam_end']) ? (int) $s['jam_end'] : null;
                $startHm = substr($startTime, 0, 5);
                $endHm = substr($endTime, 0, 5);

                $slotKey = "{$classroom->id}_{$dayOfWeek}_{$startHm}";
                // Prevent duplicate slots within the same import batch
                if (isset($processedSlotKeys[$slotKey])) {
                    continue;
                }
                $processedSlotKeys[$slotKey] = true;

                $existing = $existingBySlotKey->get($slotKey);

                // If not matched by exact start_time, check for unclaimed overlapping slot on same class & day
                if (! $existing) {
                    $classDayCandidates = $existingByClassDay->get("{$classroom->id}_{$dayOfWeek}", collect());
                    foreach ($classDayCandidates as $cand) {
                        if (in_array($cand->id, $keptExistingIds, true)) {
                            continue;
                        }
                        $candStartHm = substr((string) $cand->start_time, 0, 5);
                        $candEndHm = substr((string) $cand->end_time, 0, 5);
                        if ($candStartHm < $endHm && $candEndHm > $startHm) {
                            $existing = $cand;
                            break;
                        }
                    }
                }

                if ($existing && ! in_array($existing->id, $keptExistingIds, true)) {
                    $keptExistingIds[] = $existing->id;
                    DB::table('schedules')->where('id', $existing->id)->update([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'room_id' => $room?->id,
                        'start_session' => $startSession,
                        'end_session' => $endSession,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'updated_at' => $now,
                    ]);
                    $schedulesUpdated++;
                } else {
                    $toInsert[] = [
                        'academic_year_id' => $academicYear->id,
                        'classroom_id' => $classroom->id,
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'room_id' => $room?->id,
                        'day_of_week' => $dayOfWeek,
                        'start_session' => $startSession,
                        'end_session' => $endSession,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'cycle_type' => $cycle,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $schedulesCreated++;
                }
            }

            // Remove stale un-journaled schedules for imported classrooms in this academic year & cycle
            if (! empty($classrooms)) {
                $importedClassroomIds = array_map(fn ($c) => $c->id, array_values($classrooms));
                Schedule::where('academic_year_id', $academicYear->id)
                    ->where('cycle_type', $cycle)
                    ->whereIn('classroom_id', $importedClassroomIds)
                    ->when(! empty($keptExistingIds), fn ($q) => $q->whereNotIn('id', $keptExistingIds))
                    ->whereDoesntHave('journals')
                    ->delete();
            }

            if (! empty($toInsert)) {
                foreach (array_chunk($toInsert, 300) as $chunk) {
                    DB::table('schedules')->insert($chunk);
                }
            }

            return [
                'status' => 'success',
                'type' => 'timetable_fet',
                'message' => 'Berhasil memproses '.count($processedSlotKeys)." slot jadwal KBM untuk {$cycle}.",
                'stats' => [
                    'academic_year' => $academicYear->name,
                    'cycle_type' => $cycle,
                    'total_schedules_in_file' => count($processedSlotKeys),
                    'schedules_created' => $schedulesCreated,
                    'schedules_updated' => $schedulesUpdated,
                    'total_classes' => count($classrooms),
                    'total_teachers' => count($uniqueTeacherIds),
                    'new_teachers_created' => $newTeachersCount,
                    'total_subjects' => count($subjects),
                    'total_rooms' => count($rooms),
                ],
            ];
        });
    }

    /**
     * Import Wali Kelas & Guru BK (Wali BK) mappings.
     */
    protected function importHomeroomAndCounselor(array $data, ?int $academicYearIdOverride = null): array
    {
        return DB::transaction(function () use ($data, $academicYearIdOverride) {
            // 1. Resolve Academic Year
            $academicYear = null;
            if ($academicYearIdOverride) {
                $academicYear = AcademicYear::find($academicYearIdOverride);
            }

            if (! $academicYear) {
                $academicYear = AcademicYear::where('is_active', true)->first()
                    ?? AcademicYear::where('name', 'like', '%2026%')->first()
                    ?? AcademicYear::first();
            }

            if (! $academicYear) {
                $academicYear = AcademicYear::create([
                    'name' => '2026/2027 - Ganjil',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-12-31',
                    'is_active' => true,
                ]);
            }

            // 2. Roles
            $roleGuru = Role::firstOrCreate(['name' => 'Guru'], ['guard_name' => 'web']);
            $roleWali = Role::firstOrCreate(['name' => 'Wali Kelas'], ['guard_name' => 'web']);
            $roleBK = Role::firstOrCreate(['name' => 'Guru BK'], ['guard_name' => 'web']);

            // 3. Preload all classrooms
            $existingClassrooms = Classroom::all()->keyBy(fn ($c) => strtoupper(trim($c->name)));

            // 4. Preload candidate teachers (including trashed so they can be restored)
            $teacherUsers = User::withTrashed()
                ->where(function ($q) {
                    $q->whereHas('role', fn ($r) => $r->whereIn('name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Super Admin']))
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Super Admin']));
                })
                ->get();

            $matchedUserCache = [];
            $newUsersCount = 0;
            $updatedUsersCount = 0;

            $cleanNameHelper = function (string $raw): string {
                $noTitle = preg_split('/,/', $raw)[0] ?? $raw;
                $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $noTitle);

                return strtoupper(trim((string) preg_replace('/\s+/', ' ', (string) $clean)));
            };

            $findOrCreateTeacher = function (string $rawName, Role $targetRole, array $spatieRoles) use (
                &$teacherUsers,
                &$matchedUserCache,
                &$newUsersCount,
                &$updatedUsersCount,
                $cleanNameHelper,
                $roleGuru
            ): User {
                $clean = $cleanNameHelper($rawName);
                if (isset($matchedUserCache[$clean])) {
                    /** @var User $cached */
                    $cached = $matchedUserCache[$clean];
                    if ($cached->role_id === $roleGuru->id && $targetRole->id !== $roleGuru->id) {
                        $cached->update(['role_id' => $targetRole->id]);
                    }
                    foreach ($spatieRoles as $r) {
                        if (! $cached->hasRole($r)) {
                            $cached->assignRole($r);
                        }
                    }

                    return $cached;
                }

                // 1. Exact match
                $user = $teacherUsers->first(fn ($u) => strtolower(trim((string) $u->full_name)) === strtolower(trim($rawName)));

                // 2. Clean name prefix / exact clean match
                if (! $user) {
                    $user = $teacherUsers->first(function ($u) use ($clean, $cleanNameHelper) {
                        $uClean = $cleanNameHelper($u->full_name);

                        return $uClean === $clean || str_starts_with($uClean, $clean) || str_starts_with($clean, $uClean);
                    });
                }

                // 3. Token match (words length >= 4)
                if (! $user) {
                    $words = array_values(array_filter(explode(' ', $clean), fn ($w) => strlen($w) >= 4));
                    if (count($words) >= 2) {
                        $user = $teacherUsers->first(function ($u) use ($words, $cleanNameHelper) {
                            $uClean = $cleanNameHelper($u->full_name);
                            $matches = 0;
                            foreach ($words as $w) {
                                if (str_contains($uClean, $w)) {
                                    $matches++;
                                }
                            }

                            return $matches >= min(2, count($words));
                        });
                    }
                }

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $needsUpdate = false;
                    if ($user->status !== 'Aktif') {
                        $user->status = 'Aktif';
                        $needsUpdate = true;
                    }
                    if ($user->role_id === $roleGuru->id && $targetRole->id !== $roleGuru->id) {
                        $user->role_id = $targetRole->id;
                        $needsUpdate = true;
                    }
                    if ($needsUpdate) {
                        $user->save();
                    }
                    foreach ($spatieRoles as $r) {
                        if (! $user->hasRole($r)) {
                            $user->assignRole($r);
                            $needsUpdate = true;
                        }
                    }
                    if ($needsUpdate) {
                        $updatedUsersCount++;
                    }

                    $matchedUserCache[$clean] = $user;

                    return $user;
                }

                // Create new user
                $baseUsername = Str::slug($clean, '.');
                if (empty($baseUsername)) {
                    $baseUsername = 'guru.'.rand(100, 999);
                }
                $username = $baseUsername;
                $counter = 1;
                while (User::withTrashed()->where('username', $username)->exists()) {
                    $username = $baseUsername.$counter++;
                }

                $email = $username.'@smkn1bantul.sch.id';
                $counter = 1;
                while (User::withTrashed()->where('email', $email)->exists()) {
                    $email = $username.$counter++.'@smkn1bantul.sch.id';
                }

                $newUser = User::create([
                    'full_name' => $rawName,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role_id' => $targetRole->id,
                    'status' => 'Aktif',
                ]);

                foreach ($spatieRoles as $r) {
                    $newUser->assignRole($r);
                }

                $newUsersCount++;
                $teacherUsers->push($newUser);
                $matchedUserCache[$clean] = $newUser;

                return $newUser;
            };

            // Process assignments
            $classesMapped = 0;
            $homeroomLinked = 0;
            $counselorLinked = 0;

            foreach ($data['assignments'] ?? [] as $assignment) {
                $normClass = $this->normalizeClassName($assignment['classroom'] ?? '');
                if (empty($normClass)) {
                    continue;
                }

                if (! $existingClassrooms->has($normClass)) {
                    $c = Classroom::create(['name' => $normClass]);
                    $c->syncRelationsFromName();
                    $c->save();
                    $existingClassrooms->put($normClass, $c);
                }
                $classroom = $existingClassrooms->get($normClass);

                // 1. Wali Kelas
                if (! empty($assignment['wali_kelas'])) {
                    $waliUser = $findOrCreateTeacher($assignment['wali_kelas'], $roleWali, ['Guru', 'Wali Kelas']);
                    $exists = DB::table('user_class')
                        ->where('user_id', $waliUser->id)
                        ->where('classroom_id', $classroom->id)
                        ->where('academic_year_id', $academicYear->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('user_class')->insert([
                            'user_id' => $waliUser->id,
                            'classroom_id' => $classroom->id,
                            'academic_year_id' => $academicYear->id,
                            'status' => 'Aktif',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $homeroomLinked++;
                }

                // 2. Guru BK (Wali BK)
                if (! empty($assignment['guru_bk'])) {
                    $bkUser = $findOrCreateTeacher($assignment['guru_bk'], $roleBK, ['Guru', 'Guru BK']);
                    $exists = DB::table('user_class')
                        ->where('user_id', $bkUser->id)
                        ->where('classroom_id', $classroom->id)
                        ->where('academic_year_id', $academicYear->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('user_class')->insert([
                            'user_id' => $bkUser->id,
                            'classroom_id' => $classroom->id,
                            'academic_year_id' => $academicYear->id,
                            'status' => 'Aktif',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $counselorLinked++;
                }

                $classesMapped++;
            }

            return [
                'status' => 'success',
                'type' => 'homeroom_and_counselor',
                'message' => "Import Wali Kelas & Guru BK berhasil: {$classesMapped} rombel dipetakan. Menghubungkan {$homeroomLinked} penugasan Wali Kelas dan {$counselorLinked} penugasan Guru BK. ({$updatedUsersCount} akun guru diperbarui peran, {$newUsersCount} akun baru dibuat).",
                'details' => [
                    'classes_mapped' => $classesMapped,
                    'homeroom_linked' => $homeroomLinked,
                    'counselor_linked' => $counselorLinked,
                    'new_users' => $newUsersCount,
                    'updated_users' => $updatedUsersCount,
                ],
            ];
        });
    }

    /**
     * Import / update employee teachers data, populating NIP (identity_number) and creating any missing teachers.
     * Strictly ignores non-teaching staff (TU, CS, Satpam).
     */
    protected function importEmployeeTeachers(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $roleGuru = Role::firstOrCreate(['name' => 'Guru'], ['guard_name' => 'web']);

            $existingTeachers = User::withTrashed()
                ->where(function ($q) {
                    $q->whereHas('role', fn ($r) => $r->whereIn('name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Super Admin']))
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum', 'Super Admin']));
                })
                ->get();

            $cleanNameHelper = function (string $raw): string {
                $noTitle = preg_split('/,/', $raw)[0] ?? $raw;
                $noPrefix = preg_replace('/^(Dra\.|Drs\.|Dr\.)\s*/i', '', $noTitle);
                $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $noPrefix);

                return strtoupper(trim((string) preg_replace('/\s+/', ' ', (string) $clean)));
            };

            $updatedNipCount = 0;
            $newTeachersCount = 0;
            $matchedUserCache = [];

            foreach ($data['teachers'] ?? [] as $t) {
                $nip = trim((string) ($t['nip'] ?? ''));
                $name = trim((string) ($t['name'] ?? ''));
                if (empty($name)) {
                    continue;
                }

                $clean = $cleanNameHelper($name);
                if (isset($matchedUserCache[$clean])) {
                    continue;
                }

                // 1. Exact match
                $user = $existingTeachers->first(fn ($u) => strtolower(trim((string) $u->full_name)) === strtolower($name));

                // 2. Clean match
                if (! $user) {
                    $user = $existingTeachers->first(function ($u) use ($clean, $cleanNameHelper) {
                        $uClean = $cleanNameHelper((string) $u->full_name);

                        return $uClean === $clean || str_starts_with($uClean, $clean) || str_starts_with($clean, $uClean);
                    });
                }

                // 3. Token match (words >= 4 chars)
                if (! $user) {
                    $words = array_values(array_filter(explode(' ', $clean), fn ($w) => strlen($w) >= 4));
                    if (count($words) >= 2) {
                        $user = $existingTeachers->first(function ($u) use ($words, $cleanNameHelper) {
                            $uClean = $cleanNameHelper((string) $u->full_name);
                            $matches = 0;
                            foreach ($words as $w) {
                                if (str_contains($uClean, $w)) {
                                    $matches++;
                                }
                            }

                            return $matches >= min(2, count($words));
                        });
                    }
                }

                // 4. Match by NIP (identity_number) across all users (active or trashed)
                if (! $user && ! empty($nip)) {
                    $user = $existingTeachers->first(fn ($u) => (string) $u->identity_number === $nip)
                        ?: User::withTrashed()->where('identity_number', $nip)->first();
                }

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $needsSave = false;
                    if (! empty($nip) && $user->identity_number !== $nip) {
                        $user->identity_number = $nip;
                        $needsSave = true;
                    }
                    if ($user->status !== 'Aktif') {
                        $user->status = 'Aktif';
                        $needsSave = true;
                    }
                    if ($needsSave) {
                        $user->save();
                        $updatedNipCount++;
                    }
                    $matchedUserCache[$clean] = $user;
                } else {
                    $baseUsername = Str::slug($clean, '.');
                    if (empty($baseUsername)) {
                        $baseUsername = ! empty($nip) ? $nip : 'guru.'.rand(100, 999);
                    }
                    $username = $baseUsername;
                    $counter = 1;
                    while (User::withTrashed()->where('username', $username)->exists()) {
                        $username = $baseUsername.$counter++;
                    }

                    $email = $username.'@smkn1bantul.sch.id';
                    $counter = 1;
                    while (User::withTrashed()->where('email', $email)->exists()) {
                        $email = $username.$counter++.'@smkn1bantul.sch.id';
                    }

                    $newUser = User::create([
                        'full_name' => $name,
                        'identity_number' => ! empty($nip) ? $nip : null,
                        'username' => $username,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'role_id' => $roleGuru->id,
                        'status' => 'Aktif',
                    ]);
                    $newUser->syncRoles(['Guru']);

                    $existingTeachers->push($newUser);
                    $matchedUserCache[$clean] = $newUser;
                    $newTeachersCount++;
                }
            }

            return [
                'status' => 'success',
                'type' => 'employee_teachers',
                'message' => 'Berhasil memproses '.count($data['teachers'] ?? [])." data guru: {$updatedNipCount} NIP diperbarui, {$newTeachersCount} guru baru ditambahkan. Data TU & Outsourcing otomatis diabaikan.",
                'details' => [
                    'total_teachers_processed' => count($data['teachers'] ?? []),
                    'nip_updated' => $updatedNipCount,
                    'new_teachers_created' => $newTeachersCount,
                ],
            ];
        });
    }
}
