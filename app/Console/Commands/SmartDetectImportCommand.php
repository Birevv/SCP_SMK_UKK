<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SmartImportDetectorService;
use Illuminate\Console\Command;

class SmartDetectImportCommand extends Command
{
    protected $signature = 'import:detect 
                            {file : Path ke file Excel (.xlsx, .csv) atau PDF FET Timetable}
                            {--import : Eksekusi simpan ke database}
                            {--dry-run : Hanya validasi dan tampilkan ringkasan tanpa simpan}
                            {--cycle=Putaran 1 : Putaran jadwal (Putaran 1 / Putaran 2)}
                            {--academic-year-id= : ID Tahun Ajaran target (opsional)}';

    protected $description = 'Deteksi otomatis tipe dokumen sekolah (Daftar Hadir Siswa / Jadwal FET) dan impor ke database';

    public function handle(SmartImportDetectorService $service): int
    {
        $filePath = (string) $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $this->info("============================================================");
        $this->info("   SCP SMK - PENDETEKSI & IMPORT DOKUMEN OTOMATIS");
        $this->info("============================================================");
        $this->line("Memeriksa dan mendeteksi format dokumen...");

        try {
            $data = $service->detectAndParse($filePath);

            $this->info("Format Terdeteksi : " . ($data['detected_format'] ?? $data['type']));

            if ($data['type'] === 'student_roster') {
                $this->line("Tahun Ajaran      : " . ($data['academic_year'] ?? '-'));
                $this->line("Total Kelas       : " . ($data['total_classes'] ?? 0) . " kelas");
                $this->line("Total Siswa       : " . ($data['total_students'] ?? 0) . " siswa");
                $this->newLine();

                $this->table(
                    ['Kelas', 'No Urut', 'NIS', 'Nama Siswa', 'Gender'],
                    array_map(fn ($s) => [
                        $s['class'],
                        $s['no'],
                        $s['nis'],
                        $s['name'],
                        $s['gender'] ?? '-',
                    ], array_slice($data['students'], 0, 8))
                );

                if (count($data['students']) > 8) {
                    $this->comment("... dan " . (count($data['students']) - 8) . " siswa lainnya.");
                }

            } elseif ($data['type'] === 'timetable_fet') {
                $cycle = $this->option('cycle') ?: ($data['cycle_type'] ?? 'Putaran 1');
                $this->line("Institusi         : " . ($data['institution'] ?? 'SMK NEGERI 1 BANTUL'));
                $this->line("Putaran           : " . $cycle);
                $this->line("Total Kelas       : " . ($data['total_classes'] ?? 0) . " kelas");
                $this->line("Total Guru        : " . ($data['total_teachers'] ?? 0) . " guru");
                $this->line("Total Mapel       : " . ($data['total_subjects'] ?? 0) . " mapel");
                $this->line("Total Ruang       : " . ($data['total_rooms'] ?? 0) . " ruangan");
                $this->line("Total Slot Jadwal : " . ($data['total_schedules'] ?? 0) . " jadwal");
                $this->newLine();

                $this->table(
                    ['Kelas', 'Hari', 'Jam', 'Mata Pelajaran', 'Guru', 'Ruang'],
                    array_map(fn ($sc) => [
                        $sc['classroom'],
                        $sc['day_of_week'],
                        substr($sc['start_time'], 0, 5) . ' - ' . substr($sc['end_time'], 0, 5),
                        $sc['subject'],
                        $sc['teacher_name'],
                        $sc['room'] ?? '-',
                    ], array_slice($data['schedules'], 0, 8))
                );

                if (count($data['schedules']) > 8) {
                    $this->comment("... dan " . (count($data['schedules']) - 8) . " slot jadwal lainnya.");
                }
            }

            if ($this->option('dry-run')) {
                $this->newLine();
                $this->warn("[DRY RUN] Mode validasi selesai. Tidak ada data yang diubah dalam database.");

                return self::SUCCESS;
            }

            if ($this->option('import') || $this->confirm("Apakah Anda ingin langsung mengimpor data ini ke dalam database?", true)) {
                $this->info("Menyimpan ke database dengan transaksi aman...");
                
                $cycle = $this->option('cycle') ?: null;
                $ayId = $this->option('academic-year-id') ? (int) $this->option('academic-year-id') : null;

                $res = $service->import($data, null, $cycle, $ayId);

                $this->info("✔ " . $res['message']);
                foreach ($res['stats'] as $k => $v) {
                    $this->line("  • " . ucwords(str_replace('_', ' ', $k)) . ": " . (is_array($v) ? count($v) : $v));
                }

                $this->info("IMPORT SELESAI DENGAN SUKSES!");
            } else {
                $this->comment("Import dibatalkan.");
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Terjadi kesalahan: " . $e->getMessage());

            return self::FAILURE;
        }
    }
}

