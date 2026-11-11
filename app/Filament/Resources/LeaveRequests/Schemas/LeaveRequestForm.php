<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Enums\ReasonTypeEnum;
use App\Filament\Forms\Components\RealtimePhotoCapture;
use App\Models\LeaveRequest;
use App\Models\SchoolSession;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $role = $user?->role?->name;

        return $schema
            ->components([
                Select::make('student_id')
                    ->relationship('student', 'full_name', fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Nama Siswa')
                    ->default(fn () => $role === 'Siswa' ? $user?->id : null)
                    ->disabled(fn () => $role === 'Siswa')
                    ->dehydrated(),

                Hidden::make('submitted_by')
                    ->default(fn () => Auth::id()),

                Select::make('reason_type')
                    ->options([
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'pulang_cepat' => 'Izin Pulang Cepat',
                        'kegiatan_luar' => 'Izin Kegiatan Luar',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Pulang Cepat' => 'Izin Pulang Cepat',
                        'Kegiatan Luar' => 'Izin Kegiatan Luar',
                    ])
                    ->formatStateUsing(function ($state, ?Model $record = null) {
                        $raw = $state ?? $record?->reason_type;
                        if (! $raw) {
                            return null;
                        }

                        if ($raw instanceof ReasonTypeEnum) {
                            return $raw->value;
                        }

                        $str = strtolower(str_replace(' ', '_', (string) $raw));

                        return in_array($str, ['sakit', 'izin', 'pulang_cepat', 'kegiatan_luar'], true) ? $str : (string) $raw;
                    })
                    ->dehydrateStateUsing(function ($state) {
                        return $state;
                    })
                    ->required()
                    ->native(false)
                    ->label('Alasan')
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $type = $get('reason_type');
                        $typeKey = $type instanceof ReasonTypeEnum ? $type->value : (is_string($type) ? strtolower(str_replace(' ', '_', $type)) : null);

                        $isPulangCepatOrKegiatanLuar = in_array($typeKey, ['pulang_cepat', 'kegiatan_luar'], true);
                        $isSakitOrIzin = in_array($typeKey, ['sakit', 'izin'], true);

                        if ($isPulangCepatOrKegiatanLuar) {
                            $set('start_date', now()->toDateString());
                            $set('end_date', now()->toDateString());
                        } elseif ($isSakitOrIzin) {
                            $set('leave_time', null);
                            if (! $get('start_date')) {
                                $set('start_date', now()->toDateString());
                            }
                            if (! $get('end_date')) {
                                $set('end_date', now()->toDateString());
                            }
                        }
                    }),

                DatePicker::make('start_date')
                    ->required()
                    ->live()
                    ->default(now()->toDateString())
                    ->minDate(function (?LeaveRequest $record = null) {
                        if ($record && $record->start_date) {
                            $existing = Carbon::parse($record->start_date)->startOfDay();

                            return $existing->isPast() ? $existing->toDateString() : now()->toDateString();
                        }

                        return now()->toDateString();
                    })
                    ->validationMessages([
                        'after_or_equal' => 'Tanggal mulai minimal mengisi hari ini.',
                    ])
                    ->readOnly(function (Get $get) {
                        $type = $get('reason_type');
                        $typeKey = $type instanceof ReasonTypeEnum ? $type->value : (is_string($type) ? strtolower(str_replace(' ', '_', $type)) : null);

                        return in_array($typeKey, ['pulang_cepat', 'kegiatan_luar'], true);
                    })
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');
                        $typeKey = $type instanceof ReasonTypeEnum ? $type->value : (is_string($type) ? strtolower(str_replace(' ', '_', $type)) : null);

                        return in_array($typeKey, ['sakit', 'izin', 'pulang_cepat', 'kegiatan_luar'], true);
                    })
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state && $get('end_date') && $get('end_date') < $state) {
                            $set('end_date', $state);
                        }
                    })
                    ->label('Tanggal Mulai'),

                DatePicker::make('end_date')
                    ->required()
                    ->live()
                    ->default(now()->toDateString())
                    ->minDate(function (Get $get, ?LeaveRequest $record = null) {
                        if ($startDate = $get('start_date')) {
                            return $startDate;
                        }

                        if ($record && $record->end_date) {
                            $existing = Carbon::parse($record->end_date)->startOfDay();

                            return $existing->isPast() ? $existing->toDateString() : now()->toDateString();
                        }

                        return now()->toDateString();
                    })
                    ->afterOrEqual('start_date')
                    ->validationMessages([
                        'after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
                    ])
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');
                        $typeKey = $type instanceof ReasonTypeEnum ? $type->value : (is_string($type) ? strtolower(str_replace(' ', '_', $type)) : null);

                        return in_array($typeKey, ['sakit', 'izin'], true);
                    })
                    ->label('Tanggal Selesai'),

                Select::make('leave_time')
                    ->label('Jam Pergi')
                    ->placeholder('Pilih sesi jam pergi...')
                    ->required()
                    ->live()
                    ->native(false)
                    ->searchable()
                    ->options(function (Get $get, ?string $state) {
                        $startDate = $get('start_date') ?: now()->toDateString();
                        try {
                            $dayOfWeek = Carbon::parse($startDate)->locale('id')->isoFormat('dddd');
                        } catch (\Throwable $e) {
                            $dayOfWeek = 'Senin';
                        }

                        $sessions = SchoolSession::getSessionsForDay($dayOfWeek);
                        $options = [];
                        foreach ($sessions as $num => $session) {
                            $start = substr((string) $session['start'], 0, 5);
                            $end = substr((string) $session['end'], 0, 5);
                            $options[$start] = "{$session['label']} ({$start} - {$end})";
                        }

                        if ($state) {
                            $key = substr((string) $state, 0, 5);
                            if (! isset($options[$key])) {
                                $options[$key] = "Waktu Kustom ({$key})";
                            }
                        }

                        return $options;
                    })
                    ->formatStateUsing(function (?string $state) {
                        return $state ? substr((string) $state, 0, 5) : null;
                    })
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');
                        $typeKey = $type instanceof ReasonTypeEnum ? $type->value : (is_string($type) ? strtolower(str_replace(' ', '_', $type)) : null);

                        return in_array($typeKey, ['pulang_cepat', 'kegiatan_luar'], true);
                    })
                    ->helperText('Pilih sesi jam pelajaran saat siswa mulai izin meninggalkan sekolah.'),

                Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->label('Keterangan Alasan'),

                RealtimePhotoCapture::make('attachment_photo')
                    ->label('Lampiran Foto Surat Dokter / Bukti Izin (Realtime)')
                    ->disk('public')
                    ->directory('leave_attachments')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->allowPdf(true)
                    ->enableWatermark(false)
                    ->defaultFacingMode('environment')
                    ->captureType('leave_request')
                    ->columnSpanFull()
                    ->helperText('Ambil foto surat dokter/bukti izin secara langsung menggunakan kamera perangkat (kamera depan/belakang). Foto wajib diambil secara realtime.')
                    ->required()
                    ->validationMessages([
                        'required' => 'Foto bukti / surat perizinan wajib diambil secara realtime.',
                    ]),

                Select::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state !== 'Rejected') {
                            $set('rejection_note', null);
                        }
                    })
                    ->disabled(fn () => ! (Auth::user()?->can('approve_leave_request') || Auth::user()?->hasAnyRole(['Wali Kelas', 'Guru BK', 'Super Admin']) || in_array(Auth::user()?->role?->name, ['Wali Kelas', 'Guru BK', 'Super Admin'])))
                    ->dehydrated()
                    ->label('Status Verifikasi'),

                Textarea::make('rejection_note')
                    ->label('Keterangan Penolakan (Reject)')
                    ->placeholder('Masukkan keterangan/alasan penolakan izin siswa...')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->visible(fn (Get $get, ?LeaveRequest $record) => $get('status') === 'Rejected' || ($record && $record->status === 'Rejected'))
                    ->required(fn (Get $get) => $get('status') === 'Rejected')
                    ->disabled(fn () => ! (Auth::user()?->can('approve_leave_request') || Auth::user()?->hasAnyRole(['Wali Kelas', 'Guru BK', 'Super Admin']) || in_array(Auth::user()?->role?->name, ['Wali Kelas', 'Guru BK', 'Super Admin'])))
                    ->dehydrated(fn (Get $get) => $get('status') === 'Rejected'),

                Select::make('verified_by')
                    ->relationship('verifiedBy', 'full_name')
                    ->label('Diverifikasi Oleh')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?LeaveRequest $record) => (bool) $record?->verified_by),
            ]);
    }
}
