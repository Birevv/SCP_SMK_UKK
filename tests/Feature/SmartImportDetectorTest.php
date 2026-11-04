<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\User;
use App\Services\SmartImportDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmartImportDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_parse_and_import_student_roster_data(): void
    {
        $service = app(SmartImportDetectorService::class);

        $dummyData = [
            'type' => 'student_roster',
            'academic_year' => '2026-2027',
            'students' => [
                [
                    'class' => 'X RPL 1',
                    'no' => 1,
                    'nis' => '19001',
                    'name' => 'Budi Santoso',
                    'gender' => 'L',
                ],
                [
                    'class' => 'X RPL 1',
                    'no' => 2,
                    'nis' => '19002',
                    'name' => 'Siti Aminah',
                    'gender' => 'P',
                ],
            ],
        ];

        $result = $service->import($dummyData);

        $this->assertEquals('success', $result['status']);
        $this->assertDatabaseHas('classrooms', ['name' => 'X RPL 1']);
        $this->assertDatabaseHas('users', [
            'identity_number' => '19001',
            'full_name' => 'Budi Santoso',
        ]);
        $this->assertDatabaseHas('users', [
            'identity_number' => '19002',
            'full_name' => 'Siti Aminah',
        ]);
    }

    public function test_service_can_parse_and_import_fet_timetable_data(): void
    {
        $service = app(SmartImportDetectorService::class);

        $ay = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $dummyData = [
            'type' => 'timetable_fet',
            'cycle_type' => 'Putaran 1',
            'classes' => ['X RPL 1'],
            'subjects' => ['Matematika'],
            'rooms' => ['LAB 16'],
            'teachers' => [
                ['code' => '34', 'name' => 'Dwi Lesmiyati, S.Pd.'],
            ],
            'schedules' => [
                [
                    'classroom' => 'X RPL 1',
                    'day_of_week' => 'Senin',
                    'start_time' => '07:15:00',
                    'end_time' => '09:30:00',
                    'cycle_type' => 'Putaran 1',
                    'subject' => 'Matematika',
                    'teacher_code' => '34',
                    'teacher_name' => 'Dwi Lesmiyati, S.Pd.',
                    'room' => 'LAB 16',
                ],
            ],
        ];

        $result = $service->import($dummyData);

        $this->assertEquals('success', $result['status']);
        $this->assertDatabaseHas('classrooms', ['name' => 'X RPL 1']);
        $this->assertDatabaseHas('subjects', ['name' => 'Matematika']);
        $this->assertDatabaseHas('rooms', ['name' => 'LAB 16']);
        $this->assertDatabaseHas('users', ['full_name' => 'Dwi Lesmiyati, S.Pd.']);
        $this->assertDatabaseHas('schedules', [
            'day_of_week' => 'Senin',
            'start_time' => '07:15:00',
            'end_time' => '09:30:00',
        ]);
    }

    public function test_list_users_page_can_mount_import_action(): void
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)
            ->mountAction('import_users')
            ->assertActionMounted('import_users');
    }

    public function test_list_classrooms_page_can_mount_import_action(): void
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Filament\Resources\Classrooms\Pages\ListClassrooms::class)
            ->mountAction('import_smart')
            ->assertActionMounted('import_smart');
    }

    public function test_list_schedules_page_can_mount_import_action(): void
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Filament\Resources\Schedules\Pages\ListSchedules::class)
            ->mountAction('import_schedule')
            ->assertActionMounted('import_schedule');
    }
}
