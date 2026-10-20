<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"Super Admin","guard_name":"web","permissions":["ViewAny:AcademicYear","View:AcademicYear","Create:AcademicYear","Update:AcademicYear","Delete:AcademicYear","DeleteAny:AcademicYear","Restore:AcademicYear","ForceDelete:AcademicYear","ForceDeleteAny:AcademicYear","RestoreAny:AcademicYear","Replicate:AcademicYear","Reorder:AcademicYear","ViewAny:Classroom","View:Classroom","Create:Classroom","Update:Classroom","Delete:Classroom","DeleteAny:Classroom","Restore:Classroom","ForceDelete:Classroom","ForceDeleteAny:Classroom","RestoreAny:Classroom","Replicate:Classroom","Reorder:Classroom","ViewAny:Journal","View:Journal","Create:Journal","Update:Journal","Delete:Journal","DeleteAny:Journal","Restore:Journal","ForceDelete:Journal","ForceDeleteAny:Journal","RestoreAny:Journal","Replicate:Journal","Reorder:Journal","ViewAny:LeaveRequest","View:LeaveRequest","Create:LeaveRequest","Update:LeaveRequest","Delete:LeaveRequest","DeleteAny:LeaveRequest","Restore:LeaveRequest","ForceDelete:LeaveRequest","ForceDeleteAny:LeaveRequest","RestoreAny:LeaveRequest","Replicate:LeaveRequest","Reorder:LeaveRequest","ViewAny:Room","View:Room","Create:Room","Update:Room","Delete:Room","DeleteAny:Room","Restore:Room","ForceDelete:Room","ForceDeleteAny:Room","RestoreAny:Room","Replicate:Room","Reorder:Room","ViewAny:Schedule","View:Schedule","Create:Schedule","Update:Schedule","Delete:Schedule","DeleteAny:Schedule","Restore:Schedule","ForceDelete:Schedule","ForceDeleteAny:Schedule","RestoreAny:Schedule","Replicate:Schedule","Reorder:Schedule","ViewAny:Subject","View:Subject","Create:Subject","Update:Subject","Delete:Subject","DeleteAny:Subject","Restore:Subject","ForceDelete:Subject","ForceDeleteAny:Subject","RestoreAny:Subject","Replicate:Subject","Reorder:Subject","ViewAny:SystemConfiguration","View:SystemConfiguration","Create:SystemConfiguration","Update:SystemConfiguration","Delete:SystemConfiguration","DeleteAny:SystemConfiguration","Restore:SystemConfiguration","ForceDelete:SystemConfiguration","ForceDeleteAny:SystemConfiguration","RestoreAny:SystemConfiguration","Replicate:SystemConfiguration","Reorder:SystemConfiguration","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","lock_schedule","toggle_cycle","approve_leave_request","View:SystemConfigWidget","View:UnfilledJournalAlertWidget","View:WakaStatsWidget","View:AttendanceReport","View:TeacherStatsWidget","View:HomeroomStatsWidget","View:StudentAttendanceWidget"]},{"name":"Waka Kurikulum","guard_name":"web","permissions":["ViewAny:AcademicYear","View:AcademicYear","Create:AcademicYear","Update:AcademicYear","Delete:AcademicYear","DeleteAny:AcademicYear","ViewAny:Classroom","View:Classroom","Create:Classroom","Update:Classroom","Delete:Classroom","DeleteAny:Classroom","ViewAny:Journal","View:Journal","ViewAny:LeaveRequest","View:LeaveRequest","ViewAny:Room","View:Room","Create:Room","Update:Room","Delete:Room","DeleteAny:Room","ViewAny:Schedule","View:Schedule","Create:Schedule","Update:Schedule","Delete:Schedule","ViewAny:Subject","View:Subject","Create:Subject","Update:Subject","Delete:Subject","DeleteAny:Subject","ViewAny:SystemConfiguration","View:SystemConfiguration","Update:SystemConfiguration","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","lock_schedule","toggle_cycle","View:SystemConfigWidget","View:UnfilledJournalAlertWidget","View:WakaStatsWidget","View:AttendanceReport"]},{"name":"Guru","guard_name":"web","permissions":["ViewAny:Journal","View:Journal","Create:Journal","Update:Journal","ViewAny:Schedule","View:Schedule","Create:Schedule","Update:Schedule","Delete:Schedule","View:UnfilledJournalAlertWidget","View:TeacherStatsWidget"]},{"name":"Wali Kelas","guard_name":"web","permissions":["ViewAny:LeaveRequest","View:LeaveRequest","Update:LeaveRequest","approve_leave_request","View:AttendanceReport","View:HomeroomStatsWidget"]},{"name":"Guru BK","guard_name":"web","permissions":["ViewAny:LeaveRequest","View:LeaveRequest","Update:LeaveRequest","approve_leave_request","View:AttendanceReport","View:HomeroomStatsWidget"]},{"name":"Siswa","guard_name":"web","permissions":["ViewAny:LeaveRequest","View:LeaveRequest","Create:LeaveRequest","View:StudentAttendanceWidget"]}]';
        $directPermissions = '[]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
