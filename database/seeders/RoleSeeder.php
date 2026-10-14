<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'Super Admin',
            'Waka Kurikulum',
            'Guru',
            'Wali Kelas',
            'Guru BK',
            'Siswa',
        ];

        $roleInstances = [];
        foreach ($roles as $role) {
            $roleInstances[$role] = Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // Super Admin gets all permissions
        $allPermissions = Permission::all();
        $roleInstances['Super Admin']->syncPermissions($allPermissions);

        // Helper to safely get permissions that exist
        $getExisting = function (array $names) {
            return Permission::whereIn('name', $names)->get();
        };

        // 1. Waka Kurikulum
        $wakaPermissions = $getExisting([
            // Master Data (Class, Room, User, Role, AcademicYear, Subject, SystemConfig)
            'ViewAny:Classroom', 'View:Classroom', 'Create:Classroom', 'Update:Classroom', 'Delete:Classroom', 'DeleteAny:Classroom',
            'ViewAny:Room', 'View:Room', 'Create:Room', 'Update:Room', 'Delete:Room', 'DeleteAny:Room',
            'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'DeleteAny:User',
            'ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role', 'Delete:Role', 'DeleteAny:Role',
            'ViewAny:AcademicYear', 'View:AcademicYear', 'Create:AcademicYear', 'Update:AcademicYear', 'Delete:AcademicYear', 'DeleteAny:AcademicYear',
            'ViewAny:Subject', 'View:Subject', 'Create:Subject', 'Update:Subject', 'Delete:Subject', 'DeleteAny:Subject',
            'ViewAny:SystemConfiguration', 'View:SystemConfiguration', 'Update:SystemConfiguration',
            // Schedule (view_any, view, update, delete)
            'ViewAny:Schedule', 'View:Schedule', 'Update:Schedule', 'Delete:Schedule',
            // Journal & LeaveRequest (view_any, view)
            'ViewAny:Journal', 'View:Journal',
            'ViewAny:LeaveRequest', 'View:LeaveRequest',
            // Pages & Widgets
            'View:SystemConfigWidget', 'View:UnfilledJournalAlertWidget', 'View:WakaStatsWidget', 'View:AttendanceReport',
            // Custom Permissions
            'lock_schedule', 'toggle_cycle',
        ]);
        $roleInstances['Waka Kurikulum']->syncPermissions($wakaPermissions);

        // 2. Guru
        $guruPermissions = $getExisting([
            // Schedule (view_any, view, create, update, delete)
            'ViewAny:Schedule', 'View:Schedule', 'Create:Schedule', 'Update:Schedule', 'Delete:Schedule',
            // Journal (view_any, view, create, update)
            'ViewAny:Journal', 'View:Journal', 'Create:Journal', 'Update:Journal',
            // Widgets
            'View:TeacherStatsWidget', 'View:UnfilledJournalAlertWidget',
        ]);
        $roleInstances['Guru']->syncPermissions($guruPermissions);

        // 3. Wali Kelas & Guru BK (Tim Kesiswaan)
        $kesiswaanPermissions = $getExisting([
            // Journal (view_any, view)
            'ViewAny:Journal', 'View:Journal',
            // LeaveRequest (view_any, view, update)
            'ViewAny:LeaveRequest', 'View:LeaveRequest', 'Update:LeaveRequest',
            // Pages & Widgets
            'View:HomeroomStatsWidget', 'View:AttendanceReport',
            // Custom Permissions
            'approve_leave_request',
        ]);
        $roleInstances['Wali Kelas']->syncPermissions($kesiswaanPermissions);
        $roleInstances['Guru BK']->syncPermissions($kesiswaanPermissions);

        // 4. Siswa & Orang Tua
        $siswaPermissions = $getExisting([
            // Schedule (view_any, view)
            'ViewAny:Schedule', 'View:Schedule',
            // LeaveRequest (view_any, view, create)
            'ViewAny:LeaveRequest', 'View:LeaveRequest', 'Create:LeaveRequest',
            // Widgets
            'View:StudentAttendanceWidget',
        ]);
        $roleInstances['Siswa']->syncPermissions($siswaPermissions);
    }
}
