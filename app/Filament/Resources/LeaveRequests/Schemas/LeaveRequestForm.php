<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Enums\ReasonTypeEnum;
use App\Models\LeaveRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
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
                    ->options(ReasonTypeEnum::class)
                    ->required()
                    ->native(false)
                    ->label('Alasan')
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $type = $get('reason_type');

                        $isPulangCepatOrKegiatanLuar = $type === ReasonTypeEnum::PULANG_CEPAT
                            || $type === ReasonTypeEnum::PULANG_CEPAT->value
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR->value;

                        $isSakitOrIzin = $type === ReasonTypeEnum::SAKIT
                            || $type === ReasonTypeEnum::SAKIT->value
                            || $type === ReasonTypeEnum::IZIN
                            || $type === ReasonTypeEnum::IZIN->value;

                        if ($isPulangCepatOrKegiatanLuar) {
                            $set('start_date', now()->toDateString());
                            $set('end_date', now()->toDateString());
                        } elseif ($isSakitOrIzin) {
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

                        return $type === ReasonTypeEnum::PULANG_CEPAT
                            || $type === ReasonTypeEnum::PULANG_CEPAT->value
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR->value;
                    })
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');

                        return $type === ReasonTypeEnum::SAKIT
                            || $type === ReasonTypeEnum::SAKIT->value
                            || $type === ReasonTypeEnum::IZIN
                            || $type === ReasonTypeEnum::IZIN->value
                            || $type === ReasonTypeEnum::PULANG_CEPAT
                            || $type === ReasonTypeEnum::PULANG_CEPAT->value
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR->value;
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

                        return $type === ReasonTypeEnum::SAKIT
                            || $type === ReasonTypeEnum::SAKIT->value
                            || $type === ReasonTypeEnum::IZIN
                            || $type === ReasonTypeEnum::IZIN->value;
                    })
                    ->label('Tanggal Selesai'),

                TimePicker::make('leave_time')
                    ->required()
                    ->live()
                    ->seconds(false)
                    ->displayFormat('H:i')
                    ->format('H:i')
                    ->native(false)
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');

                        return $type === ReasonTypeEnum::PULANG_CEPAT
                            || $type === ReasonTypeEnum::PULANG_CEPAT->value
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR
                            || $type === ReasonTypeEnum::KEGIATAN_LUAR->value;
                    })
                    ->label('Jam Pergi'),

                Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->label('Keterangan Alasan'),

                FileUpload::make('attachment_photo')
                    ->label('Lampiran / Surat Dokter')
                    ->disk('public')
                    ->directory('leave_attachments')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'])
                    ->openable()
                    ->downloadable(),

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
