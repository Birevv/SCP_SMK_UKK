<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\DocumentParserService;
use App\Services\UserImportService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportSingleUserFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $filePath,
        public User $user
    ) {}

    public function handle(DocumentParserService $parser, UserImportService $importer): void
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        try {
            $rows = $parser->parse($this->filePath);

            if (empty($rows)) {
                $this->cleanupFiles();

                Notification::make()
                    ->title('Gagal Membaca File Pengguna')
                    ->body('Tidak ditemukan data pengguna yang valid pada file tersebut.')
                    ->danger()
                    ->sendToDatabase($this->user);

                return;
            }

            $result = $importer->import($rows, $this->user);
            $this->cleanupFiles();

            $body = "Berhasil memproses: {$result['imported']} user baru ditambahkan, {$result['updated']} user diperbarui.";
            if ($result['skipped'] > 0) {
                $body .= " {$result['skipped']} baris dilewati.";
            }

            if ($result['imported'] > 0 || $result['updated'] > 0) {
                Notification::make()
                    ->title('Import Pengguna Selesai')
                    ->body($body)
                    ->success()
                    ->send()
                    ->sendToDatabase($this->user);
            } else {
                Notification::make()
                    ->title('Import Pengguna Belum Berhasil')
                    ->body($body)
                    ->warning()
                    ->send()
                    ->sendToDatabase($this->user);
            }
        } catch (Throwable $e) {
            $this->cleanupFiles();
            Log::error('Background Import Pengguna Tunggal Gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            Notification::make()
                ->title('Gagal Mengimpor Data Pengguna')
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
            Log::error('ImportSingleUserFileJob Failed: '.$exception->getMessage());
        }
    }

    protected function cleanupFiles(): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }
}
