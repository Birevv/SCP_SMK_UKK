<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Services\DocumentParserService;
use App\Services\ScheduleImportService;
use App\Services\SmartImportDetectorService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import_schedule')
                ->label('Import Cerdas (Excel / PDF / CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => Auth::user()?->can('create', Schedule::class) ?? false)
                ->modalHeading('Import Dokumen Sekolah Otomatis')
                ->modalDescription('Unggah dokumen Excel daftar hadir siswa atau file PDF jadwal mengajar FET SMKN 1 Bantul. Sistem akan otomatis mendeteksi format, mendaftarkan guru/siswa, dan menginputkan seluruh data.')
                ->modalSubmitActionLabel('Deteksi & Import')
                ->form([
                    FileUpload::make('file')
                        ->label('Pilih / Drop File (Excel .xlsx/.csv atau PDF Jadwal)')
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
                        ->live()
                        ->afterStateUpdated(function ($state, $set, SmartImportDetectorService $detector) {
                            $filePath = $detector->resolveUploadedFilePath($state, 'local');
                            if (! $filePath) {
                                $set('detected_info', null);

                                return;
                            }
                            try {
                                $parsed = $detector->detectAndParse($filePath);
                                $set('detected_info', $parsed);
                                if (! empty($parsed['cycle_type'])) {
                                    $set('cycle_type', $parsed['cycle_type']);
                                }
                            } catch (\Throwable $e) {
                                $set('detected_info', ['error' => $e->getMessage()]);
                            }
                        })
                        ->helperText('Mendukung: 1) File Excel Daftar Hadir Siswa SMKN 1 Bantul, 2) File PDF Jadwal Kelas FET, atau 3) CSV jadwal.'),
                    Placeholder::make('detected_preview')
                        ->label('Hasil Deteksi Otomatis')
                        ->visible(fn ($get) => filled($get('detected_info')))
                        ->content(function ($get) {
                            $info = $get('detected_info');
                            if (! $info) {
                                return null;
                            }

                            if (isset($info['error'])) {
                                return new HtmlString(
                                    '<div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg text-amber-800 dark:text-amber-200 text-sm flex items-center gap-2">'
                                    .'<span class="font-semibold">Perhatian:</span> '.e($info['error'])
                                    .'</div>'
                                );
                            }

                            $type = $info['type'] ?? '';
                            if ($type === 'student_roster') {
                                return new HtmlString(
                                    '<div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-900 dark:text-emerald-100 space-y-2">'
                                    .'<div class="flex items-center gap-2 font-semibold text-emerald-700 dark:text-emerald-300">'
                                    .'<span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Dokumen Terdeteksi: '.e($info['detected_format'] ?? 'Excel Absen')
                                    .'</div>'
                                    .'<div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300 pt-1 border-t border-emerald-100 dark:border-emerald-900">'
                                    .'<div>• <b>Tahun Ajaran:</b> '.e($info['academic_year'] ?? '-').'</div>'
                                    .'<div>• <b>Total Siswa:</b> '.number_format((float) ($info['total_students'] ?? 0)).' siswa</div>'
                                    .'<div>• <b>Total Rombel:</b> '.($info['total_classes'] ?? 0).' kelas</div>'
                                    .'<div>• <b>Status:</b> Siap diimpor ke database</div>'
                                    .'</div>'
                                    .'</div>'
                                );
                            }

                            if ($type === 'timetable_fet') {
                                return new HtmlString(
                                    '<div class="p-4 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-900 dark:text-blue-100 space-y-2">'
                                    .'<div class="flex items-center gap-2 font-semibold text-blue-700 dark:text-blue-300">'
                                    .'<span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500"></span> Dokumen Terdeteksi: '.e($info['detected_format'] ?? 'PDF FET Timetable')
                                    .'</div>'
                                    .'<div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300 pt-1 border-t border-blue-100 dark:border-blue-900">'
                                    .'<div>• <b>Institusi:</b> '.e($info['institution'] ?? 'SMKN 1 Bantul').'</div>'
                                    .'<div>• <b>Putaran:</b> '.e($info['cycle_type'] ?? 'Putaran 1').'</div>'
                                    .'<div>• <b>Total Kelas:</b> '.($info['total_classes'] ?? 0).' kelas</div>'
                                    .'<div>• <b>Total Guru:</b> '.($info['total_teachers'] ?? 0).' guru</div>'
                                    .'<div>• <b>Mata Pelajaran:</b> '.($info['total_subjects'] ?? 0).' mapel</div>'
                                    .'<div>• <b>Slot KBM:</b> '.number_format((float) ($info['total_schedules'] ?? 0)).' jadwal</div>'
                                    .'</div>'
                                    .'</div>'
                                );
                            }

                            return null;
                        }),
                    Select::make('cycle_type')
                        ->label('Target Putaran Jadwal (Khusus PDF Timetable)')
                        ->options([
                            'Putaran 1' => 'Putaran 1',
                            'Putaran 2' => 'Putaran 2',
                        ])
                        ->default('Putaran 1')
                        ->helperText('Jika file berupa PDF jadwal kelas, Anda dapat menentukan putaran jadwal di sini.'),
                ])
                ->action(function (
                    array $data,
                    SmartImportDetectorService $detector,
                    DocumentParserService $legacyParser,
                    ScheduleImportService $legacyImporter
                ) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    try {
                        $parsed = $detector->detectAndParse($filePath);

                        if ($parsed['type'] === 'student_roster' || $parsed['type'] === 'timetable_fet') {
                            $cycle = $data['cycle_type'] ?? null;
                            $result = $detector->import($parsed, Auth::user(), $cycle);
                            @unlink($filePath);

                            $formatName = $parsed['detected_format'] ?? $parsed['type'];
                            $statsText = [];
                            foreach ($result['stats'] as $k => $v) {
                                $statsText[] = ucwords(str_replace('_', ' ', $k)).': '.(is_array($v) ? count($v) : $v);
                            }

                            Notification::make()
                                ->title("Deteksi Berhasil: {$formatName}")
                                ->body($result['message']."\n".implode(' | ', array_slice($statsText, 0, 4)))
                                ->success()
                                ->send();

                            return;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Smart import parser fallback to legacy: '.$e->getMessage());
                    }

                    // Fallback to legacy flat CSV parser
                    $rows = $legacyParser->parse($filePath);
                    if (empty($rows)) {
                        Notification::make()
                            ->title('Gagal Membaca File')
                            ->body('Dokumen tidak dapat dideteksi sebagai format daftar hadir siswa maupun jadwal FET.')
                            ->danger()
                            ->send();

                        @unlink($filePath);

                        return;
                    }

                    $result = $legacyImporter->import($rows, Auth::user());
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

    public function getTabs(): array
    {
        $baseQuery = fn () => static::getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('Semua Putaran')
                ->badge(fn () => $baseQuery()->count()),
            'putaran_1' => Tab::make('Putaran 1')
                ->badge(fn () => $baseQuery()->where('cycle_type', 'Putaran 1')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cycle_type', 'Putaran 1')),
            'putaran_2' => Tab::make('Putaran 2')
                ->badge(fn () => $baseQuery()->where('cycle_type', 'Putaran 2')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cycle_type', 'Putaran 2')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }
}
