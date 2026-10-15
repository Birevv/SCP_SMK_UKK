<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
    }

    public function test_get_me_returns_read_only_profile_data_for_different_roles(): void
    {
        $classroom = Classroom::create(['name' => 'XII RPL 1']);
        $subject = Subject::create(['name' => 'Pemrograman Web']);
        $room = Room::create(['name' => 'Lab RPL']);
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);

        // 1. Siswa
        $siswaRole = Role::where('name', 'Siswa')->first();
        $siswa = User::factory()->create([
            'role_id' => $siswaRole->id,
            'identity_number' => '0051234567',
        ]);
        $siswa->assignRole('Siswa');
        $siswa->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $this->actingAs($siswa, 'sanctum');
        $resSiswa = $this->getJson('/api/me');
        $resSiswa->assertOk()
            ->assertJsonPath('data.identity_number', '0051234567')
            ->assertJsonPath('data.role_badge', 'Siswa')
            ->assertJsonPath('data.assignment', 'XII RPL 1');

        // 2. Wali Kelas
        $waliRole = Role::where('name', 'Wali Kelas')->first();
        $wali = User::factory()->create([
            'role_id' => $waliRole->id,
            'identity_number' => '198501012010011001',
        ]);
        $wali->assignRole('Wali Kelas');
        $wali->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $this->actingAs($wali, 'sanctum');
        $resWali = $this->getJson('/api/me');
        $resWali->assertOk()
            ->assertJsonPath('data.role_badge', 'Wali Kelas')
            ->assertJsonPath('data.assignment', 'Wali Kelas XII RPL 1');

        // 3. Guru BK
        $bkRole = Role::where('name', 'Guru BK')->first();
        $bk = User::factory()->create([
            'role_id' => $bkRole->id,
            'identity_number' => '198602022010012002',
        ]);
        $bk->assignRole('Guru BK');

        $this->actingAs($bk, 'sanctum');
        $resBk = $this->getJson('/api/me');
        $resBk->assertOk()
            ->assertJsonPath('data.role_badge', 'Guru BK')
            ->assertJsonPath('data.assignment', 'Guru BK');

        // 4. Guru Mata Pelajaran
        $guruRole = Role::where('name', 'Guru')->first();
        $guru = User::factory()->create([
            'role_id' => $guruRole->id,
            'identity_number' => '199003032015011003',
        ]);
        $guru->assignRole('Guru');

        Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $guru->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($guru, 'sanctum');
        $resGuru = $this->getJson('/api/me');
        $resGuru->assertOk()
            ->assertJsonPath('data.role_badge', 'Guru')
            ->assertJsonPath('data.assignment', 'Guru Pemrograman Web');

        // 5. Guru yang mengampu 2 (Wali Kelas DAN Guru Mapel)
        $dualUser = User::factory()->create([
            'role_id' => $waliRole->id,
            'identity_number' => '198001012005011005',
        ]);
        $dualUser->assignRole('Wali Kelas');
        $dualUser->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $dualUser->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Selasa',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($dualUser, 'sanctum');
        $resDual = $this->getJson('/api/me');
        $resDual->assertOk()
            ->assertJsonPath('data.assignment', "'Wali Kelas XII RPL 1' dan 'Guru Pemrograman Web'");
    }

    public function test_update_profile_updates_allowed_fields_only(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'full_name' => 'Nama Lama',
            'email' => 'lama@scp.sch.id',
            'phone' => '081234567890',
            'identity_number' => '199001012010011001',
            'username' => 'userlama',
        ]);

        $this->actingAs($user, 'sanctum');

        $photo = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->putJson('/api/profile', [
            'full_name' => 'Nama Baru',
            'email' => 'baru@scp.sch.id',
            'phone' => '089876543210',
            'profile_photo' => $photo,
            'identity_number' => 'HACKED_NISN',
            'username' => 'hacked_username',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.full_name', 'Nama Baru')
            ->assertJsonPath('data.email', 'baru@scp.sch.id')
            ->assertJsonPath('data.phone', '089876543210');

        $user->refresh();

        // 4 fields updated
        $this->assertEquals('Nama Baru', $user->full_name);
        $this->assertEquals('baru@scp.sch.id', $user->email);
        $this->assertEquals('089876543210', $user->phone);
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);

        // Read-only / unallowed fields untouched
        $this->assertEquals('199001012010011001', $user->identity_number);
        $this->assertEquals('userlama', $user->username);
    }

    public function test_change_password_validates_and_updates_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $this->actingAs($user, 'sanctum');

        // Test wrong current password
        $resWrong = $this->putJson('/api/profile/password', [
            'current_password' => 'WrongPassword',
            'new_password' => 'NewPassword123',
            'new_password_confirmation' => 'NewPassword123',
        ]);
        $resWrong->assertStatus(422);

        // Test unconfirmed new password
        $resUnconfirmed = $this->putJson('/api/profile/password', [
            'current_password' => 'OldPassword123',
            'new_password' => 'NewPassword123',
            'new_password_confirmation' => 'Mismatch123',
        ]);
        $resUnconfirmed->assertStatus(422);

        // Test success
        $resSuccess = $this->putJson('/api/profile/password', [
            'current_password' => 'OldPassword123',
            'new_password' => 'NewPassword123',
            'new_password_confirmation' => 'NewPassword123',
        ]);
        $resSuccess->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
        $this->assertFalse(Hash::check('OldPassword123', $user->password));
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertOk();
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_user_implements_has_avatar_and_returns_correct_filament_avatar_url(): void
    {
        $userWithoutPhoto = User::factory()->create(['profile_photo' => null]);
        $this->assertInstanceOf(HasAvatar::class, $userWithoutPhoto);
        $this->assertNull($userWithoutPhoto->getFilamentAvatarUrl());

        $userWithRelativePhoto = User::factory()->create(['profile_photo' => 'profile_photos/avatar.jpg']);
        $this->assertStringContainsString('storage/profile_photos/avatar.jpg', $userWithRelativePhoto->getFilamentAvatarUrl());

        $userWithUrl = User::factory()->create(['profile_photo' => 'https://example.com/avatar.jpg']);
        $this->assertEquals('https://example.com/avatar.jpg', $userWithUrl->getFilamentAvatarUrl());
    }
}
