<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Classrooms\Pages\ListClassrooms;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\User;
use App\Services\SmartImportDetectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

        Livewire::test(ListUsers::class)
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

        Livewire::test(ListClassrooms::class)
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

        Livewire::test(ListSchedules::class)
            ->mountAction('import_schedule')
            ->assertActionMounted('import_schedule')
            ->callMountedAction()
            ->assertHasActionErrors([
                'class_schedule_file' => 'required',
                'room_schedule_file' => 'required',
            ]);
    }

    public function test_service_can_merge_and_import_schedule_pair_with_room_and_session_times(): void
    {
        $service = app(SmartImportDetectorService::class);

        AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $fetData = [
            'type' => 'timetable_fet',
            'cycle_type' => 'Putaran 1',
            'classes' => ['X RPL1', 'X RPL2'],
            'subjects' => ['Bahasa Indonesia', 'DDPK 3 RPL Dasar Algoritma Pemrograman'],
            'rooms' => ['LAB 16'],
            'teachers' => [
                ['code' => '6', 'name' => 'AMAT SUKARDI, S.Pd'],
                ['code' => '6_2', 'name' => 'Dra. SRI SULASTRI'],
            ],
            'schedules' => [
                [
                    'classroom' => 'X RPL1',
                    'day_of_week' => 'Senin',
                    'jam_start' => 1,
                    'jam_end' => 3,
                    'start_time' => '07:15:00',
                    'end_time' => '09:30:00',
                    'cycle_type' => 'Putaran 1',
                    'subject' => 'Bahasa Indonesia',
                    'teacher_code' => '6',
                    'teacher_name' => 'AMAT SUKARDI, S.Pd',
                    'room' => null,
                ],
                [
                    'classroom' => 'X RPL2',
                    'day_of_week' => 'Senin',
                    'jam_start' => 1,
                    'jam_end' => 6,
                    'start_time' => '07:15:00',
                    'end_time' => '12:00:00',
                    'cycle_type' => 'Putaran 1',
                    'subject' => 'DDPK 3 RPL Dasar Algoritma Pemrograman',
                    'teacher_code' => '6_2',
                    'teacher_name' => 'Dra. SRI SULASTRI',
                    'room' => 'LAB 16',
                ],
            ],
        ];

        $roomData = [
            'type' => 'room_schedule',
            'academic_year' => '2026/2027 - Ganjil',
            'cycle_type' => 'Putaran 1',
            'classes' => ['10 RPL 1', '10 RPL 2'],
            'rooms' => ['Ruang 12', 'LAB 16'],
            'session_times' => [
                'Senin' => [
                    1 => ['start' => '08:00:00', 'end' => '08:35:00'],
                    2 => ['start' => '08:35:00', 'end' => '09:10:00'],
                    3 => ['start' => '09:10:00', 'end' => '09:45:00'],
                    4 => ['start' => '10:00:00', 'end' => '10:35:00'],
                    5 => ['start' => '10:35:00', 'end' => '11:10:00'],
                    6 => ['start' => '11:10:00', 'end' => '11:45:00'],
                ],
            ],
            'room_matrix' => [
                'X RPL 1' => [
                    'Senin' => [1 => '12', 2 => '12', 3 => '12'],
                ],
                'X RPL 2' => [
                    'Senin' => [1 => 'L.16', 2 => 'L.16', 3 => 'L.16', 4 => 'L.16', 5 => 'L.16', 6 => 'L.16'],
                ],
            ],
        ];

        $merged = $service->mergeSchedulePairArrays($fetData, $roomData);
        $this->assertTrue($merged['has_room_schedule']);
        $this->assertEquals('Ruang 12', $merged['schedules'][0]['room']);
        $this->assertEquals('08:00:00', $merged['schedules'][0]['start_time']);
        $this->assertEquals('09:45:00', $merged['schedules'][0]['end_time']);
        $this->assertEquals('LAB 16', $merged['schedules'][1]['room']);
        $this->assertEquals('08:00:00', $merged['schedules'][1]['start_time']);
        $this->assertEquals('11:45:00', $merged['schedules'][1]['end_time']);

        $result = $service->import($merged);
        $this->assertEquals('success', $result['status']);

        $this->assertDatabaseHas('rooms', ['name' => 'Ruang 12']);
        $this->assertDatabaseHas('rooms', ['name' => 'LAB 16']);
        $this->assertDatabaseHas('users', ['full_name' => 'AMAT SUKARDI, S.Pd']);
        $this->assertDatabaseHas('users', ['full_name' => 'Dra. SRI SULASTRI']);
        $this->assertDatabaseHas('school_sessions', [
            'day_of_week' => 'Senin',
            'session_number' => 1,
            'start_time' => '08:00',
            'end_time' => '08:35',
        ]);
        $this->assertDatabaseHas('schedules', [
            'day_of_week' => 'Senin',
            'start_time' => '08:00:00',
            'end_time' => '09:45:00',
        ]);
    }

    public function test_importing_putaran_1_and_putaran_2_has_no_conflicts_or_redundancy(): void
    {
        $service = app(SmartImportDetectorService::class);

        AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $putaran1Data = [
            'type' => 'timetable_fet',
            'has_room_schedule' => true,
            'cycle_type' => 'Putaran 1',
            'classes' => ['X RPL 1', 'X DKV 1'],
            'subjects' => ['KK 1 DKV Karya UI/ UX', 'Bahasa Indonesia'],
            'rooms' => ['LAB 16', 'Ruang 12'],
            'teachers' => [
                ['code' => '60', 'name' => 'SRI NUR AROFAH, S.Pd'],
                ['code' => '25', 'name' => 'UMI KUNTARI, S.S'],
            ],
            'schedules' => [
                [
                    'classroom' => 'X RPL 1',
                    'day_of_week' => 'Senin',
                    'start_time' => '08:00:00',
                    'end_time' => '11:45:00',
                    'cycle_type' => 'Putaran 1',
                    'subject' => 'Bahasa Indonesia',
                    'teacher_code' => '25',
                    'teacher_name' => 'UMI KUNTARI, S.S',
                    'room' => 'Ruang 12',
                ],
                [
                    'classroom' => 'X DKV 1',
                    'day_of_week' => 'Senin',
                    'start_time' => '08:00:00',
                    'end_time' => '15:10:00',
                    'cycle_type' => 'Putaran 1',
                    'subject' => 'KK 1 DKV Karya UI/ UX',
                    'teacher_code' => '60',
                    'teacher_name' => 'SRI NUR AROFAH, S.Pd',
                    'room' => 'LAB 16',
                ],
            ],
        ];

        $putaran2Data = [
            'type' => 'timetable_fet',
            'has_room_schedule' => true,
            'cycle_type' => 'Putaran 2',
            'classes' => ['10 RPL 1', '10 DKV 1'],
            'subjects' => ['KK 1 DKV Karya UI/UX', 'Bahasa Indonesia]'],
            'rooms' => ['L.16', '12'],
            'teachers' => [
                ['code' => '60', 'name' => 'SRI NUR ARAFAH, S.Pd.'],
                ['code' => '25', 'name' => 'UMI KUNTARI, S.S. M.Pd'],
            ],
            'schedules' => [
                [
                    'classroom' => '10 RPL 1',
                    'day_of_week' => 'Senin',
                    'start_time' => '08:00:00',
                    'end_time' => '15:10:00',
                    'cycle_type' => 'Putaran 2',
                    'subject' => 'KK 1 DKV Karya UI/UX',
                    'teacher_code' => '60',
                    'teacher_name' => 'SRI NUR ARAFAH, S.Pd.',
                    'room' => 'L.16',
                ],
                [
                    'classroom' => '10 DKV 1',
                    'day_of_week' => 'Senin',
                    'start_time' => '08:00:00',
                    'end_time' => '11:45:00',
                    'cycle_type' => 'Putaran 2',
                    'subject' => 'Bahasa Indonesia]',
                    'teacher_code' => '25',
                    'teacher_name' => 'UMI KUNTARI, S.S. M.Pd',
                    'room' => '12',
                ],
            ],
        ];

        $res1 = $service->import($putaran1Data, null, 'Putaran 1');
        $this->assertEquals(2, $res1['stats']['schedules_created']);
        $this->assertEquals(2, $res1['stats']['new_teachers_created']);

        // Import Putaran 2 (nearly identical master data with slight formatting/spelling variations)
        $res2 = $service->import($putaran2Data, null, 'Putaran 2');
        $this->assertEquals(2, $res2['stats']['schedules_created']);
        $this->assertEquals(0, $res2['stats']['new_teachers_created']);

        // Re-import Putaran 2 to verify idempotency (updates in-place, zero duplicate rows)
        $res2Re = $service->import($putaran2Data, null, 'Putaran 2');
        $this->assertEquals(0, $res2Re['stats']['schedules_created']);
        $this->assertEquals(2, $res2Re['stats']['schedules_updated']);
        $this->assertEquals(0, $res2Re['stats']['new_teachers_created']);

        // Verify zero redundancy in master tables and isolated schedules per cycle
        $this->assertDatabaseCount('classrooms', 2);
        $this->assertDatabaseCount('subjects', 2);
        $this->assertDatabaseCount('rooms', 3); // Ruang Teori + LAB 16 + Ruang 12
        $this->assertEquals(2, Schedule::where('cycle_type', 'Putaran 1')->count());
        $this->assertEquals(2, Schedule::where('cycle_type', 'Putaran 2')->count());
    }
}
