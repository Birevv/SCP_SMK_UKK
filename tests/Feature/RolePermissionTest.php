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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ShieldSeeder::class);
    }

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
        $this->actingAs($guru, 'web');
        $this->assertTrue(JournalResource::canViewAny());
        $this->assertTrue(JournalResource::canCreate());
        $this->assertTrue(Gate::forUser($guru)->allows('viewAny', Journal::class));
        $this->assertTrue(Gate::forUser($guru)->allows('create', Journal::class));

        // Super Admin BISA melihat dan menginput jurnal
        $this->actingAs($superAdmin, 'web');
        $this->assertTrue(JournalResource::canViewAny());
        $this->assertTrue(JournalResource::canCreate());
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Journal::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Journal::class));
    }

    public function test_teacher_cannot_view_or_print_attendance_report(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $waliRole = Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        $bkRole = Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $guru = User::factory()->create(['role_id' => $guruRole->id]);
        $guru->assignRole('Guru');

        $siswa = User::factory()->create(['role_id' => $siswaRole->id]);
        $siswa->assignRole('Siswa');

        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        $wali = User::factory()->create(['role_id' => $waliRole->id]);
        $wali->assignRole('Wali Kelas');

        $bk = User::factory()->create(['role_id' => $bkRole->id]);
        $bk->assignRole('Guru BK');

        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $superAdmin->assignRole('Super Admin');

        // 1. Guru TIDAK BISA mengakses AttendanceReport maupun route print
        $this->actingAs($guru);
        $this->assertFalse(\App\Filament\Pages\AttendanceReport::canAccess());
        $this->get('/admin/attendance-report/print')->assertForbidden();

        // 2. Siswa TIDAK BISA mengakses AttendanceReport maupun route print
        $this->actingAs($siswa);
        $this->assertFalse(\App\Filament\Pages\AttendanceReport::canAccess());
        $this->get('/admin/attendance-report/print')->assertForbidden();

        // 3. Waka Kurikulum BISA mengakses AttendanceReport & route print
        $this->actingAs($waka);
        $this->assertTrue(\App\Filament\Pages\AttendanceReport::canAccess());
        $this->get('/admin/attendance-report/print')->assertSuccessful();

        // 4. Wali Kelas BISA mengakses AttendanceReport & route print
        $this->actingAs($wali);
        $this->assertTrue(\App\Filament\Pages\AttendanceReport::canAccess());
        $this->get('/admin/attendance-report/print')->assertSuccessful();

        // 5. Guru BK BISA mengakses AttendanceReport & route print
        $this->actingAs($bk);
        $this->assertTrue(\App\Filament\Pages\AttendanceReport::canAccess());
        $this->get('/admin/attendance-report/print')->assertSuccessful();

        // 6. Super Admin BISA mengakses AttendanceReport & route print
        $this->actingAs($superAdmin);
        $this->assertTrue(\App\Filament\Pages\AttendanceReport::canAccess());
        $this->get('/admin/attendance-report/print')->assertSuccessful();
    }

    public function test_student_can_delete_own_leave_request_but_not_others(): void
    {
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $student1 = User::factory()->create(['role_id' => $siswaRole->id]);
        $student1->assignRole('Siswa');

        $student2 = User::factory()->create(['role_id' => $siswaRole->id]);
        $student2->assignRole('Siswa');

        $leaveRequest1 = \App\Models\LeaveRequest::create([
            'student_id' => $student1->id,
            'submitted_by' => $student1->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Izin',
            'status' => 'Pending',
            'description' => 'Keperluan keluarga',
        ]);

        $leaveRequest2 = \App\Models\LeaveRequest::create([
            'student_id' => $student2->id,
            'submitted_by' => $student2->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Sakit',
            'status' => 'Pending',
            'description' => 'Demam',
        ]);

        // Student 1 BISA menghapus izin miliknya sendiri via Gate dan Resource
        $this->actingAs($student1);
        $this->assertTrue(Gate::forUser($student1)->allows('delete', $leaveRequest1));
        $this->assertTrue(\App\Filament\Resources\LeaveRequests\LeaveRequestResource::canDelete($leaveRequest1));

        // Student 1 TIDAK BISA menghapus izin milik student 2
        $this->assertFalse(Gate::forUser($student1)->allows('delete', $leaveRequest2));
        $this->assertFalse(\App\Filament\Resources\LeaveRequests\LeaveRequestResource::canDelete($leaveRequest2));

        // Test API endpoint: Student 1 tidak bisa menghapus leave request milik Student 2
        $this->actingAs($student1, 'sanctum');
        $deleteOtherResponse = $this->deleteJson('/api/student/leave-requests/'.$leaveRequest2->id);
        $deleteOtherResponse->assertStatus(403);
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest2->id]);

        // Test API endpoint: Student 1 BISA menghapus leave request miliknya sendiri
        $deleteOwnResponse = $this->deleteJson('/api/student/leave-requests/'.$leaveRequest1->id);
        $deleteOwnResponse->assertSuccessful();
        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveRequest1->id]);
    }

    public function test_shield_permissions_dynamically_control_access_without_hardcode(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        $this->actingAs($waka, 'web');

        // Secara default Waka tidak memiliki permission ViewAny:AcademicYear
        $this->assertFalse(AcademicYearResource::canViewAny());
        $this->assertFalse(Gate::forUser($waka)->allows('viewAny', \App\Models\AcademicYear::class));

        // Simulasi perubahan permission lewat UI Shield (memberikan ViewAny:AcademicYear ke Waka)
        $wakaRole->givePermissionTo('ViewAny:AcademicYear');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Waka SEKARANG BISA mengakses AcademicYearResource secara dinamis tanpa hardcode
        $this->assertTrue(AcademicYearResource::canViewAny());
        $this->assertTrue(Gate::forUser($waka)->allows('viewAny', \App\Models\AcademicYear::class));

        // Cabut kembali permission
        $wakaRole->revokePermissionTo('ViewAny:AcademicYear');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Waka kembali TIDAK BISA mengakses
        $this->assertFalse(AcademicYearResource::canViewAny());
        $this->assertFalse(Gate::forUser($waka)->allows('viewAny', \App\Models\AcademicYear::class));
    }

    public function test_locked_schedule_prevents_waka_and_guru_from_adding_or_modifying_schedules(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $superAdmin->assignRole('Super Admin');

        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        $guru = User::factory()->create(['role_id' => $guruRole->id]);
        $guru->assignRole('Guru');

        $config = \App\Models\SystemConfiguration::firstOrCreate([], [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        // 1. KETIKA JADWAL TERKUNCI (is_schedule_locked = true)
        $config->update(['is_schedule_locked' => true]);

        // Waka: BISA melihat jadwal, TIDAK BISA menambah jadwal
        $this->actingAs($waka, 'web');
        $this->assertTrue(Gate::forUser($waka)->allows('viewAny', Schedule::class));
        $this->assertFalse(Gate::forUser($waka)->allows('create', Schedule::class));
        $this->assertFalse(\App\Filament\Resources\Schedules\ScheduleResource::canCreate());

        // Guru: BISA melihat jadwal, TIDAK BISA menambah jadwal
        $this->actingAs($guru, 'web');
        $this->assertTrue(Gate::forUser($guru)->allows('viewAny', Schedule::class));
        $this->assertFalse(Gate::forUser($guru)->allows('create', Schedule::class));
        $this->assertFalse(\App\Filament\Resources\Schedules\ScheduleResource::canCreate());

        // Super Admin: TETAP BISA menambah jadwal
        $this->actingAs($superAdmin, 'web');
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Schedule::class));
        $this->assertTrue(\App\Filament\Resources\Schedules\ScheduleResource::canCreate());

        // 2. KETIKA JADWAL DIBUKA (is_schedule_locked = false)
        $config->update(['is_schedule_locked' => false]);

        $this->actingAs($waka, 'web');
        $this->assertTrue(Gate::forUser($waka)->allows('create', Schedule::class));
        $this->assertTrue(\App\Filament\Resources\Schedules\ScheduleResource::canCreate());

        $this->actingAs($guru, 'web');
        $this->assertTrue(Gate::forUser($guru)->allows('create', Schedule::class));
        $this->assertTrue(\App\Filament\Resources\Schedules\ScheduleResource::canCreate());
    }
}


