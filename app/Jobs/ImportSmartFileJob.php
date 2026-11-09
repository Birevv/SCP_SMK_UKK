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

class ImportSmartFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $filePath,
        public User $user,
        public string $cycle = 'Putaran 1'
    ) {}

    public function handle(SmartImportDetectorService $detector): void
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        try {
            if (! file_exists($this->filePath)) {
                return;
            }

            $parsed = $detector->detectAndParse($this->filePath);
            $cycle = $this->cycle ?? ($parsed['cycle_type'] ?? 'Putaran 1');
            $result = $detector->import($parsed, $this->user, $cycle);

            $this->cleanup();

            $formatName = $parsed['detected_format'] ?? ($parsed['type'] ?? 'Dokumen');

            Notification::make()
                ->title("Import Berhasil: {$formatName}")
                ->body($result['message'] ?? 'Import file berhasil diselesaikan.')
                ->success()
                ->sendToDatabase($this->user);
        } catch (Throwable $e) {
            Log::error('Smart import background job failed: '.$e->getMessage(), [
                'file' => $this->filePath,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->cleanup();

            Notification::make()
                ->title('Gagal Memproses Dokumen')
                ->body($e->getMessage())
                ->danger()
                ->sendToDatabase($this->user);
        }
    }

    protected function cleanup(): void
    {
        if (file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }
}
