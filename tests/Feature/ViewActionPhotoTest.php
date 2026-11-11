<?php

namespace Tests\Feature;

use App\Enums\ReasonTypeEnum;
use App\Filament\Resources\Journals\Pages\ListJournals;
use App\Filament\Resources\Journals\Schemas\JournalForm;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
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
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ViewActionPhotoTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected User $admin;
    protected User $student;
    protected User $teacher;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Storage::fake('public');

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        $teacherRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $this->admin->assignRole('Super Admin');

        $this->student = User::factory()->create(['role_id' => $studentRole->id]);
        $this->student->assignRole('Siswa');

        $this->teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $this->teacher->assignRole('Guru');

        $classroom = Classroom::factory()->create();
        $this->student->classrooms()->attach($classroom->id, ['status' => 'Aktif']);

        $academicYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Pemrograman Web']);
        $room = Room::factory()->create();

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

    public function test_leave_request_form_renders_photo_attachment_correctly(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => 'Sakit',
            'description' => 'Sakit demam tinggi',
            'status' => 'Pending',
            'attachment_photo' => 'leave_attachments/surat_dokter_bima.jpg',
        ]);

        $livewire = Livewire::new(ListLeaveRequests::class);
        $schema = Schema::make($livewire);
        $schema->record($leaveRequest);
        LeaveRequestForm::configure($schema);
        $schema->fill($leaveRequest->toArray());

        $photoField = collect($schema->getComponents(withHidden: true))->first(function ($c) {
            return method_exists($c, 'getName') && $c->getName() === 'attachment_photo';
        });

        $this->assertNotNull($photoField);
        $this->assertEquals('leave_attachments/surat_dokter_bima.jpg', $photoField->getState());

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $renderedHtml = $photoField->render()->render();

        // Must contain the photo path in src attribute for instant SSR display
        $this->assertStringContainsString('leave_attachments/surat_dokter_bima.jpg', $renderedHtml);
        $this->assertStringContainsString('Lampiran Bukti Perizinan', $renderedHtml);
        $this->assertStringContainsString('Lihat Foto Ukuran Penuh', $renderedHtml);

        $reasonField = collect($schema->getComponents(withHidden: true))->first(function ($c) {
            return method_exists($c, 'getName') && $c->getName() === 'reason_type';
        });
        $this->assertNotNull($reasonField);
        $this->assertArrayHasKey('sakit', $reasonField->getOptions());
        $this->assertEquals('Sakit', $reasonField->getOptions()['sakit']);
    }

    public function test_journal_form_renders_activity_photo_correctly(): void
    {
        $journal = Journal::create([
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->teacher->id,
            'date' => now()->toDateString(),
            'teaching_material' => 'Materi Laravel Filament',
            'notes' => 'Kelas berlangsung kondusif',
            'activity_photo' => 'journal_photos/kbm_kelas_rpl.jpg',
        ]);

        $livewire = Livewire::new(ListJournals::class);
        $schema = Schema::make($livewire);
        $schema->record($journal);
        JournalForm::configure($schema);
        $schema->fill($journal->toArray());

        $photoField = collect($schema->getComponents(withHidden: true))->first(function ($c) {
            return method_exists($c, 'getName') && $c->getName() === 'activity_photo';
        });

        $this->assertNotNull($photoField);
        $this->assertEquals('journal_photos/kbm_kelas_rpl.jpg', $photoField->getState());

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $renderedHtml = $photoField->render()->render();

        $this->assertStringContainsString('journal_photos/kbm_kelas_rpl.jpg', $renderedHtml);
        $this->assertStringContainsString('Bukti Foto Kegiatan Pembelajaran', $renderedHtml);
        $this->assertStringContainsString('Lihat Foto Ukuran Penuh', $renderedHtml);
    }

    public function test_leave_request_form_supports_pdf_attachment_preview(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => 'Izin',
            'description' => 'Izin dispensasi lomba',
            'status' => 'Pending',
            'attachment_photo' => 'leave_attachments/surat_dispensasi.pdf',
        ]);

        $livewire = Livewire::new(ListLeaveRequests::class);
        $schema = Schema::make($livewire);
        $schema->record($leaveRequest);
        LeaveRequestForm::configure($schema);
        $schema->fill($leaveRequest->toArray());

        $photoField = collect($schema->getComponents(withHidden: true))->first(function ($c) {
            return method_exists($c, 'getName') && $c->getName() === 'attachment_photo';
        });

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $renderedHtml = $photoField->render()->render();

        $this->assertStringContainsString('Berkas Dokumen Lampiran (PDF)', $renderedHtml);
        $this->assertStringContainsString('surat_dispensasi.pdf', $renderedHtml);
        $this->assertStringContainsString('Buka / Unduh Dokumen PDF', $renderedHtml);
    }
}
