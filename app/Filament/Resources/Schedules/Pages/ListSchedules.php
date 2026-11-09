<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Jobs\ImportSchedulePairJob;
use App\Models\Schedule;
use App\Services\DocumentParserService;
use App\Services\SmartImportDetectorService;
use App\Support\ImportHelper;
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
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function refreshScheduleDetectionPreview(
        callable $get,
        callable $set,
        SmartImportDetectorService $detector
    ): void {
        $classFilePath = $detector->resolveUploadedFilePath($get('class_schedule_file'), 'local');
        $roomFilePath = $detector->resolveUploadedFilePath($get('room_schedule_file'), 'local');

        if (! $classFilePath && ! $roomFilePath) {
            $set('detected_info', null);

            return;
        }

        try {
            if ($classFilePath && $roomFilePath) {
                $preview = $detector->quickPreviewSchedulePair($classFilePath, $roomFilePath);
                $set('detected_info', $preview);
                if (! empty($preview['cycle_type'])) {
                    $set('cycle_type', $preview['cycle_type']);
                }

                return;
            }

            $singlePath = $classFilePath ?: $roomFilePath;
            $preview = $detector->quickPreview($singlePath);
            $preview['partial_upload'] = true;
            $preview['missing_file'] = $classFilePath
                ? 'File PDF Jadwal Ruang belum diunggah.'
                : 'File PDF Jadwal Kelas (FET) belum diunggah.';
            $set('detected_info', $preview);

            if (! empty($preview['cycle_type'])) {
                $set('cycle_type', $preview['cycle_type']);
            }
        } catch (\Throwable $e) {
            $set('detected_info', ['error' => $e->getMessage()]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import_schedule')
                ->label('Import Jadwal (2 PDF Wajib)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => Auth::user()?->can('create', Schedule::class) ?? false)
                ->modalHeading('Import Jadwal Mengajar & Jadwal Ruang (PDF)')
                ->modalDescription('Unggah kedua dokumen PDF wajib: (1) File PDF Jadwal Kelas / Mengajar FET dan (2) File PDF Jadwal Ruang SMKN 1 Bantul. Sistem akan otomatis menggabungkan mata pelajaran, guru pengajar, ruangan, dan jam sesi.')
                ->modalSubmitActionLabel('Deteksi & Import Jadwal')
                ->form([
                    FileUpload::make('class_schedule_file')
                        ->label('1. File PDF Jadwal Kelas / Mengajar (FET)')
                        ->disk('local')
                        ->directory('imports/schedules')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/pdf',
                        ])
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $get, callable $set, SmartImportDetectorService $detector) {
                            $this->refreshScheduleDetectionPreview($get, $set, $detector);
                        })
                        ->helperText('Wajib diisi. Unggah dokumen PDF Jadwal Kelas hasil FET (contoh: JADWAL_KELAS_PUT_1.pdf).'),

                    FileUpload::make('room_schedule_file')
                        ->label('2. File PDF Jadwal Ruang')
                        ->disk('local')
                        ->directory('imports/schedules')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/pdf',
                        ])
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $get, callable $set, SmartImportDetectorService $detector) {
                            $this->refreshScheduleDetectionPreview($get, $set, $detector);
                        })
                        ->helperText('Wajib diisi. Unggah dokumen PDF Jadwal Ruang (contoh: JADWAL_RUANG_PUT_1_REVISI_SEPT.pdf).'),

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

                            $partialBanner = '';
                            if (! empty($info['partial_upload']) && ! empty($info['missing_file'])) {
                                $partialBanner = '<div class="mt-2 p-2.5 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 rounded-md text-xs text-amber-800 dark:text-amber-200 font-medium">'
                                    .'⚠️ '.e($info['missing_file']).' Kedua file PDF wajib diisi sebelum melakukan import.'
                                    .'</div>';
                            }

                            $type = $info['type'] ?? '';
                            if ($type === 'room_schedule') {
                                return new HtmlString(
                                    '<div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-900 dark:text-emerald-100 space-y-2">'
                                    .'<div class="flex items-center gap-2 font-semibold text-emerald-700 dark:text-emerald-300">'
                                    .'<span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Dokumen Terdeteksi: '.e($info['detected_format'] ?? 'PDF Jadwal Ruang')
                                    .'</div>'
                                    .'<div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300 pt-1 border-t border-emerald-100 dark:border-emerald-900">'
                                    .'<div>• <b>Tahun Ajaran:</b> '.e($info['academic_year'] ?? '-').'</div>'
                                    .'<div>• <b>Putaran:</b> '.e($info['cycle_type'] ?? 'Putaran 1').'</div>'
                                    .'<div>• <b>Total Kelas:</b> '.($info['total_classes'] ?? 0).' kelas</div>'
                                    .'<div>• <b>Total Ruangan:</b> '.($info['total_rooms'] ?? 0).' ruangan</div>'
                                    .'</div>'
                                    .$partialBanner
                                    .'</div>'
                                );
                            }

                            if ($type === 'timetable_fet') {
                                $isMerged = ! empty($info['has_room_schedule']);
                                $badgeClass = $isMerged
                                    ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100'
                                    : 'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800 text-blue-900 dark:text-blue-100';
                                $headerClass = $isMerged
                                    ? 'text-emerald-700 dark:text-emerald-300'
                                    : 'text-blue-700 dark:text-blue-300';
                                $dotClass = $isMerged ? 'bg-emerald-500' : 'bg-blue-500';

                                return new HtmlString(
                                    '<div class="p-4 '.$badgeClass.' border rounded-lg text-sm space-y-2">'
                                    .'<div class="flex items-center gap-2 font-semibold '.$headerClass.'">'
                                    .'<span class="inline-block w-2.5 h-2.5 rounded-full '.$dotClass.'"></span> Dokumen Terdeteksi: '.e($info['detected_format'] ?? 'PDF FET Timetable')
                                    .'</div>'
                                    .'<div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300 pt-1 border-t border-blue-100 dark:border-blue-900">'
                                    .'<div>• <b>Institusi:</b> '.e($info['institution'] ?? 'SMKN 1 Bantul').'</div>'
                                    .'<div>• <b>Putaran:</b> '.e($info['cycle_type'] ?? 'Putaran 1').'</div>'
                                    .'<div>• <b>Total Kelas:</b> '.($info['total_classes'] ?? 0).' kelas</div>'
                                    .'<div>• <b>Total Guru:</b> '.($info['total_teachers'] ?? 0).' guru</div>'
                                    .'<div>• <b>Mata Pelajaran:</b> '.($info['total_subjects'] ?? 0).' mapel</div>'
                                    .'<div>• <b>Total Ruangan:</b> '.($info['total_rooms'] ?? 0).' ruangan</div>'
                                    .'<div>• <b>Slot KBM:</b> '.number_format((float) ($info['total_schedules'] ?? 0)).' jadwal</div>'
                                    .'<div>• <b>Status:</b> '.($isMerged ? 'Siap diimpor (Kelas + Ruang Sinkron)' : 'Menunggu dokumen kedua').'</div>'
                                    .'</div>'
                                    .$partialBanner
                                    .'</div>'
                                );
                            }

                            return null;
                        }),

                    Select::make('cycle_type')
                        ->label('Target Putaran Jadwal')
                        ->options([
                            'Putaran 1' => 'Putaran 1',
                            'Putaran 2' => 'Putaran 2',
                        ])
                        ->default('Putaran 1')
                        ->required()
                        ->helperText('Otomatis menyesuaikan dengan putaran pada dokumen PDF yang diunggah.'),
                ])
                ->action(function (
                    array $data,
                    SmartImportDetectorService $detector
                ) {
                    $classFileRel = $data['class_schedule_file'] ?? null;
                    $roomFileRel = $data['room_schedule_file'] ?? null;

                    $classFilePath = $detector->resolveUploadedFilePath($classFileRel, 'local');
                    $roomFilePath = $detector->resolveUploadedFilePath($roomFileRel, 'local');

                    if (! $classFilePath || ! $roomFilePath) {
                        Notification::make()
                            ->title('Dokumen Belum Lengkap')
                            ->body('Kedua file PDF (Jadwal Kelas FET dan Jadwal Ruang) wajib diunggah.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $cycle = $data['cycle_type'] ?? 'Putaran 1';

                    ImportHelper::dispatch(
                        new ImportSchedulePairJob($classFilePath, $roomFilePath, Auth::user(), $cycle)
                    );

                    Notification::make()
                        ->title('Import Jadwal Sedang Diproses')
                        ->body('File PDF jadwal Anda sedang diproses di latar belakang. Anda akan menerima notifikasi setelah proses selesai.')
                        ->info()
                        ->send();
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
        $counts = once(function () {
            /** @var Builder $query */
            $query = static::getResource()::getEloquentQuery();

            return $query
                ->without(['teacher', 'classroom.major', 'classroom', 'room', 'subject', 'academicYear'])
                ->toBase()
                ->selectRaw('cycle_type, count(*) as aggregate')
                ->groupBy('cycle_type')
                ->pluck('aggregate', 'cycle_type');
        });

        $count1 = (int) ($counts['Putaran 1'] ?? 0);
        $count2 = (int) ($counts['Putaran 2'] ?? 0);
        $total = (int) $counts->sum();

        return [
            'all' => Tab::make('Semua Putaran')
                ->badge($total),
            'putaran_1' => Tab::make('Putaran 1')
                ->badge($count1)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cycle_type', 'Putaran 1')),
            'putaran_2' => Tab::make('Putaran 2')
                ->badge($count2)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cycle_type', 'Putaran 2')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }
}
