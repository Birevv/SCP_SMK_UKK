<?php

namespace Tests\Feature;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\Journals\Pages\EditJournal;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Journal;
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

class RealtimePhotoCaptureTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');
    }

    public function test_guest_cannot_upload_realtime_photo(): void
    {
        $response = $this->postJson(route('admin.journals.upload-realtime-photo'), [
            'photo' => UploadedFile::fake()->image('classroom.jpg'),
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_teacher_can_upload_multipart_realtime_photo(): void
    {
        $guruRole = Role::create(['name' => 'Guru']);
        $teacher = User::factory()->create(['role_id' => $guruRole->id]);

        $file = UploadedFile::fake()->image('live_classroom.jpg', 800, 600);

        $response = $this->actingAs($teacher)->postJson(route('admin.journals.upload-realtime-photo'), [
            'photo' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'path',
                'url',
                'filename',
                'captured_at',
                'message',
            ]);

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    public function test_authenticated_teacher_can_upload_base64_snapshot_realtime_photo(): void
    {
        $guruRole = Role::create(['name' => 'Guru']);
        $teacher = User::factory()->create(['role_id' => $guruRole->id]);

        // 1x1 transparent PNG base64
        $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($teacher)->postJson(route('admin.journals.upload-realtime-photo'), [
            'photo_base64' => $base64Png,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $path = $response->json('path');
        $this->assertStringStartsWith('journal_photos/realtime_', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_upload_fails_when_no_payload_provided(): void
    {
        $guruRole = Role::create(['name' => 'Guru']);
        $teacher = User::factory()->create(['role_id' => $guruRole->id]);

        $response = $this->actingAs($teacher)->postJson(route('admin.journals.upload-realtime-photo'), []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_upload_fails_with_invalid_base64_data(): void
    {
        $guruRole = Role::create(['name' => 'Guru']);
        $teacher = User::factory()->create(['role_id' => $guruRole->id]);

        $response = $this->actingAs($teacher)->postJson(route('admin.journals.upload-realtime-photo'), [
            'photo_base64' => 'not-a-valid-base64-image-string',
        ]);

        $response->assertStatus(422);
    }

    public function test_teacher_can_create_journal_with_realtime_photo(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');

        $classroom = Classroom::factory()->create();
        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Pemrograman Web']);

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $realtimePhotoPath = 'journal_photos/realtime_20261104_073000_snapshot.jpg';
        Storage::disk('public')->put($realtimePhotoPath, 'fake-image-content');

        Livewire::actingAs($teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => now()->toDateString(),
                'teaching_material' => 'Implementasi Realtime Photo Camera',
                'notes' => 'Siswa antusias dan hadir tepat waktu',
                'activity_photo' => $realtimePhotoPath,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('journals', [
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'activity_photo' => $realtimePhotoPath,
            'teaching_material' => 'Implementasi Realtime Photo Camera',
        ]);
    }

    public function test_teacher_can_edit_journal_and_update_realtime_photo(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');

        $classroom = Classroom::factory()->create();
        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Pemrograman Web']);

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $initialPhoto = 'journal_photos/realtime_initial.jpg';
        Storage::disk('public')->put($initialPhoto, 'initial-content');

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'teaching_material' => 'Materi Awal',
            'notes' => 'Catatan Awal',
            'activity_photo' => $initialPhoto,
        ]);

        $updatedPhoto = 'journal_photos/realtime_updated.jpg';
        Storage::disk('public')->put($updatedPhoto, 'updated-content');

        Livewire::actingAs($teacher)
            ->test(EditJournal::class, [
                'record' => $journal->getKey(),
            ])
            ->assertSchemaStateSet([
                'activity_photo' => $initialPhoto,
            ])
            ->fillForm([
                'activity_photo' => $updatedPhoto,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'activity_photo' => $updatedPhoto,
        ]);
    }
}
