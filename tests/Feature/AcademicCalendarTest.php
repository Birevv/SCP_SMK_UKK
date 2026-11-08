<?php

namespace Tests\Feature;

use App\Enums\AcademicEventCategory;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Resources\AcademicYears\Pages\ListAcademicYears;
use App\Filament\Widgets\AcademicCalendarWidget;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Saade\FilamentFullCalendar\Data\FetchInfo;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcademicCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
    }

    public function test_academic_calendar_widget_renders_and_fetches_events(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Genap',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        AcademicEvent::create([
            'academic_year_id' => $academicYear->id,
            'title' => 'Uji Kompetensi Keahlian',
            'category' => AcademicEventCategory::UKK,
            'target_grades' => ['XII'],
            'is_holiday' => false,
            'disable_kbm' => true,
            'requires_attendance' => true,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-05',
            'is_all_day' => true,
        ]);

        $test = Livewire::test(AcademicCalendarWidget::class, [
            'selectedYearId' => $academicYear->id,
        ]);
        $test->assertSuccessful();

        // Test fetching events
        $widget = app(AcademicCalendarWidget::class);
        $widget->selectedYearId = $academicYear->id;

        $start = CarbonImmutable::parse('2026-01-01');
        $end = CarbonImmutable::parse('2026-06-30');
        $fetchInfo = new FetchInfo(
            start: $start,
            end: $end,
            timezone: 'Asia/Jakarta',
            raw: ['start' => $start->toISOString(), 'end' => $end->toISOString(), 'timezone' => 'Asia/Jakarta']
        );

        $events = $widget->fetchEvents($fetchInfo);
        $this->assertIsArray($events);
        $this->assertCount(1, $events);
        $this->assertStringContainsString('Uji Kompetensi Keahlian', $events[0]['title']);
        $this->assertStringContainsString('[XII]', $events[0]['title']);
    }

    public function test_academic_calendar_widget_create_action(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Genap',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        Livewire::test(AcademicCalendarWidget::class, [
            'selectedYearId' => $academicYear->id,
        ])
            ->callAction('create', [
                'title' => 'Praktik Kerja Lapangan (PKL)',
                'category' => 'pkl',
                'target_grades' => ['XI', 'XII'],
                'is_holiday' => false,
                'disable_kbm' => true,
                'requires_attendance' => true,
                'start_date' => '2026-02-01',
                'end_date' => '2026-04-30',
                'is_all_day' => true,
                'academic_year_id' => $academicYear->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('academic_events', [
            'title' => 'Praktik Kerja Lapangan (PKL)',
            'category' => 'pkl',
            'academic_year_id' => $academicYear->id,
        ]);
    }

    public function test_list_academic_years_page_renders_fullcalendar_and_switches_tabs(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $page = Livewire::test(ListAcademicYears::class);
        $page->assertSuccessful();
        $page->assertSee('Kalender Akademik');
        $page->assertSee('Data Tahun Ajaran');

        // Test tab switching
        $page->call('switchTab', 'years');
        $page->assertSet('currentViewTab', 'years');

        $page->call('switchTab', 'calendar');
        $page->assertSet('currentViewTab', 'calendar');
    }

    public function test_list_academic_years_zero_scroll_filters_and_navigation(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Genap',
            'is_active' => true,
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
        ]);

        $event = AcademicEvent::create([
            'academic_year_id' => $academicYear->id,
            'title' => 'Praktik Kerja Lapangan (PKL) Gelombang 1',
            'category' => AcademicEventCategory::PKL,
            'target_grades' => ['XI', 'XII'],
            'is_holiday' => false,
            'disable_kbm' => true,
            'requires_attendance' => true,
            'start_date' => '2027-01-10',
            'end_date' => '2027-01-15',
            'is_all_day' => true,
        ]);

        $page = Livewire::test(ListAcademicYears::class)
            ->set('selectedYearId', $academicYear->id)
            ->set('currentMonth', 1)
            ->set('currentYear', 2027);

        $page->assertSuccessful();
        $page->assertSee('Praktik Kerja Lapangan (PKL) Gelombang 1');
        $page->assertSee('Tarik Libur Nasional');
        $page->assertSee('Tambah Agenda');

        // Test category filter toggle
        $page->call('toggleCategoryFilter', 'pkl');
        $this->assertEquals(['pkl'], $page->get('selectedCategories'));
        $page->call('toggleCategoryFilter', 'pkl');
        $this->assertEquals([], $page->get('selectedCategories'));

        // Test Quick Search
        $page->set('searchQuery', 'Gelombang 1');
        $page->assertSee('Praktik Kerja Lapangan (PKL) Gelombang 1');
        $page->set('searchQuery', 'Judul Tidak Ditemukan');
        $page->assertDontSee('Praktik Kerja Lapangan (PKL) Gelombang 1');
        $page->set('searchQuery', '');

        // Test out of period detection
        $page->set('currentMonth', 11);
        $page->set('currentYear', 2026);
        $this->assertTrue($page->instance()->isOutOfPeriod);

        $page->call('resetToActivePeriod');
        $this->assertEquals(1, $page->get('currentMonth'));
        $this->assertEquals(2027, $page->get('currentYear'));
        $this->assertFalse($page->instance()->isOutOfPeriod);

        // Test range selection via openCreateEventModal (forward and backward swipe)
        $page->call('openCreateEventModal', '2027-01-10', '2027-01-15');
        $this->assertEquals('2027-01-10', $page->get('eventStartDate'));
        $this->assertEquals('2027-01-15', $page->get('eventEndDate'));
        $page->call('closeEventModal');

        // Test backward swipe (e.g. from 15th back to 10th)
        $page->call('openCreateEventModal', '2027-01-15', '2027-01-10');
        $this->assertEquals('2027-01-10', $page->get('eventStartDate'));
        $this->assertEquals('2027-01-15', $page->get('eventEndDate'));
        $page->call('closeEventModal');

        // Test saveEvent via modal
        $page->call('openCreateEventModal', '2027-01-20');
        $this->assertTrue($page->get('showEventModal'));
        $page->set('eventTitle', 'Ujian Tengah Semester');
        $page->set('eventCategory', 'ujian');
        $page->set('eventStartDate', '2027-01-20');
        $page->set('eventEndDate', '2027-01-22');
        $page->call('saveEvent');
        $this->assertFalse($page->get('showEventModal'));

        $this->assertDatabaseHas('academic_events', [
            'title' => 'Ujian Tengah Semester',
            'category' => 'ujian',
            'academic_year_id' => $academicYear->id,
        ]);

        $createdEvent = AcademicEvent::where('title', 'Ujian Tengah Semester')->first();

        // Test native Filament edit action
        $page->call('mountAction', 'editEvent', ['id' => $createdEvent->id]);
        $page->set('mountedActions.0.data.title', 'Ujian Tengah Semester Diperbarui');
        $page->call('callMountedAction');
        $this->assertDatabaseHas('academic_events', [
            'id' => $createdEvent->id,
            'title' => 'Ujian Tengah Semester Diperbarui',
        ]);

        // Test native Filament delete action with confirmation modal
        $page->call('mountAction', 'deleteEvent', ['id' => $createdEvent->id]);
        $page->call('callMountedAction');
        $this->assertDatabaseMissing('academic_events', ['id' => $createdEvent->id]);
    }

    public function test_student_can_view_calendar_but_cannot_see_or_manage_academic_years(): void
    {
        $viewPermission = Permission::firstOrCreate(['name' => 'ViewAny:AcademicYear', 'guard_name' => 'web']);
        $role = Role::where('name', 'Siswa')->first();
        $role->givePermissionTo($viewPermission);

        $student = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $student->assignRole('Siswa');
        $this->actingAs($student);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $event = AcademicEvent::create([
            'academic_year_id' => $academicYear->id,
            'title' => 'Masa Pengenalan Lingkungan Sekolah',
            'category' => AcademicEventCategory::KEGIATAN_SISWA,
            'target_grades' => ['X'],
            'is_holiday' => false,
            'disable_kbm' => false,
            'requires_attendance' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-07-17',
            'is_all_day' => true,
        ]);

        $page = Livewire::test(ListAcademicYears::class)
            ->set('selectedYearId', $academicYear->id)
            ->set('currentMonth', 7)
            ->set('currentYear', 2026);

        $page->assertSuccessful();
        $page->assertSee('Kalender Akademik');
        $page->assertSee('Masa Pengenalan Lingkungan Sekolah');

        // Student CANNOT see "Data Tahun Ajaran" tab
        $page->assertDontSee('Data Tahun Ajaran');
        $page->assertDontSee('Tarik Libur Nasional');
        $page->assertDontSee('Tambah Agenda');
        $page->assertDontSee('Buat Tahun Ajaran');

        // Student CANNOT switch to 'years' tab
        $page->call('switchTab', 'years');
        $page->assertSet('currentViewTab', 'calendar');

        // Student CANNOT create or save events
        $page->call('openCreateEventModal', '2026-07-20');
        $page->assertSet('showEventModal', false);

        $page->set('eventTitle', 'Agenda Liar');
        $page->set('eventCategory', 'kegiatan_siswa');
        $page->set('eventStartDate', '2026-07-20');
        $page->set('eventEndDate', '2026-07-20');
        $page->call('saveEvent');

        $this->assertDatabaseMissing('academic_events', [
            'title' => 'Agenda Liar',
        ]);

        // Student CANNOT delete events
        $page->call('deleteEvent', $event->id);
        $this->assertDatabaseHas('academic_events', [
            'id' => $event->id,
        ]);

        // Student is forbidden from create and edit academic year routes
        $responseCreate = $this->get(AcademicYearResource::getUrl('create'));
        $responseCreate->assertForbidden();

        $responseEdit = $this->get(AcademicYearResource::getUrl('edit', ['record' => $academicYear]));
        $responseEdit->assertForbidden();
    }
}
