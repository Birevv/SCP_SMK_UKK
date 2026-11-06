<?php

declare(strict_types=1);

namespace App\Filament\Resources\Classrooms\Pages;

use App\Filament\Resources\Classrooms\ClassroomResource;
use App\Models\Classroom;
use App\Services\SmartImportDetectorService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ListClassrooms extends ListRecords
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import_smart')
                ->label('Import Cerdas (Excel/PDF)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => Auth::user()?->can('create', Classroom::class) ?? false)
                ->modalHeading('Import Data Kelas & Siswa Otomatis')
                ->modalDescription('Unggah dokumen Excel daftar hadir siswa atau PDF jadwal FET. Sistem akan mendeteksi seluruh kelas dan siswa secara otomatis.')
                ->modalSubmitActionLabel('Deteksi & Import')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel / PDF')
                        ->disk('local')
                        ->directory('imports/classrooms')
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
                            } catch (\Throwable $e) {
                                $set('detected_info', ['error' => $e->getMessage()]);
                            }
                        })
                        ->helperText('Mendukung: Excel Daftar Hadir Siswa atau PDF Jadwal FET SMKN 1 Bantul.'),
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
                                    '<div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 rounded-lg text-amber-800 text-sm flex items-center gap-2">'
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
                                    .'<div>• <b>Total Kelas:</b> '.($info['total_classes'] ?? 0).' kelas</div>'
                                    .'<div>• <b>Total Guru:</b> '.($info['total_teachers'] ?? 0).' guru</div>'
                                    .'<div>• <b>Slot KBM:</b> '.number_format((float) ($info['total_schedules'] ?? 0)).' jadwal</div>'
                                    .'</div>'
                                    .'</div>'
                                );
                            }

                            return null;
                        }),
                ])
                ->action(function (array $data, SmartImportDetectorService $detector) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    try {
                        $parsed = $detector->detectAndParse($filePath);
                        $result = $detector->import($parsed, Auth::user());
                        @unlink($filePath);

                        $formatName = $parsed['detected_format'] ?? $parsed['type'];
                        Notification::make()
                            ->title("Import Berhasil: {$formatName}")
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        @unlink($filePath);
                        Notification::make()
                            ->title('Gagal Memproses Dokumen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
