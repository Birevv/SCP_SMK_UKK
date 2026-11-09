<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Permission View:AttendanceReport untuk Waka Kurikulum dan Guru
        $attendanceReportPerm = Permission::firstOrCreate([
            'name' => 'View:AttendanceReport',
            'guard_name' => 'web',
        ]);

        $wakaRole = Role::where('name', 'Waka Kurikulum')->where('guard_name', 'web')->first();
        if ($wakaRole && ! $wakaRole->hasPermissionTo($attendanceReportPerm)) {
            $wakaRole->givePermissionTo($attendanceReportPerm);
        }

        $guruRole = Role::where('name', 'Guru')->where('guard_name', 'web')->first();
        if ($guruRole && ! $guruRole->hasPermissionTo($attendanceReportPerm)) {
            $guruRole->givePermissionTo($attendanceReportPerm);
        }

        // 2. Permission ViewAny:Schedule & View:Schedule untuk Siswa, Wali Kelas, dan Guru BK (Read-Only)
        $viewAnySchedulePerm = Permission::firstOrCreate([
            'name' => 'ViewAny:Schedule',
            'guard_name' => 'web',
        ]);

        $viewSchedulePerm = Permission::firstOrCreate([
            'name' => 'View:Schedule',
            'guard_name' => 'web',
        ]);

        $readOnlyScheduleRoles = ['Wali Kelas', 'Guru BK', 'Siswa'];
        foreach ($readOnlyScheduleRoles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                if (! $role->hasPermissionTo($viewAnySchedulePerm)) {
                    $role->givePermissionTo($viewAnySchedulePerm);
                }
                if (! $role->hasPermissionTo($viewSchedulePerm)) {
                    $role->givePermissionTo($viewSchedulePerm);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $wakaRole = Role::where('name', 'Waka Kurikulum')->where('guard_name', 'web')->first();
        if ($wakaRole && $wakaRole->hasPermissionTo('View:AttendanceReport')) {
            $wakaRole->revokePermissionTo('View:AttendanceReport');
        }

        $guruRole = Role::where('name', 'Guru')->where('guard_name', 'web')->first();
        if ($guruRole && $guruRole->hasPermissionTo('View:AttendanceReport')) {
            $guruRole->revokePermissionTo('View:AttendanceReport');
        }

        $readOnlyScheduleRoles = ['Wali Kelas', 'Guru BK', 'Siswa'];
        foreach ($readOnlyScheduleRoles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->revokePermissionTo('ViewAny:Schedule');
                $role->revokePermissionTo('View:Schedule');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
