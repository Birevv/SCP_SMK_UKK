<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\SmartImportDetectorService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportUserFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public ?string $guruPath,
        public ?string $waliPath,
        public ?string $siswaPath,
        public User $user
    ) {}

    public function handle(SmartImportDetectorService $detector): void
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        $summaryMessages = [];

        try {
            // 1. Process Guru first: updates NIPs and creates any teachers not in timetable
            if ($this->guruPath && file_exists($this->guruPath)) {
                $parsedGuru = $detector->detectAndParse($this->guruPath);
                $resGuru = $detector->import($parsedGuru, $this->user);
                $summaryMessages[] = "✔ Guru: {$resGuru['message']}";
                @unlink($this->guruPath);
            }

            // 2. Process Wali Kelas & Guru BK second: assigns homeroom and counselor roles and links classrooms
            if ($this->waliPath && file_exists($this->waliPath)) {
                $parsedWali = $detector->detectAndParse($this->waliPath);
                $resWali = $detector->import($parsedWali, $this->user);
                $summaryMessages[] = "✔ Wali Kelas & Guru BK: {$resWali['message']}";
                @unlink($this->waliPath);
            }

            // 3. Process Siswa third: imports students and attaches to classrooms
            if ($this->siswaPath && file_exists($this->siswaPath)) {
                $parsedSiswa = $detector->detectAndParse($this->siswaPath);
                $resSiswa = $detector->import($parsedSiswa, $this->user);
                $summaryMessages[] = "✔ Siswa: {$resSiswa['message']}";
                @unlink($this->siswaPath);
            }

            $body = ! empty($summaryMessages)
                ? implode("\n", $summaryMessages)
                : 'Seluruh data pengguna berhasil diproses.';

            Notification::make()
                ->title('Import Pengguna Selesai')
                ->body($body)
                ->success()
                ->send()
                ->sendToDatabase($this->user);
        } catch (Throwable $e) {
            $this->cleanupFiles();
            Log::error('Background Import Pengguna Gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

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
            Log::error('ImportUserFilesJob Failed: '.$exception->getMessage());
        }
    }

    protected function cleanupFiles(): void
    {
        if ($this->guruPath && file_exists($this->guruPath)) {
            @unlink($this->guruPath);
        }
        if ($this->waliPath && file_exists($this->waliPath)) {
            @unlink($this->waliPath);
        }
        if ($this->siswaPath && file_exists($this->siswaPath)) {
            @unlink($this->siswaPath);
        }
    }
}
