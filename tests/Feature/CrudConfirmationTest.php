<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYears\Pages\CreateAcademicYear;
use App\Filament\Resources\AcademicYears\Pages\EditAcademicYear;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CrudConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'admin_confirmation@test.com',
        ]);
        $this->adminUser->assignRole('Super Admin');
    }

    public function test_create_form_validates_and_requires_confirmation_before_saving(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Validasi form berjalan sebelum modal dibuka jika input kosong / tidak valid
        $component = Livewire::test(CreateAcademicYear::class);
        $component->call('mountAction', 'create', [], ['schemaComponent' => 'content.form-actions']);
        $component->assertHasErrors(['data.name']);
        $this->assertEmpty($component->get('mountedActions'));

        // 2. Jika input valid, modal konfirmasi berhasil terbuka (action termount)
        $component->set('data.name', '2027/2028 - Ganjil');
        $component->set('data.is_active', false);
        $component->call('mountAction', 'create', [], ['schemaComponent' => 'content.form-actions']);
        $this->assertNotEmpty($component->get('mountedActions'));

        // 3. Setelah konfirmasi diklik (callMountedAction), data tersimpan di database
        $component->call('callMountedAction');
        $this->assertDatabaseHas('academic_years', [
            'name' => '2027/2028 - Ganjil',
        ]);
    }

    public function test_edit_form_validates_and_requires_confirmation_before_updating(): void
    {
        $this->actingAs($this->adminUser);

        $year = AcademicYear::create([
            'name' => '2028/2029 - Genap',
            'is_active' => false,
        ]);

        $component = Livewire::test(EditAcademicYear::class, ['record' => $year->id]);

        // 1. Jika nama dikosongkan, validasi gagal dan modal tidak dibuka
        $component->set('data.name', '');
        $component->call('mountAction', 'save', [], ['schemaComponent' => 'content.form-actions']);
        $component->assertHasErrors(['data.name']);

        // 2. Jika valid, modal konfirmasi terbuka dan data berhasil diperbarui
        $component->set('data.name', '2028/2029 - Genap Diperbarui');
        $component->call('mountAction', 'save', [], ['schemaComponent' => 'content.form-actions']);
        $this->assertNotEmpty($component->get('mountedActions'));

        $component->call('callMountedAction');
        $this->assertDatabaseHas('academic_years', [
            'id' => $year->id,
            'name' => '2028/2029 - Genap Diperbarui',
        ]);
    }

    public function test_delete_action_globally_requires_confirmation_with_indonesian_labels(): void
    {
        $action = DeleteAction::make();

        $this->assertTrue($action->isConfirmationRequired());
        $this->assertStringContainsString('Konfirmasi Hapus', $action->getModalHeading());
        $this->assertStringContainsString('tidak dapat dibatalkan', $action->getModalDescription());
        $this->assertEquals('Ya, Hapus Data', $action->getModalSubmitActionLabel());
        $this->assertEquals('Batal', $action->getModalCancelActionLabel());
    }

    public function test_delete_bulk_action_globally_requires_confirmation_with_indonesian_labels(): void
    {
        $bulkAction = DeleteBulkAction::make();

        $this->assertTrue($bulkAction->isConfirmationRequired());
        $this->assertEquals('Konfirmasi Hapus Data Terpilih', $bulkAction->getModalHeading());
        $this->assertStringContainsString('tidak dapat dibatalkan', $bulkAction->getModalDescription());
        $this->assertEquals('Ya, Hapus Data Terpilih', $bulkAction->getModalSubmitActionLabel());
        $this->assertEquals('Batal', $bulkAction->getModalCancelActionLabel());
    }
}
