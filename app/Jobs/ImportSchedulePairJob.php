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

class ImportSchedulePairJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $classFilePath,
        public string $roomFilePath,
        public User $user,
        public string $cycle = 'Putaran 1'
    ) {}

    public function handle(SmartImportDetectorService $detector): void
    {
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');

        try {
            $parsed = $detector->detectAndParseSchedulePair($this->classFilePath, $this->roomFilePath);
            $cycle = $this->cycle ?: ($parsed['cycle_type'] ?? 'Putaran 1');
            $result = $detector->import($parsed, $this->user, $cycle);

            @unlink($this->classFilePath);
            @unlink($this->roomFilePath);

            $formatName = $parsed['detected_format'] ?? 'PDF Gabungan Jadwal Kelas & Ruang';
            $statsText = [];
            foreach ($result['stats'] as $k => $v) {
                $statsText[] = ucwords(str_replace('_', ' ', (string) $k)).': '.(is_array($v) ? count($v) : $v);
            }

            Notification::make()
                ->title("Import Selesai: {$formatName}")
                ->body($result['message']."\n".implode(' | ', array_slice($statsText, 0, 5)))
                ->success()
                ->send()
                ->sendToDatabase($this->user);
        } catch (Throwable $e) {
            $this->cleanupFiles();
            Log::error('Background Import Jadwal Gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            Notification::make()
                ->title('Gagal Mengimpor Jadwal PDF')
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
            Log::error('ImportSchedulePairJob Failed: '.$exception->getMessage());
        }
    }

    protected function cleanupFiles(): void
    {
        if (file_exists($this->classFilePath)) {
            @unlink($this->classFilePath);
        }
        if (file_exists($this->roomFilePath)) {
            @unlink($this->roomFilePath);
        }
    }
}
