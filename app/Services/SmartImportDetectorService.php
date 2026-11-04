<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class SmartImportDetectorService
{
    /**
     * Resolve actual file path from Livewire temporary file or storage path.
     */
    public function resolveUploadedFilePath(mixed $state, string $disk = 'local'): ?string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if (is_object($state) && method_exists($state, 'getRealPath')) {
            $path = $state->getRealPath();
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        if (is_string($state) && ! empty(trim($state))) {
            if (file_exists($state)) {
                return $state;
            }
            $diskPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($state);
            if (file_exists($diskPath)) {
                return $diskPath;
            }
        }

        return null;
    }

    /**
     * Locate available python binary.
     */
    public function findPythonBinary(): ?string
    {
        $custom = env('PYTHON_BINARY');
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
     * Detect file type and parse data into a normalized associative array.
     */
    public function detectAndParse(string $filePath): array
    {
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
     * Native PHP parser fallback if python is unavailable.
     */
    protected function nativeParseFallback(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->nativeParseExcel($filePath);
        }

        throw new Exception("Deteksi native PHP untuk file .{$ext} memerlukan Python interpreter dengan pustaka pdfplumber / openpyxl.");
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
                    if (preg_match('/(\d{4}\s*[-/]\s*\d{4})/', $c1, $m)) {
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
     * Normalize class name e.g. "X RPL1" -> "X RPL 1".
     */
    public function normalizeClassName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if (preg_match('/^(XII|XI|X|12|11|10)\s+([A-Za-z]+)(\d+)$/i', $name, $m)) {
            return strtoupper($m[1]).' '.strtoupper($m[2]).' '.$m[3];
        }
        if (preg_match('/^(XII|XI|X|12|11|10)\s+([A-Za-z]+)\s+(\d+)$/i', $name, $m)) {
            return strtoupper($m[1]).' '.strtoupper($m[2]).' '.$m[3];
        }

        return strtoupper($name);
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
        $type = $data['type'] ?? 'unknown';

        if ($type === 'student_roster') {
            return $this->importStudentRoster($data, $academicYearIdOverride);
        }

        if ($type === 'timetable_fet') {
            return $this->importFetTimetable($data, $cycleTypeOverride, $academicYearIdOverride);
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

            // 4. Preload existing users and user_class pivots
            $existingUsers = User::query()
                ->select(['id', 'identity_number', 'username', 'full_name', 'role_id'])
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

                    $existingUsers->put($nis, (object) ['id' => $userId]);
                    $newUsersCount++;
                } else {
                    $userId = $user->id;
                    DB::table('users')->where('id', $userId)->update([
                        'full_name' => $name,
                        'role_id' => $roleSiswa->id,
                        'status' => 'Aktif',
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
                'message' => "Berhasil memproses ".count($data['students'])." data siswa ke dalam {$existingClassrooms->count()} rombel kelas.",
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

            $cycle = $cycleTypeOverride ?: ($data['cycle_type'] ?? 'Putaran 1');
            $roleGuru = Role::firstOrCreate(['name' => 'Guru'], ['guard_name' => 'web']);
            $hashedPassword = Hash::make('password');
            $now = now();

            // 1. Classrooms
            $classrooms = [];
            foreach ($data['classes'] as $clsName) {
                $normalized = $this->normalizeClassName($clsName);
                if (! isset($classrooms[$normalized])) {
                    $c = Classroom::firstOrCreate(['name' => $normalized]);
                    $c->syncRelationsFromName();
                    $c->save();
                    $classrooms[$normalized] = $c;
                }
            }

            // 2. Subjects
            $subjects = [];
            foreach ($data['subjects'] as $subjName) {
                $cleanSubj = trim($subjName);
                $key = strtoupper($cleanSubj);
                if (! isset($subjects[$key])) {
                    $s = Subject::firstOrCreate(['name' => $cleanSubj]);
                    $subjects[$key] = $s;
                }
            }

            // 3. Rooms
            $rooms = [];
            $defaultRoom = Room::firstOrCreate(['name' => 'Ruang Teori']);
            $rooms['RUANG TEORI'] = $defaultRoom;

            foreach ($data['rooms'] as $roomName) {
                $cleanRoom = trim($roomName);
                $key = strtoupper($cleanRoom);
                if (! isset($rooms[$key])) {
                    $r = Room::firstOrCreate(['name' => $cleanRoom]);
                    $rooms[$key] = $r;
                }
            }

            // 4. Teachers
            $teachers = [];
            $newTeachersCount = 0;
            $existingTeachers = User::where('role_id', $roleGuru->id)->get();
            $teacherByCode = $existingTeachers->keyBy(fn ($u) => (string) $u->identity_number);
            $teacherByName = $existingTeachers->keyBy(fn ($u) => strtoupper($u->full_name));

            foreach ($data['teachers'] as $t) {
                $tName = trim($t['name']);
                $tCode = $t['code'] ? trim((string) $t['code']) : null;
                $key = strtoupper($tName);

                $user = ($tCode && $teacherByCode->has($tCode))
                    ? $teacherByCode->get($tCode)
                    : $teacherByName->get($key);

                if (! $user) {
                    $uname = $tCode ? "guru_{$tCode}" : strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tName));
                    $uname = substr($uname, 0, 30);
                    if (User::where('username', $uname)->exists()) {
                        $uname .= '_'.rand(10, 99);
                    }

                    $userId = DB::table('users')->insertGetId([
                        'role_id' => $roleGuru->id,
                        'full_name' => $tName,
                        'username' => $uname,
                        'identity_number' => $tCode ?: $uname,
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
                    $newTeachersCount++;
                }

                $teachers[$key] = $user;
                if ($tCode) {
                    $teachers[$tCode] = $user;
                }
            }

            // 5. Schedules Upsert
            $existingSchedules = Schedule::where('academic_year_id', $academicYear->id)
                ->where('cycle_type', $cycle)
                ->get()
                ->keyBy(fn ($s) => "{$s->classroom_id}_{$s->day_of_week}_{$s->start_time}");

            $schedulesCreated = 0;
            $schedulesUpdated = 0;
            $toInsert = [];

            foreach ($data['schedules'] as $s) {
                $normClass = $this->normalizeClassName($s['classroom']);
                $classroom = $classrooms[$normClass] ?? Classroom::where('name', $normClass)->first();
                $subject = $subjects[strtoupper(trim($s['subject']))] ?? null;

                $tKey = strtoupper(trim($s['teacher_name']));
                $teacher = $teachers[$tKey] ?? ($s['teacher_code'] ? ($teachers[$s['teacher_code']] ?? null) : null);

                $room = ! empty($s['room']) ? ($rooms[strtoupper(trim($s['room']))] ?? $defaultRoom) : $defaultRoom;

                if (! $classroom || ! $subject || ! $teacher) {
                    continue;
                }

                $schedKey = "{$classroom->id}_{$s['day_of_week']}_{$s['start_time']}";
                $existing = $existingSchedules->get($schedKey);

                if ($existing) {
                    DB::table('schedules')->where('id', $existing->id)->update([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'room_id' => $room?->id,
                        'end_time' => $s['end_time'],
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
                        'day_of_week' => $s['day_of_week'],
                        'start_time' => $s['start_time'],
                        'end_time' => $s['end_time'],
                        'cycle_type' => $cycle,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $schedulesCreated++;
                }
            }

            if (! empty($toInsert)) {
                foreach (array_chunk($toInsert, 300) as $chunk) {
                    DB::table('schedules')->insert($chunk);
                }
            }

            return [
                'status' => 'success',
                'type' => 'timetable_fet',
                'message' => "Berhasil memproses ".count($data['schedules'])." slot jadwal KBM untuk {$cycle}.",
                'stats' => [
                    'academic_year' => $academicYear->name,
                    'cycle_type' => $cycle,
                    'total_schedules_in_file' => count($data['schedules']),
                    'schedules_created' => $schedulesCreated,
                    'schedules_updated' => $schedulesUpdated,
                    'total_classes' => count($classrooms),
                    'total_teachers' => count($teachers),
                    'new_teachers_created' => $newTeachersCount,
                    'total_subjects' => count($subjects),
                    'total_rooms' => count($rooms),
                ],
            ];
        });
    }
}
