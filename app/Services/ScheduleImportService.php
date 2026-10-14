<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ScheduleImportService
{
    /**
     * Import schedules from parsed rows.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{
     *     total: int,
     *     imported: int,
     *     teachers_created: int,
     *     skipped: int,
     *     errors: array<int, string>,
     *     teachers_created_names: array<int, string>
     * }
     */
    public function import(array $rows, ?User $currentUser = null): array
    {
        $imported = 0;
        $teachersCreated = 0;
        $skipped = 0;
        $errors = [];
        $teachersCreatedNames = [];

        if (empty($rows)) {
            return [
                'total' => 0,
                'imported' => 0,
                'teachers_created' => 0,
                'skipped' => 0,
                'errors' => ['File tidak memiliki baris data yang valid.'],
                'teachers_created_names' => [],
            ];
        }

        $defaultAcademicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::first()
            ?? AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);

        $activeCycle = SystemConfiguration::current()->active_cycle ?? 'Putaran 1';

        $isOnlyTeacher = $currentUser
            && ($currentUser->hasRole('Guru') || $currentUser->role?->name === 'Guru')
            && ! $currentUser->hasRole('Super Admin')
            && ! $currentUser->hasRole('Waka Kurikulum');

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // header is row 1

            // 1. Extract values
            $teacherName = $this->getValue($row, ['guru', 'nama_guru', 'teacher', 'nama']);
            $nip = $this->getValue($row, ['nip', 'nip_guru', 'identity_number', 'nomor_identitas']);
            $subjectName = $this->getValue($row, ['mata_pelajaran', 'mapel', 'subject', 'pelajaran']);
            $className = $this->getValue($row, ['kelas', 'classroom', 'rombel']);
            $roomName = $this->getValue($row, ['ruangan', 'ruang', 'room', 'lab']) ?: 'Ruang Kelas';
            $dayOfWeek = $this->getValue($row, ['hari', 'day', 'day_of_week']) ?: 'Senin';
            $timeRange = $this->getValue($row, ['jam', 'waktu', 'time', 'jam_pelajaran']);
            $startTime = $this->getValue($row, ['jam_mulai', 'start_time', 'mulai']);
            $endTime = $this->getValue($row, ['jam_selesai', 'end_time', 'selesai']);
            $cycleType = $this->getValue($row, ['putaran', 'siklus', 'cycle', 'putaran_siklus']) ?: $activeCycle;
            $yearName = $this->getValue($row, ['tahun_ajaran', 'academic_year', 'tahun']);

            // Parse time range if in single column e.g. "07:30 - 09:30" or "07.30-09.30"
            if ((empty($startTime) || empty($endTime)) && ! empty($timeRange)) {
                $parts = preg_split('/\s*[-–—]\s*/', $timeRange);
                if (count($parts) >= 2) {
                    $startTime = $startTime ?: $parts[0];
                    $endTime = $endTime ?: $parts[1];
                }
            }

            // Normalizations
            $startTime = $this->normalizeTime($startTime);
            $endTime = $this->normalizeTime($endTime);
            $dayOfWeek = $this->normalizeDay($dayOfWeek);
            $cycleType = $this->normalizeCycle($cycleType);

            // Validation
            if (empty($subjectName) || empty($className) || empty($startTime) || empty($endTime)) {
                $errors[] = "Baris {$rowNum}: Kolom Mata Pelajaran, Kelas, Jam Mulai, dan Jam Selesai wajib diisi.";
                $skipped++;

                continue;
            }

            if ($startTime >= $endTime) {
                $errors[] = "Baris {$rowNum}: Jam Selesai ({$endTime}) harus lebih besar dari Jam Mulai ({$startTime}).";
                $skipped++;

                continue;
            }

            // 2. Resolve Teacher
            $teacher = null;
            if ($isOnlyTeacher) {
                $teacher = $currentUser;
            } elseif (! empty($teacherName) || ! empty($nip)) {
                // Find existing teacher
                $teacherQuery = User::query();
                if (! empty($nip)) {
                    $teacher = (clone $teacherQuery)->where('identity_number', $nip)->first();
                }
                if (! $teacher && ! empty($teacherName)) {
                    $teacher = (clone $teacherQuery)->where(function ($q) use ($teacherName) {
                        $q->where('full_name', $teacherName)
                            ->orWhere('username', Str::slug($teacherName, ''));
                    })->first();
                }

                // If teacher does not exist, AUTO-CREATE Teacher User
                if (! $teacher && ! empty($teacherName)) {
                    $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
                    $baseUsername = Str::slug($teacherName, '');
                    if (empty($baseUsername)) {
                        $baseUsername = 'guru'.rand(100, 999);
                    }
                    $username = $baseUsername;
                    $counter = 1;
                    while (User::where('username', $username)->exists()) {
                        $username = $baseUsername.$counter++;
                    }

                    $teacher = User::create([
                        'full_name' => $teacherName,
                        'username' => $username,
                        'identity_number' => $nip ?: null,
                        'email' => $username.'@scp.sch.id',
                        'password' => Hash::make('password'),
                        'role_id' => $guruRole->id,
                        'status' => 'Aktif',
                    ]);
                    $teacher->syncRoles(['Guru']);
                    $teachersCreated++;
                    $teachersCreatedNames[] = $teacherName;
                }
            } elseif ($currentUser) {
                $teacher = $currentUser;
            }

            if (! $teacher) {
                $errors[] = "Baris {$rowNum}: Guru tidak dapat diidentifikasi.";
                $skipped++;

                continue;
            }

            // 3. Resolve Master Data
            $subject = Subject::firstOrCreate(['name' => trim($subjectName)]);
            $classroom = Classroom::firstOrCreate(['name' => trim($className)]);
            $room = Room::firstOrCreate(['name' => trim($roomName)]);

            $academicYear = $defaultAcademicYear;
            if (! empty($yearName)) {
                $academicYear = AcademicYear::firstOrCreate(['name' => trim($yearName)]);
            }

            // 4. Check for identical duplicate schedule
            $existing = Schedule::where([
                'academic_year_id' => $academicYear->id,
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'room_id' => $room->id,
                'subject_id' => $subject->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'cycle_type' => $cycleType,
            ])->first();

            if ($existing) {
                $skipped++;

                continue;
            }

            // 5. Check conflict: same teacher, same room, or same class during this time
            $conflict = Schedule::where('day_of_week', $dayOfWeek)
                ->where('cycle_type', $cycleType)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->where(function ($q) use ($teacher, $classroom, $room) {
                    $q->where('teacher_id', $teacher->id)
                        ->orWhere('classroom_id', $classroom->id)
                        ->orWhere('room_id', $room->id);
                })
                ->first();

            if ($conflict) {
                $reason = $conflict->teacher_id === $teacher->id
                    ? "Guru {$teacher->full_name} sudah mengajar di kelas lain"
                    : ($conflict->classroom_id === $classroom->id
                        ? "Kelas {$classroom->name} sudah memiliki jadwal"
                        : "Ruangan {$room->name} sudah digunakan");

                $errors[] = "Baris {$rowNum}: Bentrok waktu pada {$dayOfWeek} {$startTime}-{$endTime} ({$reason}).";
                $skipped++;

                continue;
            }

            // 6. Create Schedule
            Schedule::create([
                'academic_year_id' => $academicYear->id,
                'teacher_id' => $teacher->id,
                'classroom_id' => $classroom->id,
                'room_id' => $room->id,
                'subject_id' => $subject->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'cycle_type' => $cycleType,
            ]);

            $imported++;
        }

        return [
            'total' => count($rows),
            'imported' => $imported,
            'teachers_created' => $teachersCreated,
            'skipped' => $skipped,
            'errors' => $errors,
            'teachers_created_names' => array_unique($teachersCreatedNames),
        ];
    }

    /**
     * Get first available value matching one of candidate keys.
     */
    protected function getValue(array $row, array $candidateKeys): string
    {
        foreach ($candidateKeys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    /**
     * Normalize time to HH:MM format.
     */
    protected function normalizeTime(string $time): string
    {
        $time = str_replace('.', ':', trim($time));
        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            $h = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $m = $matches[2];

            return "{$h}:{$m}";
        }

        return $time;
    }

    /**
     * Normalize day to standard Indonesian title-case.
     */
    protected function normalizeDay(string $day): string
    {
        $lower = strtolower(trim($day));

        return match ($lower) {
            'senin', 'monday', 'mon' => 'Senin',
            'selasa', 'tuesday', 'tue' => 'Selasa',
            'rabu', 'wednesday', 'wed' => 'Rabu',
            'kamis', 'thursday', 'thu' => 'Kamis',
            'jumat', "jum'at", 'friday', 'fri' => 'Jumat',
            'sabtu', 'saturday', 'sat' => 'Sabtu',
            'minggu', 'ahad', 'sunday', 'sun' => 'Minggu',
            default => ucfirst($lower),
        };
    }

    /**
     * Normalize cycle to "Putaran 1" or "Putaran 2".
     */
    protected function normalizeCycle(string $cycle): string
    {
        $lower = strtolower(trim($cycle));
        if (str_contains($lower, '2')) {
            return 'Putaran 2';
        }

        return 'Putaran 1';
    }
}
