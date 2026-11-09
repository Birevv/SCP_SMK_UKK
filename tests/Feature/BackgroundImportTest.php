<?php

namespace Tests\Feature;

use App\Jobs\ImportSchedulePairJob;
use App\Jobs\ImportSingleScheduleFileJob;
use App\Jobs\ImportSingleUserFileJob;
use App\Jobs\ImportSmartFileJob;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Support\ImportHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BackgroundImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        AcademicYear::firstOrCreate([
            'name' => '2026/2027',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
    }

    public function test_import_helper_dispatches_properly_with_sync_queue(): void
    {
        Config::set('queue.default', 'sync');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_job_').'.csv';
        file_put_contents($tmpFile, "guru,nip,mata_pelajaran,kelas,ruangan,hari,jam_mulai,jam_selesai,putaran\n");

        $job = new ImportSingleScheduleFileJob($tmpFile, $this->admin);

        ImportHelper::dispatch($job);

        $this->assertFileDoesNotExist($tmpFile);
    }

    public function test_import_helper_executes_synchronously_regardless_of_queue_setting(): void
    {
        Config::set('queue.default', 'database');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_job_').'.csv';
        file_put_contents($tmpFile, "guru,nip,mata_pelajaran,kelas,ruangan,hari,jam_mulai,jam_selesai,putaran\n");

        $job = new ImportSingleScheduleFileJob($tmpFile, $this->admin);
        ImportHelper::dispatch($job);

        // Job executed immediately: file is processed and cleaned up
        $this->assertFileDoesNotExist($tmpFile);
    }

    public function test_single_schedule_file_job_imports_and_notifies(): void
    {
        $csvContent = "guru,nip,mata_pelajaran,kelas,ruangan,hari,jam_mulai,jam_selesai,putaran\n"
            ."Guru Background Test,198501012010011009,Fisika Terapan,X TKJ 1,Lab Komputer 2,Senin,07:30,09:30,Putaran 1\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_schedule_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        $job = new ImportSingleScheduleFileJob($tmpFile, $this->admin);
        app()->call([$job, 'handle']);

        // Check file was cleaned up
        $this->assertFileDoesNotExist($tmpFile);

        // Check schedule was imported
        $this->assertDatabaseHas('schedules', [
            'day_of_week' => 'Senin',
            'start_time' => '07:30',
        ]);

        // Check database notification was stored for the admin user
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->admin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_single_user_file_job_imports_and_notifies(): void
    {
        $csvContent = "nama_lengkap,nisn,role,email,status\n"
            ."Siswa Background Test,9988776655,Siswa,siswatest@smk.sch.id,Aktif\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_user_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        $job = new ImportSingleUserFileJob($tmpFile, $this->admin);
        app()->call([$job, 'handle']);

        // Check file was cleaned up
        $this->assertFileDoesNotExist($tmpFile);

        // Check user was imported
        $this->assertDatabaseHas('users', [
            'identity_number' => '9988776655',
            'full_name' => 'Siswa Background Test',
        ]);

        // Check database notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->admin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_import_schedule_pair_job_handles_missing_files_gracefully(): void
    {
        $job = new ImportSchedulePairJob('non_existent_1.pdf', 'non_existent_2.pdf', $this->admin, 'Putaran 1');
        app()->call([$job, 'handle']);

        // Notification error sent to user
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->admin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_import_smart_file_job_handles_missing_file(): void
    {
        $job = new ImportSmartFileJob('non_existent.xlsx', $this->admin);
        app()->call([$job, 'handle']);

        // Should return cleanly without throwing
        $this->assertTrue(true);
    }
}
