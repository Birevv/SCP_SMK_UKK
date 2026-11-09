<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Jobs\ImportSingleScheduleFileJob;
use App\Models\Schedule;
use App\Services\DocumentParserService;
use App\Support\ImportHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreateSchedule extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = ScheduleResource::class;

    protected static ?string $title = 'Tambah Jadwal';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_schedule')
                ->label('Import dari File (Excel / PDF / CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => Auth::user()?->can('create', Schedule::class) ?? false)
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
                        ->maxSize(51200)
                        ->required()
                        ->helperText('Format kolom: Guru, NIP (opsional), Mata Pelajaran, Kelas, Ruangan, Hari, Jam Mulai, Jam Selesai, Putaran. Maksimal ukuran file: 50 MB. Pastikan dokumen berisi data tabel yang sesuai.'),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    ImportHelper::dispatch(
                        new ImportSingleScheduleFileJob($filePath, Auth::user())
                    );

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Action::make('download_template')
                ->label('Template CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => Auth::user()?->can('create', Schedule::class) ?? false)
                ->action(function (): StreamedResponse {
                    return response()->streamDownload(function () {
                        echo DocumentParserService::getScheduleCsvTemplate();
                    }, 'template_import_jadwal.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
