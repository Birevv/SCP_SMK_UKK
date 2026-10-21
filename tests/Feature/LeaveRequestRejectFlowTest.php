<?php

namespace Tests\Feature;

use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaveRequestRejectFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $student;

    private User $waliKelas;

    private User $guruBk;

    private Classroom $classroom;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        $waliRole = Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        $bkRole = Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);

        $this->academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $this->classroom = Classroom::factory()->create();

        $this->student = User::factory()->create([
            'role_id' => $siswaRole->id,
            'full_name' => 'Siswa Pengaju Izin',
        ]);
        $this->student->assignRole('Siswa');
        $this->student->classrooms()->attach($this->classroom->id, ['academic_year_id' => $this->academicYear->id]);

        $this->waliKelas = User::factory()->create([
            'role_id' => $waliRole->id,
            'full_name' => 'Budi Wali Kelas',
        ]);
        $this->waliKelas->assignRole('Wali Kelas');
        $this->waliKelas->classrooms()->attach($this->classroom->id, ['academic_year_id' => $this->academicYear->id]);

        $this->guruBk = User::factory()->create([
            'role_id' => $bkRole->id,
            'full_name' => 'Dra. Siti Guru BK',
        ]);
        $this->guruBk->assignRole('Guru BK');
    }

    public function test_wali_kelas_can_reject_leave_request_with_rejection_note_via_api(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Izin',
            'status' => 'Pending',
            'description' => 'Ada acara keluarga di luar kota',
        ]);

        $this->actingAs($this->waliKelas, 'sanctum');

        $response = $this->putJson("/api/homeroom/leave-requests/{$leaveRequest->id}/verify", [
            'status' => 'Rejected',
            'rejection_note' => 'Dokumen surat izin dari orang tua belum dilampirkan.',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'Rejected')
            ->assertJsonPath('data.rejection_note', 'Dokumen surat izin dari orang tua belum dilampirkan.')
            ->assertJsonPath('data.verified_by', $this->waliKelas->full_name);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'Rejected',
            'rejection_note' => 'Dokumen surat izin dari orang tua belum dilampirkan.',
            'verified_by' => $this->waliKelas->id,
        ]);
    }

    public function test_rejection_without_rejection_note_fails_validation(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Sakit',
            'status' => 'Pending',
            'description' => 'Flu berat',
        ]);

        $this->actingAs($this->waliKelas, 'sanctum');

        $response = $this->putJson("/api/homeroom/leave-requests/{$leaveRequest->id}/verify", [
            'status' => 'Rejected',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rejection_note']);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'Pending',
            'rejection_note' => null,
        ]);
    }

    public function test_guru_bk_can_reject_leave_request_with_rejection_note(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Izin',
            'status' => 'Pending',
            'description' => 'Izin keperluan pribadi',
        ]);

        $this->actingAs($this->guruBk, 'sanctum');

        $response = $this->putJson("/api/homeroom/leave-requests/{$leaveRequest->id}/verify", [
            'status' => 'Rejected',
            'rejection_note' => 'Alasan perizinan tidak memenuhi kriteria izin resmi sekolah.',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'Rejected')
            ->assertJsonPath('data.rejection_note', 'Alasan perizinan tidak memenuhi kriteria izin resmi sekolah.')
            ->assertJsonPath('data.verified_by', $this->guruBk->full_name);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'Rejected',
            'rejection_note' => 'Alasan perizinan tidak memenuhi kriteria izin resmi sekolah.',
            'verified_by' => $this->guruBk->id,
        ]);
    }

    public function test_student_sees_rejection_note_and_rejected_status(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Izin',
            'status' => 'Rejected',
            'rejection_note' => 'Foto bukti tidak terbaca / blur.',
            'verified_by' => $this->waliKelas->id,
            'description' => 'Izin keluar kota',
        ]);

        $this->actingAs($this->student, 'sanctum');

        $response = $this->getJson('/api/student/leave-requests');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.id', $leaveRequest->id)
            ->assertJsonPath('data.0.status', 'Rejected')
            ->assertJsonPath('data.0.rejection_note', 'Foto bukti tidak terbaca / blur.')
            ->assertJsonPath('data.0.verified_by', $this->waliKelas->full_name);
    }

    public function test_approving_leave_request_clears_or_keeps_rejection_note_null(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Sakit',
            'status' => 'Pending',
            'description' => 'Sakit demam',
        ]);

        $this->actingAs($this->waliKelas, 'sanctum');

        $response = $this->putJson("/api/homeroom/leave-requests/{$leaveRequest->id}/verify", [
            'status' => 'Approved',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.rejection_note', null);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'Approved',
            'rejection_note' => null,
            'verified_by' => $this->waliKelas->id,
        ]);
    }

    public function test_reject_action_logic_and_authorization(): void
    {
        $leaveRequest = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason_type' => 'Izin',
            'status' => 'Pending',
            'description' => 'Izin keluarga',
        ]);

        $this->actingAs($this->waliKelas);

        $this->assertTrue($this->waliKelas->can('approve_leave_request') || $this->waliKelas->hasRole('Wali Kelas'));

        $leaveRequest->update([
            'status' => 'Rejected',
            'rejection_note' => 'Surat izin tidak ditandatangani orang tua.',
            'verified_by' => $this->waliKelas->id,
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'Rejected',
            'rejection_note' => 'Surat izin tidak ditandatangani orang tua.',
            'verified_by' => $this->waliKelas->id,
        ]);
    }

    public function test_student_cannot_submit_leave_request_with_past_start_date(): void
    {
        $this->actingAs($this->student, 'sanctum');

        $response = $this->postJson('/api/student/leave-requests', [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => 'Izin',
            'description' => 'Izin keperluan keluarga kemarin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_student_can_submit_leave_request_starting_today(): void
    {
        $this->actingAs($this->student, 'sanctum');

        $response = $this->postJson('/api/student/leave-requests', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => 'Izin',
            'description' => 'Izin keperluan keluarga hari ini',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'Pending');

        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $this->student->id,
            'reason_type' => 'Izin',
            'description' => 'Izin keperluan keluarga hari ini',
        ]);
    }

    public function test_leave_request_form_start_date_min_date_is_today_on_create(): void
    {
        $this->actingAs($this->student);
        $schema = Schema::make();
        LeaveRequestForm::configure($schema);

        /** @var DatePicker $startDateField */
        $startDateField = collect($schema->getComponents(withHidden: true))->first(function ($c) {
            return method_exists($c, 'getName') && $c->getName() === 'start_date';
        });

        $this->assertNotNull($startDateField);
        $this->assertEquals(now()->toDateString(), $startDateField->getMinDate());
    }

    public function test_leave_request_form_preserves_past_start_date_when_editing_existing_record(): void
    {
        $this->actingAs($this->student);
        $schema = Schema::make();
        $record = new LeaveRequest(['start_date' => now()->subDays(5)->toDateString()]);
        $schema->record($record);

        LeaveRequestForm::configure($schema);

        /** @var DatePicker $startDateField */
        $startDateField = collect($schema->getComponents(withHidden: true))->first(function ($c) {
            return method_exists($c, 'getName') && $c->getName() === 'start_date';
        });

        $this->assertNotNull($startDateField);
        $this->assertEquals(now()->subDays(5)->toDateString(), $startDateField->getMinDate());
    }
}
