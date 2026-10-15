<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Resources\Classrooms\ClassroomResource;
use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\SystemConfigurations\SystemConfigurationResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\SystemConfigWidget;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_manage_roles(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $waka = User::factory()->create();
        $waka->assignRole('Waka Kurikulum');

        // Super Admin must have access to manage Spatie Roles
        $this->actingAs($superAdmin);
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Role::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Role::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $superAdminRole));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $superAdminRole));
        $this->assertTrue(RoleResource::canViewAny());

        // Test with Spatie model instance directly as passed by Filament Shield
        $spatieRole = \Spatie\Permission\Models\Role::first();
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $spatieRole));

        // Waka Kurikulum must NOT have access to manage Spatie Roles or Filament Shield
        $this->actingAs($waka);
        $this->assertFalse(Gate::forUser($waka)->allows('viewAny', Role::class));
        $this->assertFalse(Gate::forUser($waka)->allows('create', Role::class));
        $this->assertFalse(Gate::forUser($waka)->allows('update', $superAdminRole));
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_waka_only_has_access_to_system_config_widget_and_other_features_moved_to_super_admin(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $superAdmin->assignRole('Super Admin');

        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        // 1. Waka BISA mengakses widget Lock Schedule & Cycle Toggle
        $this->actingAs($waka);
        $this->assertTrue(SystemConfigWidget::canView());

        // 2. Waka BISA mengakses dan menginput user
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(Gate::forUser($waka)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($waka)->allows('create', User::class));

        // Master data lainnya dipindahkan ke Super Admin
        $this->assertFalse(AcademicYearResource::canViewAny());
        $this->assertFalse(SubjectResource::canViewAny());
        $this->assertFalse(RoomResource::canViewAny());
        $this->assertFalse(ClassroomResource::canViewAny());
        $this->assertFalse(SystemConfigurationResource::canViewAny());

        // Waka BISA melihat dan menginput jadwal mengajar
        $this->assertTrue(Gate::forUser($waka)->allows('viewAny', Schedule::class));
        $this->assertTrue(Gate::forUser($waka)->allows('create', Schedule::class));

        // 3. Super Admin BISA mengakses seluruh master data
        $this->actingAs($superAdmin);
        $this->assertTrue(SystemConfigWidget::canView());
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(AcademicYearResource::canViewAny());
        $this->assertTrue(SubjectResource::canViewAny());
        $this->assertTrue(RoomResource::canViewAny());
        $this->assertTrue(ClassroomResource::canViewAny());
        $this->assertTrue(SystemConfigurationResource::canViewAny());
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Schedule::class));
    }

    public function test_waka_can_input_user_and_roles_sync_automatically(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($waka);

        // Waka input user baru (contoh Guru)
        $newTeacher = User::create([
            'full_name' => 'Guru Matematika Baru',
            'username' => 'gurubaru',
            'email' => 'gurubaru@scp.sch.id',
            'identity_number' => '199001012020011001',
            'password' => bcrypt('password'),
            'role_id' => $guruRole->id,
            'status' => 'Aktif',
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'gurubaru',
            'role_id' => $guruRole->id,
        ]);

        // Spatie role otomatis tersinkronisasi via booted model
        $this->assertTrue($newTeacher->hasRole('Guru'));

        // Waka diizinkan mengedit guru baru
        $this->assertTrue(Gate::forUser($waka)->allows('update', $newTeacher));
        $this->assertTrue(UserResource::canEdit($newTeacher));

        // Waka TIDAK diizinkan mengedit atau menghapus Super Admin
        $this->assertFalse(Gate::forUser($waka)->allows('update', $superAdmin));
        $this->assertFalse(Gate::forUser($waka)->allows('delete', $superAdmin));
        $this->assertFalse(UserResource::canEdit($superAdmin));
        $this->assertFalse(UserResource::canDelete($superAdmin));
    }

    public function test_walikelas_and_bk_cannot_view_or_input_journal(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $waliRole = Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        $bkRole = Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);

        $wali = User::factory()->create(['role_id' => $waliRole->id]);
        $wali->assignRole('Wali Kelas');

        $bk = User::factory()->create(['role_id' => $bkRole->id]);
        $bk->assignRole('Guru BK');

        $guru = User::factory()->create(['role_id' => $guruRole->id]);
        $guru->assignRole('Guru');

        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $superAdmin->assignRole('Super Admin');

        // Wali Kelas TIDAK BISA melihat maupun menginput jurnal di Filament & Gate
        $this->actingAs($wali);
        $this->assertFalse(JournalResource::canViewAny());
        $this->assertFalse(JournalResource::canCreate());
        $this->assertFalse(Gate::forUser($wali)->allows('viewAny', Journal::class));
        $this->assertFalse(Gate::forUser($wali)->allows('create', Journal::class));

        // Guru BK TIDAK BISA melihat maupun menginput jurnal di Filament & Gate
        $this->actingAs($bk);
        $this->assertFalse(JournalResource::canViewAny());
        $this->assertFalse(JournalResource::canCreate());
        $this->assertFalse(Gate::forUser($bk)->allows('viewAny', Journal::class));
        $this->assertFalse(Gate::forUser($bk)->allows('create', Journal::class));

        // Wali Kelas & Guru BK juga tidak bisa akses API teacher journals
        $this->actingAs($wali, 'sanctum');
        $responseWali = $this->getJson('/api/teacher/journals');
        $responseWali->assertForbidden();

        $this->actingAs($bk, 'sanctum');
        $responseBk = $this->getJson('/api/teacher/journals');
        $responseBk->assertForbidden();

        // Guru BISA melihat dan menginput jurnal
        $this->actingAs($guru);
        $this->assertTrue(JournalResource::canViewAny());
        $this->assertTrue(JournalResource::canCreate());
        $this->assertTrue(Gate::forUser($guru)->allows('viewAny', Journal::class));
        $this->assertTrue(Gate::forUser($guru)->allows('create', Journal::class));

        // Super Admin BISA melihat dan menginput jurnal
        $this->actingAs($superAdmin);
        $this->assertTrue(JournalResource::canViewAny());
        $this->assertTrue(JournalResource::canCreate());
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Journal::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Journal::class));
    }
}
