<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserImportService
{
    /**
     * Import users from parsed rows.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{
     *     total: int,
     *     imported: int,
     *     updated: int,
     *     skipped: int,
     *     errors: array<int, string>
     * }
     */
    public function import(array $rows, ?User $currentUser = null): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        if (empty($rows)) {
            return [
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ['File tidak memiliki baris data pengguna yang valid.'],
            ];
        }

        $activeYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
        $isSuperAdmin = $currentUser?->hasRole('Super Admin') || $currentUser?->role?->name === 'Super Admin';

        // Preload roles
        $roles = Role::pluck('id', 'name')->toArray();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $fullName = $this->getValue($row, ['nama_lengkap', 'nama', 'full_name', 'name', 'nama_wali_kelas', 'wali_kelas', 'nama_guru_bk', 'guru_bk', 'wali_bk', 'nama_wali_bk']);
            $username = $this->getValue($row, ['username', 'user']);

            $roleFromCol = null;
            if (! empty($this->getValue($row, ['nama_wali_kelas', 'wali_kelas']))) {
                $roleFromCol = 'Wali Kelas';
            } elseif (! empty($this->getValue($row, ['nama_guru_bk', 'guru_bk', 'wali_bk', 'nama_wali_bk']))) {
                $roleFromCol = 'Guru BK';
            }

            $roleName = $this->getValue($row, ['role', 'role_akses', 'jabatan', 'peran']) ?: ($roleFromCol ?: 'Siswa');
            $identityNumber = $this->getValue($row, ['nisn_nip', 'identity_number', 'nisn', 'nip', 'nomor_identitas']);
            $email = $this->getValue($row, ['email', 'surel']);
            $phone = $this->getValue($row, ['telepon', 'phone', 'no_hp', 'no_telepon']);
            $className = $this->getValue($row, ['kelas', 'classroom', 'rombel']);
            $status = $this->getValue($row, ['status']) ?: 'Aktif';
            $password = $this->getValue($row, ['password', 'kata_sandi']) ?: 'password';

            if (empty($fullName)) {
                $errors[] = "Baris {$rowNum}: Nama Lengkap wajib diisi.";
                $skipped++;

                continue;
            }

            // Normalize Role
            $normalizedRoleName = $this->normalizeRoleName($roleName);

            // Security guard: Non-Super Admin cannot import or assign Super Admin role
            if ($normalizedRoleName === 'Super Admin' && ! $isSuperAdmin) {
                $errors[] = "Baris {$rowNum}: Hanya Super Admin yang dapat menambahkan role Super Admin.";
                $skipped++;

                continue;
            }

            $roleId = $roles[$normalizedRoleName] ?? null;
            if (! $roleId) {
                $roleInstance = Role::firstOrCreate(['name' => $normalizedRoleName, 'guard_name' => 'web']);
                $roleId = $roleInstance->id;
                $roles[$normalizedRoleName] = $roleId;
            }

            // Generate username if empty
            if (empty($username)) {
                $base = ! empty($identityNumber)
                    ? $identityNumber
                    : Str::slug($fullName, '');

                if (empty($base)) {
                    $base = 'user'.rand(100, 999);
                }

                $username = $base;
                $counter = 1;
                while (User::withTrashed()->where('username', $username)->exists()) {
                    $username = $base.$counter++;
                }
            }

            // Generate email if empty
            if (empty($email)) {
                $email = Str::slug($username).'@scp.sch.id';
                $counter = 1;
                while (User::withTrashed()->where('email', $email)->exists()) {
                    $email = Str::slug($username).rand(10, 99).$counter++.'@scp.sch.id';
                }
            }

            // Check if user already exists by identity_number, username, or teacher name (including trashed)
            $user = null;
            if (! empty($identityNumber)) {
                $user = User::withTrashed()->where('identity_number', $identityNumber)->first();
            }
            if (! $user && ! empty($username)) {
                $user = User::withTrashed()->where('username', $username)->first();
            }
            if (! $user && in_array($normalizedRoleName, ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum'])) {
                $cleanNorm = strtoupper(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', (string) (preg_split('/,/', (string) preg_replace('/^(dra\s*\.?|drs\s*\.?|dr\s*\.?|ir\s*\.?|h\s*\.?|hj\s*\.?)\s*/i', '', $fullName))[0] ?? ''))));
                if (strlen($cleanNorm) >= 6) {
                    $teacherCandidates = User::withTrashed()
                        ->where(function ($q) {
                            $q->whereHas('role', fn ($r) => $r->whereIn('name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum']))
                                ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['Guru', 'Wali Kelas', 'Guru BK', 'Waka Kurikulum']));
                        })->get();
                    foreach ($teacherCandidates as $cand) {
                        $candNorm = strtoupper(trim((string) preg_replace('/[^a-zA-Z0-9]/', '', (string) (preg_split('/,/', (string) preg_replace('/^(dra\s*\.?|drs\s*\.?|dr\s*\.?|ir\s*\.?|h\s*\.?|hj\s*\.?)\s*/i', '', $cand->full_name))[0] ?? ''))));
                        if ($candNorm === $cleanNorm || (strlen($candNorm) >= 6 && levenshtein($cleanNorm, $candNorm) <= 2 && similar_text($cleanNorm, $candNorm, $pct) && $pct >= 85)) {
                            $user = $cand;
                            break;
                        }
                    }
                }
            }

            $rolesToSync = in_array($normalizedRoleName, ['Wali Kelas', 'Guru BK'])
                ? ['Guru', $normalizedRoleName]
                : [$normalizedRoleName];

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                // Prevent editing Super Admin by non-Super Admin
                if (($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') && ! $isSuperAdmin) {
                    $errors[] = "Baris {$rowNum}: Akun Super Admin tidak dapat diperbarui melalui import.";
                    $skipped++;

                    continue;
                }

                $user->update([
                    'full_name' => $fullName,
                    'role_id' => $roleId,
                    'email' => $email ?: $user->email,
                    'phone' => $phone ?: $user->phone,
                    'status' => in_array($status, ['Aktif', 'Lulus', 'Keluar', 'DO']) ? $status : 'Aktif',
                ]);
                $user->syncRoles($rolesToSync);
                $updated++;
            } else {
                $user = User::create([
                    'full_name' => $fullName,
                    'username' => $username,
                    'identity_number' => $identityNumber ?: null,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'password' => Hash::make($password),
                    'role_id' => $roleId,
                    'status' => in_array($status, ['Aktif', 'Lulus', 'Keluar', 'DO']) ? $status : 'Aktif',
                ]);
                $user->syncRoles($rolesToSync);
                $imported++;
            }

            // Attach classroom if provided
            if (! empty($className) && $user) {
                $classroom = Classroom::firstOrCreate(['name' => trim($className)]);
                if (! $user->classrooms()->where('classrooms.id', $classroom->id)->exists()) {
                    $user->classrooms()->attach($classroom->id, [
                        'academic_year_id' => $activeYear?->id,
                        'status' => 'Aktif',
                    ]);
                }
            }
        }

        return [
            'total' => count($rows),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    protected function getValue(array $row, array $candidateKeys): string
    {
        foreach ($candidateKeys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    protected function normalizeRoleName(string $role): string
    {
        $lower = strtolower(trim($role));

        return match ($lower) {
            'guru', 'teacher' => 'Guru',
            'wali_kelas', 'wali kelas', 'walikelas', 'homeroom' => 'Wali Kelas',
            'guru_bk', 'guru bk', 'gurubk', 'bk', 'counselor', 'wali_bk', 'wali bk', 'walibk' => 'Guru BK',
            'siswa', 'student', 'murid' => 'Siswa',
            'waka_kurikulum', 'waka kurikulum', 'wakakurikulum', 'waka' => 'Waka Kurikulum',
            'super_admin', 'super admin', 'superadmin', 'admin' => 'Super Admin',
            default => ucfirst($lower),
        };
    }
}
