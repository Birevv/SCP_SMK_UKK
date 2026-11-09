<?php

namespace Tests\Feature;

use App\Filament\Pages\AttendanceReport;
use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HomeroomAndBkClassroomFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Classroom $classRpl1;

    protected Classroom $classRpl2;

    protected Classroom $classTkj1;

    protected Classroom $classTkj2;

    protected User $waliKelas;

    protected User $guruBk;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'View:AttendanceReport', 'guard_name' => 'web']);

        AcademicYear::firstOrCreate([
            'name' => '2026/2027',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $level10 = ClassLevel::firstOrCreate(['level' => 10, 'name' => 'X']);
        $majorRpl = Major::firstOrCreate(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $majorTkj = Major::firstOrCreate(['code' => 'TKJ', 'name' => 'Teknik Komputer dan Jaringan']);
        $rombel1 = Rombel::firstOrCreate(['name' => '1', 'number' => 1]);
        $rombel2 = Rombel::firstOrCreate(['name' => '2', 'number' => 2]);

        $this->classRpl1 = Classroom::create(['name' => 'X RPL 1', 'class_level_id' => $level10->id, 'major_id' => $majorRpl->id, 'rombel_id' => $rombel1->id]);
        $this->classRpl2 = Classroom::create(['name' => 'X RPL 2', 'class_level_id' => $level10->id, 'major_id' => $majorRpl->id, 'rombel_id' => $rombel2->id]);
        $this->classTkj1 = Classroom::create(['name' => 'X TKJ 1', 'class_level_id' => $level10->id, 'major_id' => $majorTkj->id, 'rombel_id' => $rombel1->id]);
        $this->classTkj2 = Classroom::create(['name' => 'X TKJ 2', 'class_level_id' => $level10->id, 'major_id' => $majorTkj->id, 'rombel_id' => $rombel2->id]);

        // 1. Wali Kelas hanya mengampu 1 kelas: X RPL 2
        $this->waliKelas = User::factory()->create();
        $this->waliKelas->assignRole('Wali Kelas');
        $this->waliKelas->givePermissionTo('View:AttendanceReport');
        $this->waliKelas->classrooms()->attach($this->classRpl2->id, ['status' => 'Aktif']);

        // 2. Guru BK mengampu banyak kelas binaan: X RPL 1 dan X RPL 2
        $this->guruBk = User::factory()->create();
        $this->guruBk->assignRole('Guru BK');
        $this->guruBk->givePermissionTo('View:AttendanceReport');
        $this->guruBk->classrooms()->attach([
            $this->classRpl1->id => ['status' => 'Aktif'],
            $this->classRpl2->id => ['status' => 'Aktif'],
        ]);

        // 3. Super Admin
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
        $this->superAdmin->givePermissionTo('View:AttendanceReport');
    }

    public function test_wali_kelas_only_sees_their_assigned_classroom(): void
    {
        $component = Livewire::actingAs($this->waliKelas)->test(AttendanceReport::class);
        $options = $component->instance()->getAvailableClassrooms();

        // Hanya boleh ada 1 kelas, yaitu X RPL 2
        $this->assertCount(1, $options);
        $this->assertArrayHasKey($this->classRpl2->id, $options);
        $this->assertEquals('X RPL 2', $options[$this->classRpl2->id]);

        // Tidak boleh melihat X RPL 1, X TKJ 1, atau X TKJ 2
        $this->assertArrayNotHasKey($this->classRpl1->id, $options);
        $this->assertArrayNotHasKey($this->classTkj1->id, $options);
        $this->assertArrayNotHasKey($this->classTkj2->id, $options);

        // Nilai default form otomatis mengarah ke X RPL 2
        $this->assertEquals($this->classRpl2->id, $component->get('classroom_id'));
    }

    public function test_guru_bk_sees_multiple_assigned_classrooms_only(): void
    {
        $component = Livewire::actingAs($this->guruBk)->test(AttendanceReport::class);
        $options = $component->instance()->getAvailableClassrooms();

        // Harus menampilkan 2 kelas binaan yang diampu (X RPL 1 & X RPL 2)
        $this->assertCount(2, $options);
        $this->assertArrayHasKey($this->classRpl1->id, $options);
        $this->assertArrayHasKey($this->classRpl2->id, $options);

        // Tidak boleh melihat kelas TKJ yang tidak diampu
        $this->assertArrayNotHasKey($this->classTkj1->id, $options);
        $this->assertArrayNotHasKey($this->classTkj2->id, $options);
    }

    public function test_super_admin_sees_all_classrooms(): void
    {
        $component = Livewire::actingAs($this->superAdmin)->test(AttendanceReport::class);
        $options = $component->instance()->getAvailableClassrooms();

        // Super Admin melihat semua 4 kelas
        $this->assertCount(4, $options);
        $this->assertArrayHasKey($this->classRpl1->id, $options);
        $this->assertArrayHasKey($this->classRpl2->id, $options);
        $this->assertArrayHasKey($this->classTkj1->id, $options);
        $this->assertArrayHasKey($this->classTkj2->id, $options);
    }

    public function test_print_authorization_for_wali_kelas(): void
    {
        // Wali Kelas diizinkan mencetak kelas binaannya (X RPL 2)
        $responseAllowed = $this->actingAs($this->waliKelas)
            ->get(route('attendance-report.print', ['classroom_id' => $this->classRpl2->id]));
        $responseAllowed->assertSuccessful();

        // Wali Kelas DITOLAK (403) jika mencoba mengakses cetak kelas lain (X RPL 1)
        $responseForbidden = $this->actingAs($this->waliKelas)
            ->get(route('attendance-report.print', ['classroom_id' => $this->classRpl1->id]));
        $responseForbidden->assertForbidden();
    }

    public function test_print_authorization_for_guru_bk(): void
    {
        // Guru BK diizinkan mencetak kelas binaannya (X RPL 1 dan X RPL 2)
        $this->actingAs($this->guruBk)
            ->get(route('attendance-report.print', ['classroom_id' => $this->classRpl1->id]))
            ->assertSuccessful();

        $this->actingAs($this->guruBk)
            ->get(route('attendance-report.print', ['classroom_id' => $this->classRpl2->id]))
            ->assertSuccessful();

        // Guru BK DITOLAK (403) jika mencoba mengakses cetak kelas yang tidak diampu (X TKJ 1)
        $this->actingAs($this->guruBk)
            ->get(route('attendance-report.print', ['classroom_id' => $this->classTkj1->id]))
            ->assertForbidden();
    }
}
