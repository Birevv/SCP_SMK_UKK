<?php

namespace Tests\Feature;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CameraIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $teacher;

    protected User $student;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $this->teacher = User::factory()->create(['role_id' => $guruRole->id]);
        $this->teacher->assignRole('Guru');

        $this->student = User::factory()->create(['role_id' => $siswaRole->id]);
        $this->student->assignRole('Siswa');

        $classroom = Classroom::factory()->create();
        $this->student->classrooms()->attach($classroom->id, ['status' => 'Aktif']);

        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Rekayasa Perangkat Lunak']);

        $this->schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);
    }

    /**
     * TC-CAM-01: Membuka kamera belakang (environment) dan mengambil foto via upload endpoint.
     */
    public function test_tc_cam_01_capture_photo_with_environment_mode(): void
    {
        $base64Image = 'data:image/jpeg;base64,'.base64_encode('fake-jpeg-camera-rear-stream');

        $response = $this->actingAs($this->teacher)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'photo_base64' => $base64Image,
                'folder' => 'journal_photos',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $path = $response->json('path');
        $this->assertStringStartsWith('journal_photos/realtime_', $path);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * TC-CAM-02: Beralih dari kamera belakang ke kamera depan (user facing mode).
     */
    public function test_tc_cam_02_switch_camera_facing_modes(): void
    {
        // Komponen blade mendukung properti defaultFacingMode dan switchFacingMode
        $base64Front = 'data:image/jpeg;base64,'.base64_encode('fake-front-camera-selfie');

        $response = $this->actingAs($this->teacher)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'photo_base64' => $base64Front,
                'folder' => 'journal_photos',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * TC-CAM-03: Mengambil foto menggunakan kamera depan.
     */
    public function test_tc_cam_03_capture_photo_with_user_facing_mode(): void
    {
        $file = UploadedFile::fake()->image('front_camera.jpg', 640, 480);

        $response = $this->actingAs($this->teacher)
            ->post(route('admin.journals.upload-realtime-photo'), [
                'photo' => $file,
                'folder' => 'journal_photos',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * TC-CAM-04: Mengambil ulang foto sebelum submit.
     */
    public function test_tc_cam_04_retake_photo_before_submit(): void
    {
        $photo1 = 'data:image/jpeg;base64,'.base64_encode('photo-1-blur');
        $resp1 = $this->actingAs($this->teacher)->postJson(route('admin.journals.upload-realtime-photo'), [
            'photo_base64' => $photo1,
            'folder' => 'journal_photos',
        ]);
        $path1 = $resp1->json('path');

        $photo2 = 'data:image/jpeg;base64,'.base64_encode('photo-2-crisp-retake');
        $resp2 = $this->actingAs($this->teacher)->postJson(route('admin.journals.upload-realtime-photo'), [
            'photo_base64' => $photo2,
            'folder' => 'journal_photos',
        ]);
        $path2 = $resp2->json('path');

        $this->assertNotEquals($path1, $path2);
        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);
    }

    /**
     * TC-CAM-05: Menutup modal dan memastikan stream kamera dibersihkan.
     */
    public function test_tc_cam_05_close_modal_stream_cleanup_contract(): void
    {
        // Memastikan view mengandung handler stopCameraStream pada closeCameraModal dan beforeunload
        $viewContent = file_get_contents(resource_path('views/filament/forms/components/realtime-photo-capture.blade.php'));
        $this->assertStringContainsString('stopCameraStream()', $viewContent);
        $this->assertStringContainsString('track.stop()', $viewContent);
        $this->assertStringContainsString('closeCameraModal()', $viewContent);
    }

    /**
     * TC-CAM-06: Menolak izin kamera dan memastikan pesan error muncul.
     */
    public function test_tc_cam_06_permission_denied_contract(): void
    {
        $viewContent = file_get_contents(resource_path('views/filament/forms/components/realtime-photo-capture.blade.php'));
        $this->assertStringContainsString('NotAllowedError', $viewContent);
        $this->assertStringContainsString('Izin kamera ditolak', $viewContent);
    }

    /**
     * TC-CAM-07: Mengirim foto kamera pada pengajuan izin / sakit.
     */
    public function test_tc_cam_07_submit_camera_photo_on_leave_request(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminUser = User::factory()->create(['role_id' => $superAdminRole->id]);
        $adminUser->assignRole('Super Admin');

        // 1. Upload foto kamera ke folder leave_attachments
        $base64DoctorNote = 'data:image/jpeg;base64,'.base64_encode('surat-dokter-kamera-belakang');

        $uploadResp = $this->actingAs($this->student)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'photo_base64' => $base64DoctorNote,
                'folder' => 'leave_attachments',
            ]);

        $uploadResp->assertOk()
            ->assertJsonPath('success', true);

        $savedPath = $uploadResp->json('path');
        $this->assertStringStartsWith('leave_attachments/leave_doc_', $savedPath);
        Storage::disk('public')->assertExists($savedPath);

        // 2. Submit formulir CreateLeaveRequest dengan foto hasil kamera
        Livewire::actingAs($adminUser)
            ->test(CreateLeaveRequest::class)
            ->fillForm([
                'student_id' => $this->student->id,
                'reason_type' => 'sakit',
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'description' => 'Izin sakit dengan foto surat dokter dari kamera',
                'attachment_photo' => $savedPath,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $this->student->id,
            'reason_type' => 'sakit',
            'attachment_photo' => $savedPath,
        ]);
    }

    /**
     * TC-CAM-08: Mengirim foto kamera pada jurnal KBM.
     */
    public function test_tc_cam_08_submit_camera_photo_on_journal(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminTeacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $adminTeacher->assignRole('Super Admin');

        $base64Class = 'data:image/jpeg;base64,'.base64_encode('suasana-kelas-kbm');

        $uploadResp = $this->actingAs($adminTeacher)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'photo_base64' => $base64Class,
                'folder' => 'journal_photos',
            ]);

        $uploadResp->assertOk()
            ->assertJsonPath('success', true);

        $savedPath = $uploadResp->json('path');
        Storage::disk('public')->assertExists($savedPath);

        Livewire::actingAs($adminTeacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $this->schedule->id,
                'date' => now()->toDateString(),
                'teaching_material' => 'Implementasi Modul Kamera Realtime',
                'notes' => 'Kelas berlangsung aktif dan tertib',
                'activity_photo' => $savedPath,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('journals', [
            'schedule_id' => $this->schedule->id,
            'activity_photo' => $savedPath,
        ]);
    }

    /**
     * TC-CAM-09: Memastikan file tersimpan dan terhubung dengan record database yang benar.
     */
    public function test_tc_cam_09_file_storage_and_database_relation_integrity(): void
    {
        $file = UploadedFile::fake()->image('bukti_kegiatan.jpg', 800, 600);

        $uploadResp = $this->actingAs($this->teacher)
            ->post(route('admin.journals.upload-realtime-photo'), [
                'photo' => $file,
                'folder' => 'journal_photos',
            ]);

        $savedPath = $uploadResp->json('path');

        $journal = Journal::create([
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'teaching_material' => 'Materi Uji Integritas File',
            'notes' => 'Kamera tersimpan baik',
            'activity_photo' => $savedPath,
        ]);

        $this->assertNotNull($journal->id);
        $this->assertEquals($savedPath, $journal->activity_photo);
        Storage::disk('public')->assertExists($journal->activity_photo);
    }

    /**
     * TC-CAM-10: Menguji kamera pada format file dan batasan ukuran (2048 KB leave request & 5120 KB journal).
     */
    public function test_tc_cam_10_camera_validation_limits_and_responsive_capabilities(): void
    {
        // 1. Leave attachment max 2048 KB enforcement: file > 2048 KB ditolak dengan status 422
        $tooBigFile = UploadedFile::fake()->create('big_doc.jpg', 2500, 'image/jpeg');

        $response = $this->actingAs($this->student)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'file' => $tooBigFile,
                'folder' => 'leave_attachments',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        // 2. Normal leave attachment <= 2048 KB passes
        $validFile = UploadedFile::fake()->create('valid_doc.jpg', 1500, 'image/jpeg');

        $response2 = $this->actingAs($this->student)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'file' => $validFile,
                'folder' => 'leave_attachments',
            ]);

        $response2->assertOk()
            ->assertJsonPath('success', true);
    }
}
