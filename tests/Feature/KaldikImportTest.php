<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYears\Pages\ListAcademicYears;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use App\Services\KaldikImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KaldikImportTest extends TestCase
{
    use RefreshDatabase;

    protected string $sampleKaldikContent = <<<'CSV'
KALENDER PENDIDIKAN TAHUN AJARAN 2026/2027,,,,,,,,,,,,,,,,,,,,,,,,,,,,
SMKN 1 BANTUL,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
JULI 2026,,,,,,,,AGUSTUS 2026,,,,,,,,SEPTEMBER 2026,,,,,,,,,,,,
AHAD,,5,12,19,26,,,AHAD,,2,9,16,23,30,,AHAD,,6,13,20,27,,,,,,,
SENIN,,6,13,20,27,,,SENIN,,3,10,17,24,31,,SENIN,,7,14,21,28,,,,,,,
SELASA,,7,14,21,28,,,SELASA,,4,11,18,25,,,SELASA,1,8,15,22,29,,,,,,,
RABU,1,8,15,22,29,,,RABU,,5,12,19,26,,,RABU,2,9,16,23,30,,,,,,,
KAMIS,2,9,16,23,30,,,KAMIS,,6,13,20,27,,,KAMIS,3,10,17,24,,,,,,,,
JUMÁT,3,10,17,24,31,,,JUMÁT,,7,14,21,28,,,JUMÁT,4,11,18,25,,,,,,,,
SABTU,4,11,18,25,,,,SABTU,1,8,15,22,29,,,SABTU,5,12,19,26,,,,,,,,
1 - 10 Juli 2025 : Libur Akhir Tahun Ajaran 2025/2026,,,,,,,,17 Agustus 2026 : Hari Kemerdekaan RI ke-81,,,,,,,,"14 s.d 18 Sept. 2026: PSTS Ganjil kelas X, XI ",,,,,,,,,,,,
13 Juli 2026 : Hari Pertama Masuk Sekolah 2026/2027,,,,,,,,25 Agustus 2026 : Maulid Nabi Muhammad SAW,,,,,,,,& PSAS Ganjil kelas XII,,,,,,,,,,,,
13 - 17 Juli 2026 : MPLS Tahun Ajaran Baru 2026/2027,,,,,,,,,,,,,,,,21 s.d 27 Sept 2026 : Simulasi TKA/AN,,,,,,,,,,,,
27-31 Juli 2026 : Rentang pelaksanaan TOEIC,,,,,,,,,,,,,,,,,,,,,,,,,,,,
OKTOBER 2026,,,,,,,,NOVEMBER 2026,,,,,,,,DESEMBER 2026,,,,,,,,,,,,
AHAD,,4,11,18,25,,,AHAD,,1,8,15,22,29,,AHAD,,6,13,20,27,,,,,,,22000
SENIN,,5,12,19,26,,,SENIN,,2,9,16,23,30,,SENIN,,7,14,21,28,,,,,,,
SELASA,,6,13,20,27,,,SELASA,,3,10,17,24,,,SELASA,1,8,15,22,29,,,,,,,
RABU,,7,14,21,28,,,RABU,,4,11,18,25,,,RABU,2,9,16,23,30,,,,,,,78700
KAMIS,1,8,15,22,29,,,KAMIS,,5,12,19,26,,,KAMIS,3,10,17,24,31,,,,,,,
JUMÁT,2,9,16,23,30,,,JUMÁT,,6,13,20,27,,,JUMÁT,4,11,18,25,,,,,,,,
SABTU,3,10,17,24,31,,,SABTU,,7,14,21,28,,,SABTU,5,12,19,26,,,,,,,,
26-29 Oktober 2026: TKA & AN kelas XII,,,,,,,,9-13 November 2026 : UKK Mandiri ,,,,,,,,1-4 Desember 2026 : USEK  & PSAS ,,,,,,,,,,,,
5-8 Oktober 2026 : Gladi Bersih TKA/AN,,,,,,,,16-19 November 2026 : Tes Susulan TKA & AN (Tentatif),,,,,,,,18 Desember 2026 : Penerimaan Rapor Sem. 1,,,,,,,,,,,,
,,,,,,,,25 November 2026 : Hari Guru Nasional,,,,,,,,21 - 31 Desember 2026 : Libur Akhir Semester 1,,,,,,,,,,,,
,,,,,,,,26-28 November 2026 : USEK & PSAS Ganjil ,,,,,,,,25 Desember 2026 : Hari Raya Natal,,,,,,,,,,,,
,,,,,,,,,,,,,,,,26 Desember 2026 : Cuti Bersama Hari Natal,,,,,,,,,,,,
,,,,,,,,,,,,,,,,14 Desember 2026 : Penerjunan siswa PKL,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
JANUARI 2027,,,,,,,,FEBRUARI 2027,,,,,,,,MARET 2027,,,,,,,,,,,,
AHAD,,3,10,17,24,31,,AHAD,,7,14,21,28,,,AHAD,,7,14,21,28,,,,,,,
SENIN,,4,11,18,25,,,SENIN,1,8,15,22,,,,SENIN,1,8,15,22,29,,,,,,,
SELASA,,5,12,19,26,,,SELASA,2,9,16,23,,,,SELASA,2,9,16,23,30,,,,,,,
RABU,,6,13,20,27,,,RABU,3,10,17,24,,,,RABU,3,10,17,24,31,,,,,,,
KAMIS,,7,14,21,28,,,KAMIS,4,11,18,25,,,,KAMIS,4,11,18,25,,,,,,,,
JUMÁT,1,8,15,22,29,,,JUMÁT,5,12,19,26,,,,JUMÁT,5,12,19,26,,,,,,,,
SABTU,2,9,16,23,30,,,SABTU,6,13,20,27,,,,SABTU,6,13,20,27,,,,,,,,
1 Januari 2027 : Tahun Baru 2027,,,,,,,,6 Februari 2027 : Tahun Baru Imlek,,,,,,,,4-8 Maret 2027 : Libur akhir Puasa ,,,,,,,,,,,,
4 Januari 2027 : Awal Masuk Semester Genap,,,,,,,,8-9  Februari 2027 : Libur Awal Ramadhan 1448 H,,,,,,,,9 Maret 2027 :Hari Raya Nyepi,,,,,,,,,,,,
5 Januari 2026 : Isra Mi'raj Nabi Muhammad,,,,,,,,,,,,,,,,10-11 Maret 2027 : Libur Idul Fitri 1448 H,,,,,,,,,,,,
,,,,,,,,,,,,,,,,13 Maret 2027 : Hari Jadi DIY ,,,,,,,,,,,,
,,,,,,,,,,,,,,,,"26, 28 Maret : Wafat & Kebangkitan Yesus Kristus",,,,,,,,,,,,
,,,,,,,,,,,,,,,,23-29 Maret 2027 : PSTS Ganjil ,,,,,,,,,,,,
APRIL 2027,,,,,,,,MEI 2027,,,,,,,,JUNI 2027,,,,,,,,,,,,
AHAD,,4,11,18,25,,,AHAD,,2,9,16,23,30,,AHAD,,6,13,20,27,,,,,,,
SENIN,,5,12,19,26,,,SENIN,,3,10,17,24,31,,SENIN,,7,14,21,28,,,,,,,
SELASA,,6,13,20,27,,,SELASA,,4,11,18,25,,,SELASA,1,8,15,22,30,,,,,,,
RABU,,7,14,21,28,,,RABU,,5,12,19,26,,,RABU,2,9,16,23,,,,,,,,
KAMIS,1,8,15,22,29,,,KAMIS,,6,13,20,27,,,KAMIS,3,10,17,24,,,,,,,,
JUMÁT,2,9,16,23,30,,,JUMÁT,,7,14,21,28,,,JUMÁT,4,11,18,25,,,,,,,,
SABTU,3,10,17,24,,,,SABTU,1,8,15,22,29,,,SABTU,5,12,19,26,,,,,,,,
23 April 2027 :  Penarikan Siswa PKL,,,,,,,,1 Mei 2027 : Hari Buruh Nasional,,,,,,,,1 Juni 2027 : Hari Lahir Pancasila,,,,,,,,,,,,
,,,,,,,,3 Mei 2027 : Kelulusan SMK,,,,,,,,2-3 Juni 2027 : PSAS Genap,,,,,,,,,,,,
,,,,,,,,6 Mei 2027 : Kenaikan Isa Almasih,,,,,,,,6 Juni 2027: Tahun Baru Islam 1449 H,,,,,,,,,,,,
,,,,,,,,17 Mei 2027 : Hari  Idul Adha,,,,,,,,18 Juni 2027 : Penerimaan Rapor Akhir Tahun,,,,,,,,,,,,
,,,,,,,,20 Mei 2027 : Hari Waisak,,,,,,,,21-30 Juni 2027 : Libur Akhir Tahun 2026/2027,,,,,,,,,,,,
,,,,,,,,24-28 Mei 2027 : PSAS Genap,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
JULI 2027,,,,,,,,,,,,,,,,,,,,,,,,,,,,
AHAD,,4,11,18,25,,,,,,,,,,,,,,,,,,,,,,,
SENIN,,5,12,19,26,,,,,,,,,,,,,,,,,,,,,,,
SELASA,,6,13,20,27,,,,,,,,,,,,,,,,,,,,,,,
RABU,,7,14,21,28,,,,,,,,,,,"Bantul, 26 Juni 2026",,,,,,,,,,,,
KAMIS,1,8,15,22,29,,,,,,,,,,,"Kepala Sekolah,",,,,,,,,,,,,
JUMÁT,2,9,16,23,30,,,,,,,,,,,,,,,,,,,,,,,
SABTU,3,10,17,24,31,,,,,,,,,,,,,,,,,,,,,,,
1-10 Juli 2027 : Libur Akhir Tahun Ajaran 2026/2027,,,,,,,,,,,,,,,,,,,,,,,,,,,,
12 Juli 2027 : Hari I Masuk Sekolah TA. Baru 2027/2028,,,,,,,,,,,,,,,,"RAHARJO, S.IP., M.Pd",,,,,,,,,,,,
,PERHITUNGAN MINGGU EFEKTIF KELAS X,,,,,,,,,,,,,,,,
,TAHUN PELAJARAN 2025/2026,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,
,Semester Ganjil,,,,,,,,,,,,,,,,
,No,Bulan,Jumlah Minggu,Jumah Minggu,,Keterangan (pertemuan tidak efektif),,,,,,,,,,,
,,,,Efektif,Tidak Efektif,,,,,,,,,,,,
,1,Juli,3,2,1,Minggu II : MPLS,,,,,,,,,,,
,2,Agustus,4,4,0,Minggu III : P5,,,,,,,,,,,
,3,September,4,3,1,Minggu IV PSTS Ganjil/ PSA Kelas XII,,,,,,,,,,,
,4,Oktober,5,5,0,,,,,,,,,,,,
,5,Nopember,4,4,0,,,,,,,,,,,,
,6,Desember,5,0,4,Minggu I & II : PSAS Ganjil ,,,,,,,,,,,
,,,,,,1 minggu: pasca PAS,,,,,,,,,,,
,,,,,,2 minggu: libur akhir semester ganjil,,,,,,,,,,,
,Jumlah,,25,18,6,,,,,,,,,,,,aa
CSV;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
    }

    public function test_kaldik_import_service_parses_sample_file_correctly(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'kaldik_').'.csv';
        file_put_contents($tempFile, $this->sampleKaldikContent);

        $service = new KaldikImportService;
        $parsed = $service->parse($tempFile);

        $this->assertEquals('SMKN 1 BANTUL', $parsed['school_name']);
        $this->assertEquals('2026/2027', $parsed['academic_year_name']);
        $this->assertEquals(2026, $parsed['start_year']);
        $this->assertEquals(2027, $parsed['end_year']);
        $this->assertEquals(45, $parsed['total_events']);
        $this->assertNotEmpty($parsed['events']);

        // Check specific key events
        $events = collect($parsed['events']);

        // 1. Libur Akhir Tahun Ajaran 2025/2026 (starts 2026-07-01 to 2026-07-10)
        $liburAkhir = $events->firstWhere('title', 'Libur Akhir Tahun Ajaran 2025/2026');
        $this->assertNotNull($liburAkhir);
        $this->assertEquals('2026-07-01', $liburAkhir['start_date']);
        $this->assertEquals('2026-07-10', $liburAkhir['end_date']);
        $this->assertEquals('libur_sekolah', $liburAkhir['category']);
        $this->assertTrue($liburAkhir['is_holiday']);

        // 2. UKK Mandiri (target_grades: XII)
        $ukk = $events->firstWhere('title', 'UKK Mandiri');
        $this->assertNotNull($ukk);
        $this->assertEquals('2026-11-09', $ukk['start_date']);
        $this->assertEquals('2026-11-13', $ukk['end_date']);
        $this->assertEquals('ukk', $ukk['category']);
        $this->assertEquals(['XII'], $ukk['target_grades']);

        // 3. Penerjunan siswa PKL (target_grades: XI, XII)
        $pkl = $events->firstWhere('title', 'Penerjunan siswa PKL');
        $this->assertNotNull($pkl);
        $this->assertEquals('2026-12-14', $pkl['start_date']);
        $this->assertEquals('pkl', $pkl['category']);
        $this->assertEquals(['XI', 'XII'], $pkl['target_grades']);

        // 4. Hari Kemerdekaan RI ke-81 (requires_attendance: true)
        $kemerdekaan = $events->firstWhere('title', 'Hari Kemerdekaan RI ke-81');
        $this->assertNotNull($kemerdekaan);
        $this->assertEquals('2026-08-17', $kemerdekaan['start_date']);
        $this->assertTrue($kemerdekaan['requires_attendance']);

        // 5. Consolidated multiline event: PSTS Ganjil kelas X, XI & PSAS Ganjil kelas XII
        $jointExam = $events->first(fn ($e) => str_contains($e['title'], 'PSTS Ganjil') && str_contains($e['title'], 'PSAS Ganjil'));
        $this->assertNotNull($jointExam);
        $this->assertEquals('2026-09-14', $jointExam['start_date']);
        $this->assertEquals('2026-09-18', $jointExam['end_date']);
        $this->assertEquals('ujian', $jointExam['category']);

        // 6. Effective weeks parsed
        $this->assertArrayHasKey('KELAS X', $parsed['effective_weeks']);
        $this->assertNotEmpty($parsed['effective_weeks']['KELAS X']['ganjil']);

        unlink($tempFile);
    }

    public function test_kaldik_import_service_imports_into_database_with_auto_semesters(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'kaldik_').'.csv';
        file_put_contents($tempFile, $this->sampleKaldikContent);

        $service = new KaldikImportService;
        $parsed = $service->parse($tempFile);

        $result = $service->import($parsed, null, true, null);

        $this->assertTrue($result['success']);
        $this->assertEquals(45, $result['total_events']);
        $this->assertEquals(45, $result['created_count']);
        $this->assertContains('2026/2027 - Ganjil', $result['academic_years_affected']);
        $this->assertContains('2026/2027 - Genap', $result['academic_years_affected']);

        // Verify both semesters exist in database
        $ganjil = AcademicYear::where('name', '2026/2027 - Ganjil')->first();
        $genap = AcademicYear::where('name', '2026/2027 - Genap')->first();

        $this->assertNotNull($ganjil);
        $this->assertNotNull($genap);
        $this->assertEquals('2026-07-01', $ganjil->start_date->toDateString());
        $this->assertEquals('2026-12-31', $ganjil->end_date->toDateString());

        // Verify events belong to correct semester
        $ganjilEventsCount = AcademicEvent::where('academic_year_id', $ganjil->id)->count();
        $genapEventsCount = AcademicEvent::where('academic_year_id', $genap->id)->count();

        $this->assertGreaterThan(0, $ganjilEventsCount);
        $this->assertGreaterThan(0, $genapEventsCount);
        $this->assertEquals(45, $ganjilEventsCount + $genapEventsCount);

        // Re-importing should update without duplicates
        $reimportResult = $service->import($parsed, null, true, null);
        $this->assertEquals(0, $reimportResult['created_count']);
        $this->assertEquals(45, $reimportResult['updated_count']);
        $this->assertEquals(45, AcademicEvent::count());

        unlink($tempFile);
    }

    public function test_list_academic_years_kaldik_import_modal_and_flow(): void
    {
        $role = Role::where('name', 'Super Admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $page = Livewire::test(ListAcademicYears::class);
        $page->assertSuccessful();

        // Check button exists
        $page->assertSee('Import Kaldik');

        // Open modal
        $page->call('openImportKaldikModal');
        $page->assertSet('showImportKaldikModal', true);

        // Create uploaded file fake
        $fakeFile = UploadedFile::fake()->createWithContent('kaldik_smkn1.csv', $this->sampleKaldikContent);
        $page->set('kaldikFile', $fakeFile);

        // Preview should be populated
        $preview = $page->get('kaldikPreviewData');
        $this->assertNotNull($preview);
        $this->assertEquals(45, $preview['total_events']);
        $this->assertEquals('SMKN 1 BANTUL', $preview['school_name']);

        // Execute import
        $page->call('executeImportKaldik');
        $page->assertSet('showImportKaldikModal', false);

        // Verify database has the 45 events
        $this->assertEquals(45, AcademicEvent::count());

        // Test sample download
        $response = $page->instance()->downloadSampleKaldik();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_student_cannot_access_import_kaldik(): void
    {
        $viewPermission = Permission::firstOrCreate(['name' => 'ViewAny:AcademicYear', 'guard_name' => 'web']);
        $role = Role::where('name', 'Siswa')->first();
        $role->givePermissionTo($viewPermission);

        $student = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $student->assignRole('Siswa');
        $this->actingAs($student);

        AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
        ]);

        $page = Livewire::test(ListAcademicYears::class);
        $page->assertSuccessful();

        // Student CANNOT see Import Kaldik button
        $page->assertDontSee('Import Kaldik');

        // Calling openImportKaldikModal should be blocked
        $page->call('openImportKaldikModal');
        $page->assertSet('showImportKaldikModal', false);
    }
}
