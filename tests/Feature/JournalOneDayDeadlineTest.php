<?php

namespace Tests\Feature;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Widgets\HomeroomScheduleWidget;
use App\Filament\Widgets\TeacherScheduleWidget;
use App\Filament\Widgets\WakaScheduleWidget;
use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Major;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolSession;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JournalOneDayDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected User $wakaUser;

    protected User $teacher;

    protected AcademicYear $academicYear;

    protected Classroom $classroom;

    protected Subject $subject;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);

        foreach (['ViewAny:Journal', 'View:Journal', 'Create:Journal'] as $permName) {
            $p = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $guruRole->givePermissionTo($p);
        }

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $this->wakaUser = User::factory()->create([
            'role_id' => Role::where('name', 'Waka Kurikulum')->first()->id,
        ]);
        $this->wakaUser->assignRole('Waka Kurikulum');

        $this->teacher = User::factory()->create([
            'role_id' => $guruRole->id,
            'full_name' => 'Budi Santoso, S.Kom',
        ]);
        $this->teacher->assignRole('Guru');

        $level = ClassLevel::create(['level' => 12, 'name' => 'XII']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);

        $this->classroom = Classroom::create([
            'name' => 'XII RPL 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->subject = Subject::create([
            'name' => 'Pemrograman Web Lanjut',
            'code' => 'PWL',
        ]);

        $this->room = Room::create([
            'name' => 'Lab Komputer 1',
        ]);

        SchoolSession::seedDefaults();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_schedule_status_is_belum_diisi_within_one_day(): void
    {
        // Set waktu pengujian: Senin 10:00
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00')); // Monday

        // Jadwal selesai pada 09:15 hari ini (baru lewat 45 menit, masih dalam batas 1 hari)
        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($this->wakaUser);

        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDate', '2026-11-09')
            ->set('selectedDay', 'Senin')
            ->assertSee('Belum diisi')
            ->assertDontSee('Tidak mengisi jurnal')
            ->set('selectedStatus', 'unfilled')
            ->assertSee('Pemrograman Web Lanjut')
            ->set('selectedStatus', 'not_filled')
            ->assertDontSee('Pemrograman Web Lanjut');

        Carbon::setTestNow();
    }

    public function test_schedule_status_becomes_tidak_mengisi_jurnal_after_one_day(): void
    {
        // Set waktu pengujian: Rabu 10:00 (sudah lewat > 24 jam dari jadwal Senin 09:15)
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00')); // Wednesday

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($this->wakaUser);

        // Jika melihat jadwal Senin tanggal 2026-11-09
        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDate', '2026-11-09')
            ->set('selectedDay', 'Senin')
            ->assertSee('Tidak mengisi jurnal')
            ->assertDontSee('Terlambat >45m')
            ->set('selectedStatus', 'not_filled')
            ->assertSee('Pemrograman Web Lanjut')
            ->set('selectedStatus', 'unfilled')
            ->assertDontSee('Pemrograman Web Lanjut');

        Carbon::setTestNow();
    }

    public function test_schedule_status_is_sudah_diisi_when_journal_exists(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-09 12:00:00'));

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-09',
            'teaching_material' => 'Materi Pemrograman Web Selesai',
        ]);

        $this->actingAs($this->wakaUser);

        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDate', '2026-11-09')
            ->set('selectedDay', 'Senin')
            ->assertSee('Sudah diisi')
            ->assertDontSee('Belum diisi')
            ->assertDontSee('Tidak mengisi jurnal')
            ->set('selectedStatus', 'filled')
            ->assertSee('Pemrograman Web Lanjut')
            ->set('selectedStatus', 'not_filled')
            ->assertDontSee('Pemrograman Web Lanjut');

        Carbon::setTestNow();
    }

    public function test_teacher_and_homeroom_widgets_show_correct_status(): void
    {
        // Set waktu pengujian: Rabu 10:00
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        // Jadwal Senin (sudah > 1 hari)
        $scheduleExpired = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Jadwal Rabu hari ini (masih dalam 1 hari)
        $scheduleToday = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Rabu',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($this->teacher);

        // Untuk hari ini (Rabu) -> Belum Diisi
        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Rabu')
            ->assertSee('Belum Diisi')
            ->assertDontSee('Tidak mengisi jurnal');

        // Untuk hari Senin (sudah > 1 hari) -> Tidak Mengisi Jurnal
        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->assertSee('Tidak Mengisi Jurnal');

        // Homeroom widget untuk Wali Kelas
        $waliKelasRole = Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        $waliKelas = User::factory()->create(['role_id' => $waliKelasRole->id]);
        $waliKelas->assignRole('Wali Kelas');
        $waliKelas->classrooms()->attach($this->classroom->id);
        $this->classroom->update(['homeroom_teacher_id' => $waliKelas->id]);

        $this->actingAs($waliKelas);

        Livewire::test(HomeroomScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->assertSee('Tidak Mengisi Jurnal');

        Carbon::setTestNow();
    }

    public function test_teacher_schedule_widget_on_weekend_shows_upcoming_monday_as_belum_diisi(): void
    {
        // Simulasi hari Minggu 8 November 2026 (seperti screenshot user)
        Carbon::setTestNow(Carbon::parse('2026-11-08 17:00:00')); // Sunday

        // Jadwal hari Senin esok (9 November 2026)
        Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($this->teacher);

        // Pada hari Minggu, jadwal Senin esok berstatus "Belum Diisi" dengan tombol nonaktif dan keterangan tanggal
        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->assertSee('Belum Diisi')
            ->assertDontSee('Tidak Mengisi Jurnal')
            ->assertSee('Diisi pada 09 Nov 2026')
            ->assertDontSee('Isi Jurnal');

        // Ketika hari Senin esok telah tiba, tombol "Isi Jurnal" aktif kembali
        Carbon::setTestNow(Carbon::parse('2026-11-09 08:00:00'));

        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->assertSee('Belum Diisi')
            ->assertSee('Isi Jurnal')
            ->assertDontSee('Diisi pada 09 Nov 2026');

        Carbon::setTestNow();
    }

    public function test_waka_schedule_widget_clamps_date_to_academic_year_and_filters(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

        $this->actingAs($this->wakaUser);

        // Uji jika user memilih tanggal sebelum tahun ajaran (misal 2017 seperti screenshot user)
        $component = Livewire::test(WakaScheduleWidget::class)
            ->set('selectedAcademicYearId', $this->academicYear->id)
            ->set('selectedDate', '2017-09-01');

        // Tanggal harus otomatis di-clamp ke batas awal tahun ajaran (2026-07-01)
        $this->assertEquals('2026-07-01', $component->get('selectedDate'));

        Carbon::setTestNow();
    }

    public function test_teacher_cannot_create_journal_for_past_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Livewire::actingAs($this->teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => '2026-11-01', // tanggal lampau
                'teaching_material' => 'Materi Lampau',
            ])
            ->call('create')
            ->assertHasFormErrors(['date']);

        $this->assertDatabaseMissing('journals', [
            'schedule_id' => $schedule->id,
            'date' => '2026-11-01',
        ]);

        Carbon::setTestNow();
    }

    public function test_create_journal_hydrates_date_from_query_parameters(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-08 17:00:00')); // Sunday

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Livewire::actingAs($this->teacher)
            ->withQueryParams([
                'schedule_id' => $schedule->id,
                'date' => '2026-11-09',
            ])
            ->test(CreateJournal::class)
            ->assertFormSet([
                'schedule_id' => $schedule->id,
                'date' => '2026-11-09',
            ]);

        Carbon::setTestNow();
    }

    public function test_teacher_cannot_create_journal_for_future_date_tomorrow(): void
    {
        // Hari Minggu 8 November 2026
        Carbon::setTestNow(Carbon::parse('2026-11-08 17:00:00'));

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Guru mencoba mengisi jurnal untuk besok (Senin 9 November 2026)
        Livewire::actingAs($this->teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => '2026-11-09',
                'teaching_material' => 'Materi Besok Belum Mulai',
            ])
            ->call('create')
            ->assertHasFormErrors(['date']);

        $this->assertDatabaseMissing('journals', [
            'schedule_id' => $schedule->id,
            'date' => '2026-11-09',
        ]);

        Carbon::setTestNow();
    }

    public function test_teacher_can_create_journal_for_today(): void
    {
        // Hari Senin 9 November 2026
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Livewire::actingAs($this->teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => '2026-11-09',
                'teaching_material' => 'Materi Pembelajaran Hari Ini',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $journal = Journal::where('schedule_id', $schedule->id)->latest()->first();
        $this->assertNotNull($journal);
        $this->assertEquals('2026-11-09', Carbon::parse($journal->date)->toDateString());
        $this->assertEquals('Materi Pembelajaran Hari Ini', $journal->teaching_material);

        Carbon::setTestNow();
    }

    public function test_super_admin_can_create_journal_for_future_date(): void
    {
        // Hari Minggu 8 November 2026
        Carbon::setTestNow(Carbon::parse('2026-11-08 17:00:00'));

        $superAdmin = User::factory()->create([
            'role_id' => Role::where('name', 'Super Admin')->first()->id,
        ]);
        $superAdmin->assignRole('Super Admin');

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Livewire::actingAs($superAdmin)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => '2026-11-09',
                'teaching_material' => 'Super Admin Bypass Date Restriction',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $journal = Journal::where('schedule_id', $schedule->id)->latest()->first();
        $this->assertNotNull($journal);
        $this->assertEquals('2026-11-09', Carbon::parse($journal->date)->toDateString());
        $this->assertEquals('Super Admin Bypass Date Restriction', $journal->teaching_material);

        Carbon::setTestNow();
    }
}
