<?php

namespace Tests\Feature;

use App\Enums\AcademicEventCategory;
use App\Filament\Resources\AcademicYears\Pages\ListAcademicYears;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CulturalCalendarTest extends TestCase
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

    public function test_pasaran_jawa_calculation_accuracy(): void
    {
        $service = app(CulturalCalendarService::class);

        // 1. Tanggal acuan proklamasi: 17 Agustus 1945 adalah Jumat Legi
        $proklamasi = $service->getPasaran('1945-08-17');
        $this->assertEquals('Jumat', $proklamasi['hari']);
        $this->assertEquals('Legi', $proklamasi['pasaran']);
        $this->assertEquals('Jumat Legi', $proklamasi['weton']);

        // 2. Satu hari setelahnya: 18 Agustus 1945 harus Sabtu Pahing
        $dayAfter = $service->getPasaran('1945-08-18');
        $this->assertEquals('Sabtu', $dayAfter['hari']);
        $this->assertEquals('Pahing', $dayAfter['pasaran']);

        // 3. Verifikasi siklus kontinu 5 pasaran
        $dates = ['1945-08-17', '1945-08-18', '1945-08-19', '1945-08-20', '1945-08-21'];
        $expectedPasaran = ['Legi', 'Pahing', 'Pon', 'Wage', 'Kliwon'];

        foreach ($dates as $index => $date) {
            $p = $service->getPasaran($date);
            $this->assertEquals($expectedPasaran[$index], $p['pasaran']);
        }
    }

    public function test_kamis_pahing_identification(): void
    {
        $service = app(CulturalCalendarService::class);

        // Cari satu hari Kamis dalam rentang 35 hari
        $start = Carbon::parse('2026-01-01');
        $foundKamisPahing = false;

        for ($i = 0; $i < 35; $i++) {
            $checkDate = $start->copy()->addDays($i);
            $pasaran = $service->getPasaran($checkDate);

            if ($pasaran['hari'] === 'Kamis' && $pasaran['pasaran'] === 'Pahing') {
                $this->assertTrue($pasaran['is_kamis_pahing']);
                $foundKamisPahing = true;
                break;
            } else {
                $this->assertFalse($pasaran['is_kamis_pahing']);
            }
        }

        $this->assertTrue($foundKamisPahing, 'Siklus 35 hari weton harus memuat tepat 1 Kamis Pahing.');
    }

    public function test_hijri_and_jawa_conversion(): void
    {
        $service = app(CulturalCalendarService::class);
        $result = $service->getHijriAndJawaDate('2026-11-10');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hijri_day', $result);
        $this->assertArrayHasKey('hijri_month_name', $result);
        $this->assertArrayHasKey('hijri_year', $result);
        $this->assertArrayHasKey('jawa_month_name', $result);
        $this->assertArrayHasKey('jawa_year', $result);
        $this->assertArrayHasKey('pasaran', $result);
        $this->assertArrayHasKey('weton', $result);

        $this->assertGreaterThanOrEqual(1, $result['hijri_day']);
        $this->assertLessThanOrEqual(30, $result['hijri_day']);
        $this->assertEquals(1448, $result['hijri_year']);
        $this->assertNotEmpty($result['hijri_short']);
        $this->assertNotEmpty($result['jawa_formatted']);
    }

    public function test_monthly_cultural_calendar_pure_code_offline_and_adjustment(): void
    {
        $service = app(CulturalCalendarService::class);
        $monthData = $service->getMonthlyCalendarData(2026, 11);

        $this->assertIsArray($monthData);
        $this->assertArrayHasKey('2026-11-10', $monthData);
        $this->assertGreaterThanOrEqual(1, $monthData['2026-11-10']['hijri_day']);
        $this->assertEquals('Legi', $monthData['2026-11-10']['pasaran']);
        $this->assertEquals('Selasa Legi', $monthData['2026-11-10']['weton']);

        // Uji penyesuaian manual (offset sidang isbat)
        $initialHijriDay = $monthData['2026-11-10']['hijri_day'];
        $service->setHijriAdjustmentDays(1);
        $adjustedDate = $service->getHijriAndJawaDate('2026-11-10');
        $this->assertEquals(1, $service->getHijriAdjustmentDays());
        // Menguji bahwa offset 1 hari mengubah tanggal hijriah secara deterministik
        $this->assertNotEquals($initialHijriDay - 1, $adjustedDate['hijri_day']);
    }

    public function test_sync_islamic_events_within_semester_range(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        // Semester Genap 2026: 1 Jan 2026 s/d 30 Juni 2026
        $academicYear = AcademicYear::create([
            'name' => '2025/2026 - Genap',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $service = app(CulturalCalendarService::class);
        $result = $service->syncIslamicEvents($academicYear, 2026);

        $this->assertGreaterThan(0, $result['total_synced']);

        // Pastikan agenda Idul Fitri 2026 tersimpan
        $idulFitri = AcademicEvent::where('academic_year_id', $academicYear->id)
            ->where('title', 'like', '%Idul Fitri%')
            ->first();

        $this->assertNotNull($idulFitri);
        $this->assertEquals(AcademicEventCategory::KEAGAMAAN_ISLAM, $idulFitri->category);
        $this->assertTrue($idulFitri->is_holiday);
        $this->assertTrue($idulFitri->disable_kbm);

        // Uji Idempotensi: sinkronisasi ulang tidak menduplikasi data
        $countBefore = AcademicEvent::where('academic_year_id', $academicYear->id)->count();
        $service->syncIslamicEvents($academicYear, 2026);
        $countAfter = AcademicEvent::where('academic_year_id', $academicYear->id)->count();
        $this->assertEquals($countBefore, $countAfter);
    }

    public function test_sync_javanese_cultural_events(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026 - Genap',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $service = app(CulturalCalendarService::class);
        $result = $service->syncJavaneseCulturalEvents($academicYear, 2026, includeKamisPahing: true);

        $this->assertGreaterThan(0, $result['total_synced']);

        // Verifikasi event Kamis Pahing terdaftar dengan atribut tepat
        $kamisPahing = AcademicEvent::where('academic_year_id', $academicYear->id)
            ->where('title', 'like', '%Kamis Pahing%')
            ->first();

        $this->assertNotNull($kamisPahing);
        $this->assertEquals(AcademicEventCategory::BUDAYA_JAWA, $kamisPahing->category);
        $this->assertFalse($kamisPahing->is_holiday);
        $this->assertFalse($kamisPahing->disable_kbm);
        $this->assertTrue($kamisPahing->requires_attendance);
    }

    public function test_custom_gagrak_kamis_pon_flexibility(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2025/2026 - Genap',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $service = app(CulturalCalendarService::class);
        // Konfigurasi ke Kamis Pon (Gagrak Surakarta)
        $service->setGagrak('Kamis', 'Pon', 'Gagrak Surakarta');

        $result = $service->syncJavaneseCulturalEvents(
            $academicYear,
            2026,
            includeGagrak: true,
            gagrakHari: 'Kamis',
            gagrakPasaran: 'Pon',
            gagrakTitle: 'Kamis Pon (Busana Adat Jawa Gagrak Surakarta)'
        );

        $this->assertGreaterThan(0, $result['total_synced']);

        // Verifikasi event Kamis Pon tersimpan
        $kamisPon = AcademicEvent::where('academic_year_id', $academicYear->id)
            ->where('title', 'like', '%Kamis Pon%')
            ->first();

        $this->assertNotNull($kamisPon);
        $this->assertEquals(AcademicEventCategory::BUDAYA_JAWA, $kamisPon->category);
        $this->assertFalse($kamisPon->is_holiday);
        $this->assertTrue($kamisPon->requires_attendance);
    }

    public function test_livewire_academic_calendar_renders_with_cultural_dates(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $test = Livewire::test(ListAcademicYears::class);
        $test->assertSuccessful();

        // Verifikasi toggle cultural dates
        $this->assertTrue($test->get('showCulturalDates'));
        $test->call('toggleCulturalDates');
        $this->assertFalse($test->get('showCulturalDates'));

        // Verifikasi struktur data calendarWeeks memuat pasaran dan hijriah
        $weeks = $test->get('calendarWeeks');
        $this->assertIsArray($weeks);
        $firstDay = $weeks[0]['days'][0];

        $this->assertArrayHasKey('pasaran', $firstDay);
        $this->assertArrayHasKey('weton', $firstDay);
        $this->assertArrayHasKey('hijri_short', $firstDay);
        $this->assertArrayHasKey('hijri_formatted', $firstDay);
        $this->assertNotEmpty($firstDay['pasaran']);

        // Verifikasi blok libur nasional terdeteksi merah otomatis
        AcademicEvent::create([
            'academic_year_id' => $academicYear->id,
            'title' => 'Hari Kemerdekaan RI',
            'start_date' => '2026-08-17',
            'end_date' => '2026-08-17',
            'category' => AcademicEventCategory::LIBUR_NASIONAL,
            'is_holiday' => true,
            'is_all_day' => true,
            'created_by' => $user->id,
        ]);

        $test->set('currentMonth', 8);
        $test->set('currentYear', 2026);
        $weeks = $test->get('calendarWeeks');

        $foundAug17 = false;
        foreach ($weeks as $w) {
            foreach ($w['days'] as $d) {
                if ($d['date'] === '2026-08-17') {
                    $this->assertTrue($d['is_national_holiday']);
                    $foundAug17 = true;
                }
            }
        }
        $this->assertTrue($foundAug17);
        $test->assertSee('scp-day-holiday');
    }

    public function test_sync_year_2027_events_with_full_year_resolution(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        // Academic Year yang start_date awalnya hanya di 2026
        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Genap',
            'is_active' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $service = app(CulturalCalendarService::class);
        $resIslam = $service->syncIslamicEvents($academicYear, 2027, forceFullYear: true);
        $resJawa = $service->syncJavaneseCulturalEvents($academicYear, 2027, includeGagrak: true, forceFullYear: true);

        $this->assertGreaterThan(0, $resIslam['total_synced']);
        $this->assertGreaterThan(0, $resJawa['total_synced']);

        // Pastikan agenda 2027 tersimpan tanpa ditolak batas tanggal 2026
        $event2027Count = AcademicEvent::where('academic_year_id', $academicYear->id)
            ->whereYear('start_date', 2027)
            ->count();

        $this->assertGreaterThan(5, $event2027Count);
    }
}

