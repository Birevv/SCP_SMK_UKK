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

    public function test_journal_form_has_realtime_photo_file_upload(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');

        Livewire::actingAs($teacher)
            ->test(CreateJournal::class)
            ->assertFormFieldExists('activity_photo');
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

        $photoFile = UploadedFile::fake()->image('classroom_activity.jpg', 800, 600);

        Livewire::actingAs($teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => now()->toDateString(),
                'teaching_material' => 'Materi Pembelajaran Realtime',
                'notes' => 'Catatan kelas lancar',
                'activity_photo' => $photoFile,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $journal = Journal::where('schedule_id', $schedule->id)->first();
        $this->assertNotNull($journal);
        $this->assertNotNull($journal->activity_photo);
        Storage::disk('public')->assertExists($journal->activity_photo);
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

        $newPhoto = UploadedFile::fake()->image('updated_activity.jpg', 800, 600);

        $component = Livewire::actingAs($teacher)
            ->test(EditJournal::class, [
                'record' => $journal->getKey(),
            ])
            ->assertSchemaStateSet([
                'activity_photo' => $initialPhoto,
            ]);

        $component->set('data.activity_photo', []);
        $component->fillForm([
            'activity_photo' => $newPhoto,
        ]);
        $component->call('save');
        $component->assertHasNoFormErrors();

        $journal->refresh();
        $this->assertNotNull($journal->activity_photo);
        $this->assertNotEquals($initialPhoto, $journal->activity_photo);
        Storage::disk('public')->assertExists($journal->activity_photo);
    }

    public function test_can_upload_realtime_photo_via_base64_endpoint(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');

        $base64Image = 'data:image/jpeg;base64,'.base64_encode('fake-image-bytes');

        $response = $this->actingAs($teacher)
            ->postJson(route('admin.journals.upload-realtime-photo'), [
                'photo_base64' => $base64Image,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $path = $response->json('path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_can_upload_realtime_photo_via_file_endpoint(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');

        $file = UploadedFile::fake()->image('camera_snap.jpg', 640, 480);

        $response = $this->actingAs($teacher)
            ->post(route('admin.journals.upload-realtime-photo'), [
                'photo' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $path = $response->json('path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }
}
