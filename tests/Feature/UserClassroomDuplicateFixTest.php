<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
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

class UserClassroomDuplicateFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->admin->assignRole('Super Admin');

        AcademicYear::create([
            'name' => '2026/2027',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_user_form_classroom_options_have_no_duplicates(): void
    {
        $level = ClassLevel::create(['level' => 10, 'name' => 'X']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel1 = Rombel::create(['name' => '1', 'number' => 1]);
        $rombel2 = Rombel::create(['name' => '2', 'number' => 2]);

        $classA = Classroom::create([
            'name' => 'X RPL 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel1->id,
        ]);

        $classB = Classroom::create([
            'name' => 'X RPL 2',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel2->id,
        ]);

        // Tambahkan banyak siswa ke kelas A (yang sebelumnya memicu duplikasi opsi di query pivot)
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        for ($i = 1; $i <= 5; $i++) {
            $student = User::factory()->create(['role_id' => $siswaRole->id]);
            $student->classrooms()->attach($classA->id, ['status' => 'Aktif']);
        }

        // Test form create user
        $component = Livewire::actingAs($this->admin)->test(CreateUser::class);
        $options = $component->instance()->form->getComponent('activeClassrooms')->getOptions();

        $this->assertNotEmpty($options);
        // Pastikan tidak ada nama kelas yang duplikat dalam options
        $this->assertEquals(count($options), count(array_unique($options)));

        // Pastikan key option adalah ID kelas (bukan ID pivot user_class)
        $this->assertArrayHasKey($classA->id, $options);
        $this->assertArrayHasKey($classB->id, $options);
        $this->assertEquals('X RPL 1', $options[$classA->id]);
        $this->assertEquals('X RPL 2', $options[$classB->id]);
    }

    public function test_users_table_classroom_filter_works_correctly(): void
    {
        $level = ClassLevel::create(['level' => 10, 'name' => 'X']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel1 = Rombel::create(['name' => '1', 'number' => 1]);
        $rombel2 = Rombel::create(['name' => '2', 'number' => 2]);

        $classA = Classroom::create([
            'name' => 'X RPL 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel1->id,
        ]);

        $classB = Classroom::create([
            'name' => 'X RPL 2',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel2->id,
        ]);

        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        $studentA = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Budi Siswa RPL 1']);
        $studentA->classrooms()->attach($classA->id, ['status' => 'Aktif']);

        $studentB = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Siti Siswa RPL 2']);
        $studentB->classrooms()->attach($classB->id, ['status' => 'Aktif']);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->filterTable('activeClassrooms', $classA->id)
            ->assertCanSeeTableRecords([$studentA])
            ->assertCanNotSeeTableRecords([$studentB]);
    }
}
