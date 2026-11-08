<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\StudentPromotion;
use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $siswaGrade12;

    protected User $siswaGrade11;

    protected User $siswaGrade10;

    protected Classroom $class12;

    protected Classroom $class10;

    protected Classroom $class11;

    protected AcademicYear $academicYear;

    protected AcademicYear $nextAcademicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026 - Ganjil',
            'is_active' => true,
        ]);

        $this->nextAcademicYear = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => false,
        ]);

        $level10 = ClassLevel::create(['name' => 'X', 'level' => 10]);
        $level11 = ClassLevel::create(['name' => 'XI', 'level' => 11]);
        $level12 = ClassLevel::create(['name' => 'XII', 'level' => 12]);

        $major = Major::create(['name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);

        $this->class10 = Classroom::create([
            'name' => 'X RPL 1',
            'class_level_id' => $level10->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->class11 = Classroom::create([
            'name' => 'XI RPL 1',
            'class_level_id' => $level11->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->class12 = Classroom::create([
            'name' => 'XII RPL 1',
            'class_level_id' => $level12->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'Aktif',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->siswaGrade12 = User::factory()->create([
            'full_name' => 'Siswa Dua Belas',
            'username' => 'siswa12',
            'identity_number' => '1234567890',
            'role_id' => $siswaRole->id,
            'status' => 'Aktif',
        ]);
        $this->siswaGrade12->assignRole('Siswa');
        $this->siswaGrade12->classrooms()->attach($this->class12->id, [
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->siswaGrade11 = User::factory()->create([
            'full_name' => 'Siswa Sebelas',
            'username' => 'siswa11',
            'identity_number' => '1234567892',
            'role_id' => $siswaRole->id,
            'status' => 'Aktif',
        ]);
        $this->siswaGrade11->assignRole('Siswa');
        $this->siswaGrade11->classrooms()->attach($this->class11->id, [
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);

        $this->siswaGrade10 = User::factory()->create([
            'full_name' => 'Siswa Sepuluh',
            'username' => 'siswa10',
            'identity_number' => '1234567891',
            'role_id' => $siswaRole->id,
            'status' => 'Aktif',
        ]);
        $this->siswaGrade10->assignRole('Siswa');
        $this->siswaGrade10->classrooms()->attach($this->class10->id, [
            'academic_year_id' => $this->academicYear->id,
            'status' => 'Aktif',
        ]);
    }

    public function test_admin_can_access_student_promotion_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(StudentPromotion::getUrl());
        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_student_promotion_page(): void
    {
        $this->actingAs($this->siswaGrade12);

        $response = $this->get(StudentPromotion::getUrl());
        $response->assertForbidden();
    }

    public function test_all_classes_and_grade_12_students_can_be_promoted_and_graduated_directly_without_inputs(): void
    {
        $this->actingAs($this->admin);

        // Call action directly without any input parameters
        Livewire::test(StudentPromotion::class)
            ->callAction('promote_and_graduate')
            ->assertHasNoErrors();

        // 1. Grade 12 student is graduated to Alumni
        $this->siswaGrade12->refresh();
        $this->assertEquals('Lulus', $this->siswaGrade12->status);
        $this->assertEquals(date('Y'), $this->siswaGrade12->graduation_year);
        $this->assertEquals(now()->toDateString(), $this->siswaGrade12->graduated_at?->format('Y-m-d'));
        $this->assertTrue($this->siswaGrade12->isAlumni());

        $pivot12 = $this->siswaGrade12->classrooms()->wherePivot('status', 'Lulus')->exists();
        $this->assertTrue($pivot12);

        // 2. Grade 11 student is automatically promoted to Grade 12
        $this->siswaGrade11->refresh();
        $promoted11 = $this->siswaGrade11->classrooms()
            ->where('classrooms.id', $this->class12->id)
            ->wherePivot('status', 'Aktif')
            ->exists();
        $this->assertTrue($promoted11);
        $this->assertEquals('Aktif', $this->siswaGrade11->status);

        // 3. Grade 10 student is automatically promoted to Grade 11
        $this->siswaGrade10->refresh();
        $promoted10 = $this->siswaGrade10->classrooms()
            ->where('classrooms.id', $this->class11->id)
            ->wherePivot('status', 'Aktif')
            ->exists();
        $this->assertTrue($promoted10);
        $this->assertEquals('Aktif', $this->siswaGrade10->status);
    }

    public function test_single_student_can_be_graduated_via_table_row_action(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StudentPromotion::class)
            ->callTableAction('graduate_single', $this->siswaGrade12, [
                'graduation_year' => '2026',
                'graduated_at' => '2026-05-15',
                'alumni_notes' => '[Kuliah] S1 Informatika UGM',
            ])
            ->assertHasNoErrors();

        $this->siswaGrade12->refresh();

        $this->assertEquals('Lulus', $this->siswaGrade12->status);
        $this->assertEquals('2026', $this->siswaGrade12->graduation_year);
        $this->assertTrue($this->siswaGrade12->isAlumni());
    }

    public function test_alumni_detail_can_be_updated_and_reverted(): void
    {
        $this->actingAs($this->admin);

        // Graduate first
        $this->siswaGrade12->update([
            'status' => 'Lulus',
            'graduation_year' => '2026',
            'graduated_at' => '2026-05-15',
            'alumni_notes' => 'Awal',
        ]);

        // Edit alumni detail
        Livewire::test(StudentPromotion::class)
            ->callTableAction('edit_alumni', $this->siswaGrade12, [
                'graduation_year' => '2026',
                'graduated_at' => '2026-05-15',
                'alumni_notes' => 'Bekerja di Google',
            ])
            ->assertHasNoErrors();

        $this->siswaGrade12->refresh();
        $this->assertEquals('Bekerja di Google', $this->siswaGrade12->alumni_notes);

        // Revert graduation back to active
        Livewire::test(StudentPromotion::class)
            ->callTableAction('revert_graduation', $this->siswaGrade12)
            ->assertHasNoErrors();

        $this->siswaGrade12->refresh();
        $this->assertEquals('Aktif', $this->siswaGrade12->status);
        $this->assertNull($this->siswaGrade12->graduation_year);
        $this->assertFalse($this->siswaGrade12->isAlumni());
    }

    public function test_all_alumni_can_be_reverted_via_header_action(): void
    {
        $this->actingAs($this->admin);

        // Graduate first
        $this->siswaGrade12->update([
            'status' => 'Lulus',
            'graduation_year' => '2026',
            'graduated_at' => '2026-05-15',
            'alumni_notes' => 'Angkatan 2026',
        ]);
        $this->siswaGrade12->classrooms()->updateExistingPivot($this->class12->id, ['status' => 'Lulus']);

        $this->assertTrue($this->siswaGrade12->fresh()->isAlumni());

        // Call header action 'revert_all_alumni'
        Livewire::test(StudentPromotion::class)
            ->callAction('revert_all_alumni')
            ->assertHasNoErrors();

        $this->siswaGrade12->refresh();
        $this->assertEquals('Aktif', $this->siswaGrade12->status);
        $this->assertNull($this->siswaGrade12->graduation_year);
        $this->assertNull($this->siswaGrade12->graduated_at);
        $this->assertNull($this->siswaGrade12->alumni_notes);
        $this->assertFalse($this->siswaGrade12->isAlumni());

        $pivot = $this->siswaGrade12->classrooms()->wherePivot('status', 'Aktif')->exists();
        $this->assertTrue($pivot);
    }

    public function test_all_promotions_and_graduations_can_be_reverted(): void
    {
        $this->actingAs($this->admin);

        // 1. Run promotion
        Livewire::test(StudentPromotion::class)
            ->callAction('promote_and_graduate')
            ->assertHasNoErrors();

        $this->siswaGrade10->refresh();
        $this->siswaGrade11->refresh();
        $this->siswaGrade12->refresh();

        $this->assertEquals('Aktif', $this->siswaGrade10->status);
        $this->assertEquals($this->class11->id, $this->siswaGrade10->classroom_id);
        $this->assertEquals('Aktif', $this->siswaGrade11->status);
        $this->assertEquals($this->class12->id, $this->siswaGrade11->classroom_id);
        $this->assertEquals('Lulus', $this->siswaGrade12->status);

        // 2. Call revert_all_promotions
        Livewire::test(StudentPromotion::class)
            ->callAction('revert_all_promotions')
            ->assertHasNoErrors();

        $this->siswaGrade10->refresh();
        $this->siswaGrade11->refresh();
        $this->siswaGrade12->refresh();

        // Check Grade 10 student is back to Grade 10
        $this->assertEquals('Aktif', $this->siswaGrade10->status);
        $this->assertEquals($this->class10->id, $this->siswaGrade10->classroom_id);
        $this->assertCount(1, $this->siswaGrade10->activeClassrooms);

        // Check Grade 11 student is back to Grade 11
        $this->assertEquals('Aktif', $this->siswaGrade11->status);
        $this->assertEquals($this->class11->id, $this->siswaGrade11->classroom_id);
        $this->assertCount(1, $this->siswaGrade11->activeClassrooms);

        // Check Grade 12 student is back to Aktif Grade 12
        $this->assertEquals('Aktif', $this->siswaGrade12->status);
        $this->assertFalse($this->siswaGrade12->isAlumni());
    }

    public function test_selected_alumni_can_be_reverted_via_bulk_action(): void
    {
        $this->actingAs($this->admin);

        // Graduate first
        $this->siswaGrade12->update([
            'status' => 'Lulus',
            'graduation_year' => '2026',
            'graduated_at' => '2026-05-15',
            'alumni_notes' => 'Angkatan 2026',
        ]);
        $this->siswaGrade12->classrooms()->updateExistingPivot($this->class12->id, ['status' => 'Lulus']);

        // Call bulk action 'bulk_revert'
        Livewire::test(StudentPromotion::class)
            ->callTableBulkAction('bulk_revert', [$this->siswaGrade12])
            ->assertHasNoErrors();

        $this->siswaGrade12->refresh();
        $this->assertEquals('Aktif', $this->siswaGrade12->status);
        $this->assertNull($this->siswaGrade12->graduation_year);
        $this->assertFalse($this->siswaGrade12->isAlumni());
    }

    public function test_table_only_displays_grade_12_and_alumni_students(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StudentPromotion::class)
            ->assertCanSeeTableRecords([$this->siswaGrade12])
            ->assertCanNotSeeTableRecords([$this->siswaGrade10, $this->siswaGrade11]);
    }

    public function test_promotion_is_idempotent_and_does_not_graduate_newly_promoted_students_if_run_twice(): void
    {
        $this->actingAs($this->admin);

        // Run 1
        Livewire::test(StudentPromotion::class)
            ->callAction('promote_and_graduate')
            ->assertHasNoErrors();

        $this->siswaGrade12->refresh();
        $this->siswaGrade11->refresh();
        $this->siswaGrade10->refresh();

        $this->assertEquals('Lulus', $this->siswaGrade12->status);
        $this->assertEquals('Aktif', $this->siswaGrade11->status);
        $this->assertEquals($this->class12->id, $this->siswaGrade11->classroom_id);
        $this->assertEquals('Aktif', $this->siswaGrade10->status);
        $this->assertEquals($this->class11->id, $this->siswaGrade10->classroom_id);

        // Run 2 immediately
        Livewire::test(StudentPromotion::class)
            ->callAction('promote_and_graduate')
            ->assertHasNoErrors();

        $this->siswaGrade11->refresh();
        $this->siswaGrade10->refresh();

        // The newly promoted Grade 11 student must STILL be 'Aktif' in Grade 12 and NOT graduated!
        $this->assertEquals('Aktif', $this->siswaGrade11->status);
        $this->assertEquals($this->class12->id, $this->siswaGrade11->classroom_id);

        // The newly promoted Grade 10 student must STILL be 'Aktif' in Grade 11 and NOT promoted again!
        $this->assertEquals('Aktif', $this->siswaGrade10->status);
        $this->assertEquals($this->class11->id, $this->siswaGrade10->classroom_id);
    }

    public function test_graduated_alumni_student_cannot_access_filament_panel(): void
    {
        $this->siswaGrade12->update(['status' => 'Lulus']);

        $panel = Filament::getCurrentOrDefaultPanel();
        $this->assertFalse($this->siswaGrade12->canAccessPanel($panel));
    }

    public function test_inactive_student_is_denied_login_with_specific_message(): void
    {
        $this->siswaGrade12->update([
            'status' => 'Lulus',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'login' => $this->siswaGrade12->username,
                'password' => 'password123',
            ])
            ->call('authenticate')
            ->assertHasErrors(['data.login' => 'Akun Anda berstatus Alumni (Sudah Lulus). Hak akses ke portal telah dinonaktifkan.']);
    }
}
