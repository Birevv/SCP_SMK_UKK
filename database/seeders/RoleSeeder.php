<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Auto-generate Shield permissions if none exist (e.g. after migrate:fresh)
        if (Permission::count() === 0) {
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--option' => 'permissions',
            ]);
        }

        // 2. Ensure custom permissions exist
        $customPermissions = [
            'lock_schedule',
            'toggle_cycle',
            'approve_leave_request',
        ];
        foreach ($customPermissions as $customPerm) {
            Permission::firstOrCreate([
                'name' => $customPerm,
                'guard_name' => 'web',
            ]);
        }

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

        // Helper to safely get or create permissions
        $getPermissions = function (array $names) {
            $permissions = [];
            foreach ($names as $name) {
                $permissions[] = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }

            return $permissions;
        };

        // 1. Waka Kurikulum
        $wakaPermissions = $getPermissions([
            // Master Data (Class, Room, User, AcademicYear, Subject, SystemConfig)
            'ViewAny:Classroom', 'View:Classroom', 'Create:Classroom', 'Update:Classroom', 'Delete:Classroom', 'DeleteAny:Classroom',
            'ViewAny:Room', 'View:Room', 'Create:Room', 'Update:Room', 'Delete:Room', 'DeleteAny:Room',
            'ViewAny:User', 'View:User', 'Create:User', 'Update:User', 'Delete:User', 'DeleteAny:User',
            'ViewAny:AcademicYear', 'View:AcademicYear', 'Create:AcademicYear', 'Update:AcademicYear', 'Delete:AcademicYear', 'DeleteAny:AcademicYear',
            'ViewAny:Subject', 'View:Subject', 'Create:Subject', 'Update:Subject', 'Delete:Subject', 'DeleteAny:Subject',
            'ViewAny:SystemConfiguration', 'View:SystemConfiguration', 'Update:SystemConfiguration',
            // Schedule (view_any, view, create, update, delete)
            'ViewAny:Schedule', 'View:Schedule', 'Create:Schedule', 'Update:Schedule', 'Delete:Schedule',
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
        $guruPermissions = $getPermissions([
            // Schedule (view_any, view, create, update, delete)
            'ViewAny:Schedule', 'View:Schedule', 'Create:Schedule', 'Update:Schedule', 'Delete:Schedule',
            // Journal (view_any, view, create, update)
            'ViewAny:Journal', 'View:Journal', 'Create:Journal', 'Update:Journal',
            // Widgets
            'View:TeacherStatsWidget', 'View:UnfilledJournalAlertWidget',
        ]);
        $roleInstances['Guru']->syncPermissions($guruPermissions);

        // 3. Wali Kelas & Guru BK (Tim Kesiswaan)
        $kesiswaanPermissions = $getPermissions([
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
        $siswaPermissions = $getPermissions([
            // LeaveRequest (view_any, view, create)
            'ViewAny:LeaveRequest', 'View:LeaveRequest', 'Create:LeaveRequest',
            // Widgets
            'View:StudentAttendanceWidget',
        ]);
        $roleInstances['Siswa']->syncPermissions($siswaPermissions);

        // 5. Super Admin gets all permissions
        $roleInstances['Super Admin']->syncPermissions(Permission::all());

        // Final cache flush
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
