<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Resources\Classrooms\ClassroomResource;
use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Filament\Resources\SystemConfigurations\SystemConfigurationResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\SystemConfigWidget;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Role::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', Role::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $superAdminRole));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $superAdminRole));

        // Test with Spatie model instance directly as passed by Filament Shield
        $spatieRole = \Spatie\Permission\Models\Role::first();
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $spatieRole));

        // Waka Kurikulum must NOT have access to manage Spatie Roles
        $this->assertFalse(Gate::forUser($waka)->allows('viewAny', Role::class));
        $this->assertFalse(Gate::forUser($waka)->allows('create', Role::class));
        $this->assertFalse(Gate::forUser($waka)->allows('update', $superAdminRole));
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

        // 2. Waka TIDAK BISA mengakses resource master data (dipindahkan ke Super Admin)
        $this->assertFalse(UserResource::canViewAny());
        $this->assertFalse(AcademicYearResource::canViewAny());
        $this->assertFalse(SubjectResource::canViewAny());
        $this->assertFalse(RoomResource::canViewAny());
        $this->assertFalse(ClassroomResource::canViewAny());
        $this->assertFalse(SystemConfigurationResource::canViewAny());
        $this->assertFalse(Gate::forUser($waka)->allows('viewAny', Schedule::class));

        // 3. Super Admin BISA mengakses seluruh master data
        $this->actingAs($superAdmin);
        $this->assertTrue(SystemConfigWidget::canView());
        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(AcademicYearResource::canViewAny());
        $this->assertTrue(SubjectResource::canViewAny());
        $this->assertTrue(RoomResource::canViewAny());
        $this->assertTrue(ClassroomResource::canViewAny());
        $this->assertTrue(SystemConfigurationResource::canViewAny());
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewAny', Schedule::class));
    }
}
