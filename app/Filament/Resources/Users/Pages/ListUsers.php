<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\DocumentParserService;
use App\Services\SmartImportDetectorService;
use App\Services\UserImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import_users')
                ->label('Import Pengguna (Excel / PDF / CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Data Pengguna Otomatis')
                ->modalDescription('Unggah dokumen Excel daftar hadir siswa, PDF jadwal FET, atau CSV pengguna. Sistem akan mendeteksi format dan mendaftarkan siswa/guru secara otomatis.')
                ->modalSubmitActionLabel('Mulai Import')
                ->form([
                    FileUpload::make('file')
                        ->label('Drop File Excel / PDF / CSV Di Sini')
                        ->disk('local')
                        ->directory('imports/users')
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
                        ->helperText('Mendukung: 1) Excel Daftar Hadir Siswa SMKN 1 Bantul, 2) PDF Jadwal FET (import Guru), atau 3) CSV Pengguna umum.'),
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
                                    .'<div>• <b>Status:</b> Siap didaftarkan sebagai User (Role: Siswa)</div>'
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
                                    .'<div>• <b>Total Guru:</b> '.($info['total_teachers'] ?? 0).' guru</div>'
                                    .'<div>• <b>Status:</b> Siap didaftarkan sebagai User (Role: Guru)</div>'
                                    .'</div>'
                                    .'</div>'
                                );
                            }

                            return null;
                        }),
                ])
                ->action(function (
                    array $data,
                    SmartImportDetectorService $detector,
                    DocumentParserService $parser,
                    UserImportService $importer
                ) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    try {
                        $parsed = $detector->detectAndParse($filePath);

                        if ($parsed['type'] === 'student_roster' || $parsed['type'] === 'timetable_fet') {
                            $result = $detector->import($parsed, Auth::user());
                            @unlink($filePath);

                            $formatName = $parsed['detected_format'] ?? $parsed['type'];
                            Notification::make()
                                ->title("Import Pengguna Berhasil: {$formatName}")
                                ->body($result['message'])
                                ->success()
                                ->send();

                            return;
                        }
                    } catch (\Throwable $e) {
                        // fallback to flat parser
                    }

                    $rows = $parser->parse($filePath);

                    if (empty($rows)) {
                        Notification::make()
                            ->title('Gagal Membaca File')
                            ->body('Tidak ditemukan data pengguna yang valid pada file tersebut.')
                            ->danger()
                            ->send();

                        @unlink($filePath);

                        return;
                    }

                    $result = $importer->import($rows, Auth::user());
                    @unlink($filePath);

                    $body = "Berhasil memproses: {$result['imported']} user baru ditambahkan, {$result['updated']} user diperbarui.";
                    if ($result['skipped'] > 0) {
                        $body .= " {$result['skipped']} baris dilewati.";
                    }

                    if ($result['imported'] > 0 || $result['updated'] > 0) {
                        Notification::make()
                            ->title('Import Pengguna Berhasil')
                            ->body($body)
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import Pengguna Belum Berhasil')
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
            Action::make('download_user_template')
                ->label('Template CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    return response()->streamDownload(function () {
                        echo DocumentParserService::getUserCsvTemplate();
                    }, 'template_import_pengguna.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Role'),
            'waka' => Tab::make('Waka Kurikulum')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Waka Kurikulum'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Waka Kurikulum'))),
            'guru' => Tab::make('Guru')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Guru'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru'))),
            'wali_kelas' => Tab::make('Wali Kelas')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Wali Kelas'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Wali Kelas'))),
            'bk' => Tab::make('Guru BK')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Guru BK'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru BK'))),
            'siswa' => Tab::make('Siswa')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))),
        ];
    }
}
