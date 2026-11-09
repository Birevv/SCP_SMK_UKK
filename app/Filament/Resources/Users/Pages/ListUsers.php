<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Jobs\ImportUserFilesJob;
use App\Models\User;
use App\Services\DocumentParserService;
use App\Services\SmartImportDetectorService;
use App\Support\ImportHelper;
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
            CreateAction::make()
                ->successNotificationTitle('Data berhasil diinput'),
            Action::make('import_users')
                ->label('Import Pengguna (3 File)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Data Pengguna (3 File: Wali Kelas, Guru & Siswa)')
                ->modalDescription('Alur import pengguna: Unggah 1) File Wali Kelas & Guru BK, 2) File Data Pegawai Guru (ekstrak NIP & guru baru), dan 3) File Siswa (Daftar Hadir). Data TU & outsourcing pada file pegawai akan diabaikan secara otomatis.')
                ->modalSubmitActionLabel('Mulai Import Pengguna (3 File)')
                ->form([
                    FileUpload::make('file_walikelas')
                        ->label('1. File Wali Kelas & Guru BK (Excel / CSV / PDF)')
                        ->disk('local')
                        ->directory('imports/users')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                            'application/pdf',
                        ])
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $get, callable $set, SmartImportDetectorService $detector) {
                            $this->refreshUserDetectionPreview($get, $set, $detector);
                        })
                        ->helperText('Wajib diisi. File pembagian Wali Kelas dan Guru BK (Wali BK) per rombel.'),

                    FileUpload::make('file_guru')
                        ->label('2. File Data Pegawai / Guru (NIP) (Excel / CSV)')
                        ->disk('local')
                        ->directory('imports/users')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $get, callable $set, SmartImportDetectorService $detector) {
                            $this->refreshUserDetectionPreview($get, $set, $detector);
                        })
                        ->helperText('Wajib diisi. File Data Nominatif Pegawai 2026. Melengkapi NIP guru dan menambahkan guru yang belum terimport (data TU & outsourcing otomatis diabaikan).'),

                    FileUpload::make('file_siswa')
                        ->label('3. File Data Siswa / Rombel (Excel / CSV)')
                        ->disk('local')
                        ->directory('imports/users')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->required()
                        ->live(debounce: 500)
                        ->afterStateUpdated(function ($state, callable $get, callable $set, SmartImportDetectorService $detector) {
                            $this->refreshUserDetectionPreview($get, $set, $detector);
                        })
                        ->helperText('Wajib diisi. Dokumen Excel/CSV Daftar Hadir Siswa per kelas.'),

                    Placeholder::make('detected_preview')
                        ->label('Hasil Deteksi 3 File')
                        ->visible(fn ($get) => filled($get('detected_info')))
                        ->content(function ($get) {
                            $info = $get('detected_info');
                            if (! $info) {
                                return null;
                            }

                            $html = '<div class="space-y-3">';

                            // 1. Wali Kelas & Guru BK card
                            $wali = $info['walikelas'] ?? null;
                            if ($wali) {
                                if (isset($wali['error'])) {
                                    $html .= '<div class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-xs"><b>1. File Wali Kelas:</b> '.e($wali['error']).'</div>';
                                } else {
                                    $html .= '<div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 rounded-lg text-xs text-indigo-900 dark:text-indigo-100 space-y-1">'
                                        .'<div class="font-semibold text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-500 inline-block"></span> 1. File Wali Kelas & Guru BK: '.e($wali['detected_format'] ?? 'Terdeteksi').'</div>'
                                        .'<div class="grid grid-cols-3 gap-1 pt-1 text-gray-600 dark:text-gray-300">'
                                        .'<div>• Rombel: <b>'.($wali['total_classes'] ?? 0).'</b></div>'
                                        .'<div>• Wali Kelas: <b>'.($wali['total_homeroom'] ?? 0).' guru</b></div>'
                                        .'<div>• Guru BK: <b>'.($wali['total_counselor'] ?? 0).' guru</b></div>'
                                        .'</div></div>';
                                }
                            } else {
                                $html .= '<div class="p-2.5 bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">'
                                    .'<span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span> 1. File Wali Kelas: Menunggu file diunggah...'
                                    .'</div>';
                            }

                            // 2. Data Guru (NIP) card
                            $guru = $info['guru'] ?? null;
                            if ($guru) {
                                if (isset($guru['error'])) {
                                    $html .= '<div class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-xs"><b>2. File Data Guru:</b> '.e($guru['error']).'</div>';
                                } else {
                                    $html .= '<div class="p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg text-xs text-blue-900 dark:text-blue-100 space-y-1">'
                                        .'<div class="font-semibold text-blue-700 dark:text-blue-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span> 2. File Data Guru (NIP): '.e($guru['detected_format'] ?? 'Terdeteksi').'</div>'
                                        .'<div class="pt-1 text-gray-600 dark:text-gray-300 flex items-center justify-between">'
                                        .'<div>• Total Guru: <b>'.($guru['total_teachers'] ?? 0).' guru</b> (NIP akan disinkronkan)</div>'
                                        .'<div class="text-[11px] text-blue-600 dark:text-blue-400 font-medium">TU & Outsourcing diabaikan otomatis</div>'
                                        .'</div></div>';
                                }
                            } else {
                                $html .= '<div class="p-2.5 bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">'
                                    .'<span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span> 2. File Data Guru: Menunggu file diunggah...'
                                    .'</div>';
                            }

                            // 3. Data Siswa card
                            $siswa = $info['siswa'] ?? null;
                            if ($siswa) {
                                if (isset($siswa['error'])) {
                                    $html .= '<div class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-xs"><b>3. File Siswa:</b> '.e($siswa['error']).'</div>';
                                } else {
                                    $html .= '<div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-900 dark:text-emerald-100 space-y-1">'
                                        .'<div class="font-semibold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> 3. File Siswa: '.e($siswa['detected_format'] ?? 'Terdeteksi').'</div>'
                                        .'<div class="grid grid-cols-2 gap-1 pt-1 text-gray-600 dark:text-gray-300">'
                                        .'<div>• Total Siswa: <b>'.number_format((float) ($siswa['total_students'] ?? 0)).' siswa</b></div>'
                                        .'<div>• Total Rombel: <b>'.($siswa['total_classes'] ?? 0).' kelas</b></div>'
                                        .'</div></div>';
                                }
                            } else {
                                $html .= '<div class="p-2.5 bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">'
                                    .'<span class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span> 3. File Siswa: Menunggu file diunggah...'
                                    .'</div>';
                            }

                            $html .= '</div>';

                            return new HtmlString($html);
                        }),
                ])
                ->action(function (
                    array $data,
                ) {
                    $waliFile = $data['file_walikelas'] ?? null;
                    $guruFile = $data['file_guru'] ?? null;
                    $siswaFile = $data['file_siswa'] ?? null;

                    $waliPath = $waliFile ? Storage::disk('local')->path($waliFile) : null;
                    $guruPath = $guruFile ? Storage::disk('local')->path($guruFile) : null;
                    $siswaPath = $siswaFile ? Storage::disk('local')->path($siswaFile) : null;

                    if (! $guruPath && ! $waliPath && ! $siswaPath) {
                        Notification::make()
                            ->title('Tidak Ada File')
                            ->body('Pilih minimal satu file dokumen untuk diimpor.')
                            ->warning()
                            ->send();

                        return;
                    }

                    ImportHelper::dispatch(
                        new ImportUserFilesJob($guruPath, $waliPath, $siswaPath, Auth::user())
                    );

                    Notification::make()
                        ->title('Import Pengguna Sedang Diproses')
                        ->body('Data pengguna sedang diproses di latar belakang. Anda akan menerima notifikasi setelah proses selesai.')
                        ->info()
                        ->send();
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
            'all' => Tab::make('Semua'),
            'waka' => Tab::make('Waka Kurikulum')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Waka Kurikulum'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Waka Kurikulum'))),
            'guru' => Tab::make('Guru')
                ->badge(fn () => User::where(fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'Guru'))->orWhereHas('roles', fn ($r) => $r->where('name', 'Guru')))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'Guru'))->orWhereHas('roles', fn ($r) => $r->where('name', 'Guru')))),
            'wali_kelas' => Tab::make('Wali Kelas')
                ->badge(fn () => User::where(fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'Wali Kelas'))->orWhereHas('roles', fn ($r) => $r->where('name', 'Wali Kelas')))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'Wali Kelas'))->orWhereHas('roles', fn ($r) => $r->where('name', 'Wali Kelas')))),
            'bk' => Tab::make('Guru BK')
                ->badge(fn () => User::where(fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'Guru BK'))->orWhereHas('roles', fn ($r) => $r->where('name', 'Guru BK')))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'Guru BK'))->orWhereHas('roles', fn ($r) => $r->where('name', 'Guru BK')))),
            'siswa_aktif' => Tab::make('Siswa (Aktif)')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))->where('status', 'Aktif')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))->where('status', 'Aktif')),
            'alumni' => Tab::make('Alumni')
                ->badge(fn () => User::where('status', 'Lulus')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Lulus')),
        ];
    }

    protected function refreshUserDetectionPreview(callable $get, callable $set, SmartImportDetectorService $detector): void
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $waliState = $get('file_walikelas');
        $guruState = $get('file_guru');
        $siswaState = $get('file_siswa');

        $waliPath = $detector->resolveUploadedFilePath($waliState, 'local');
        $guruPath = $detector->resolveUploadedFilePath($guruState, 'local');
        $siswaPath = $detector->resolveUploadedFilePath($siswaState, 'local');

        if (! $waliPath && ! $guruPath && ! $siswaPath) {
            $set('detected_info', null);

            return;
        }

        $info = [
            'walikelas' => null,
            'guru' => null,
            'siswa' => null,
        ];

        try {
            if ($waliPath && file_exists($waliPath)) {
                $info['walikelas'] = $detector->quickPreview($waliPath);
            }
        } catch (\Throwable $e) {
            $info['walikelas'] = ['error' => $e->getMessage()];
        }

        try {
            if ($guruPath && file_exists($guruPath)) {
                $info['guru'] = $detector->quickPreview($guruPath);
            }
        } catch (\Throwable $e) {
            $info['guru'] = ['error' => $e->getMessage()];
        }

        try {
            if ($siswaPath && file_exists($siswaPath)) {
                $info['siswa'] = $detector->quickPreview($siswaPath);
            }
        } catch (\Throwable $e) {
            $info['siswa'] = ['error' => $e->getMessage()];
        }

        $set('detected_info', $info);
    }
}
