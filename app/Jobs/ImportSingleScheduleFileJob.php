<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\DocumentParserService;
use App\Services\ScheduleImportService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportSingleScheduleFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $filePath,
        public User $user
    ) {}

    public function handle(DocumentParserService $parser, ScheduleImportService $importer): void
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        try {
            $rows = $parser->parse($this->filePath);

            if (empty($rows)) {
                $this->cleanupFiles();

                Notification::make()
                    ->title('Gagal Membaca File Jadwal')
                    ->body('Tidak ditemukan data jadwal yang dapat diproses. Pastikan format kolom sesuai template.')
                    ->danger()
                    ->persistent()
                    ->send()
                    ->sendToDatabase($this->user);

                return;
            }

            $result = $importer->import($rows, $this->user);
            $this->cleanupFiles();

            $body = "Berhasil mengimpor {$result['imported']} jadwal.";
            if ($result['teachers_created'] > 0) {
                $body .= " Berhasil mendaftarkan {$result['teachers_created']} guru baru (".implode(', ', $result['teachers_created_names']).').';
            }
            if ($result['skipped'] > 0) {
                $body .= " {$result['skipped']} baris dilewati.";
            }

            if ($result['imported'] > 0) {
                Notification::make()
                    ->title('Import Jadwal Selesai')
                    ->body($body)
                    ->success()
                    ->send()
                    ->sendToDatabase($this->user);
            } else {
                Notification::make()
                    ->title('Import Jadwal Belum Berhasil')
                    ->body($body)
                    ->warning()
                    ->send()
                    ->sendToDatabase($this->user);
            }
        } catch (Throwable $e) {
            $this->cleanupFiles();
            Log::error('Background Import Jadwal Tunggal Gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            Notification::make()
                ->title('Gagal Mengimpor Jadwal')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send()
                ->sendToDatabase($this->user);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->cleanupFiles();
        if ($exception) {
            Log::error('ImportSingleScheduleFileJob Failed: '.$exception->getMessage());
        }
    }

    protected function cleanupFiles(): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }
}
