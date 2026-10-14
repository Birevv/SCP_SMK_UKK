<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Services\DocumentParserService;
use App\Services\ScheduleImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import_schedule')
                ->label('Import Jadwal (Excel / PDF / CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Jadwal Pelajaran')
                ->modalDescription('Unggah atau drop file Excel (.xlsx, .csv) atau PDF berisi jadwal mengajar. Sistem akan otomatis mendaftarkan guru (jika belum ada) dan menginputkan jadwal mengajar.')
                ->modalSubmitActionLabel('Mulai Import')
                ->form([
                    FileUpload::make('file')
                        ->label('Drop File Excel / PDF / CSV Di Sini')
                        ->disk('local')
                        ->directory('imports/schedules')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                            'application/pdf',
                        ])
                        ->required()
                        ->helperText('Format kolom: Guru, NIP (opsional), Mata Pelajaran, Kelas, Ruangan, Hari, Jam Mulai, Jam Selesai, Putaran. Maksimal ukuran file: 2 MB (batas PHP bawaan). Pastikan dokumen berisi data tabel yang sesuai.'),
                ])
                ->action(function (array $data, DocumentParserService $parser, ScheduleImportService $importer) {
                    $filePath = Storage::disk('local')->path($data['file']);
                    $rows = $parser->parse($filePath);

                    if (empty($rows)) {
                        Notification::make()
                            ->title('Gagal Membaca File')
                            ->body('Tidak ditemukan data jadwal yang dapat diproses. Pastikan format kolom sesuai dengan template.')
                            ->danger()
                            ->send();

                        @unlink($filePath);

                        return;
                    }

                    $result = $importer->import($rows, Auth::user());
                    @unlink($filePath);

                    $body = "Berhasil mengimpor {$result['imported']} jadwal.";
                    if ($result['teachers_created'] > 0) {
                        $body .= " Berhasil mendaftarkan {$result['teachers_created']} guru baru (".implode(', ', $result['teachers_created_names']).').';
                    }
                    if ($result['skipped'] > 0) {
                        $body .= " {$result['skipped']} baris dilewati.";
                    }

                    if ($result['imported'] > 0) {
                        Notification::make()
                            ->title('Import Jadwal Berhasil')
                            ->body($body)
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import Jadwal Belum Berhasil')
                            ->body($body)
                            ->warning()
                            ->send();
                    }

                    if (! empty($result['errors'])) {
                        Notification::make()
                            ->title('Catatan Validasi')
                            ->body(implode("\n", array_slice($result['errors'], 0, 5)))
                            ->warning()
                            ->send();
                    }
                }),
            Action::make('download_template')
                ->label('Template CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    return response()->streamDownload(function () {
                        echo DocumentParserService::getScheduleCsvTemplate();
                    }, 'template_import_jadwal.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }
}
