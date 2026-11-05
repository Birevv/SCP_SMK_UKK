<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $wakaUser;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ShieldSeeder::class);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'email' => 'superadmin@test.com',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->wakaUser = User::factory()->create([
            'role_id' => $wakaRole->id,
            'email' => 'waka@test.com',
        ]);
        $this->wakaUser->assignRole('Waka Kurikulum');
    }

    public function test_waka_with_delete_any_permission_can_access_bulk_delete(): void
    {
        $this->actingAs($this->wakaUser);

        $this->wakaUser->givePermissionTo('ViewAny:User');
        $this->wakaUser->givePermissionTo('Delete:User');
        $this->wakaUser->givePermissionTo('DeleteAny:User');

        $studentRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        $student1 = User::factory()->create(['role_id' => $studentRole->id]);
        $student2 = User::factory()->create(['role_id' => $studentRole->id]);

        $component = Livewire::test(ListUsers::class);
        $component->assertSuccessful();
        $component->assertTableBulkActionExists(DeleteBulkAction::class);

        $component->callTableBulkAction(DeleteBulkAction::class, [$student1, $student2]);

        $this->assertSoftDeleted('users', ['id' => $student1->id]);
        $this->assertSoftDeleted('users', ['id' => $student2->id]);
    }

    public function test_waka_cannot_delete_super_admin_or_self(): void
    {
        $this->actingAs($this->wakaUser);

        $this->wakaUser->givePermissionTo('ViewAny:User');
        $this->wakaUser->givePermissionTo('Delete:User');
        $this->wakaUser->givePermissionTo('DeleteAny:User');

        $component = Livewire::test(ListUsers::class);
        $component->assertSuccessful();
        $component->assertTableBulkActionExists(DeleteBulkAction::class);

        $component->callTableBulkAction(DeleteBulkAction::class, [$this->superAdmin, $this->wakaUser]);

        $this->assertNotSoftDeleted('users', ['id' => $this->superAdmin->id]);
        $this->assertNotSoftDeleted('users', ['id' => $this->wakaUser->id]);
    }

    public function test_bulk_delete_is_hidden_without_delete_any_permission(): void
    {
        $restrictedRole = Role::firstOrCreate(['name' => 'Restricted User', 'guard_name' => 'web']);
        $restrictedUser = User::factory()->create([
            'role_id' => $restrictedRole->id,
            'email' => 'restricted@test.com',
        ]);
        $restrictedUser->assignRole('Restricted User');

        $restrictedUser->givePermissionTo('ViewAny:User');

        $this->actingAs($restrictedUser);

        $component = Livewire::test(ListUsers::class);
        $component->assertSuccessful();
        $component->assertTableBulkActionHidden(DeleteBulkAction::class);
    }

    public function test_create_user_form_has_default_password(): void
    {
        $this->actingAs($this->superAdmin);

        $component = Livewire::test(CreateUser::class);
        $component->assertSuccessful();
        $component->assertSchemaStateSet([
            'password' => 'password',
        ]);
    }
}
