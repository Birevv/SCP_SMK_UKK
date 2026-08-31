<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Enums\ReasonTypeEnum;
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
                    ->relationship('student', 'full_name', fn (Builder $query) =>
                        $query->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
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

                        if ($type == ReasonTypeEnum::PULANG_CEPAT || $type == ReasonTypeEnum::KEGIATAN_LUAR) {
                            $set('start_date', now());
                            $set('end_date', now());
                        }
                    }),

                DatePicker::make('start_date')
                    ->required()
                    ->live()
                    ->readOnly(function (Get $get) {
                        $type = $get('reason_type');

                        return $type == ReasonTypeEnum::PULANG_CEPAT || $type == ReasonTypeEnum::KEGIATAN_LUAR;
                    })
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');

                        if ($type == ReasonTypeEnum::SAKIT || $type == ReasonTypeEnum::IZIN || $type == ReasonTypeEnum::PULANG_CEPAT || $type == ReasonTypeEnum::KEGIATAN_LUAR) {
                            return true;
                        }

                        return false;
                    })
                    ->label('Tanggal Mulai'),

                DatePicker::make('end_date')
                    ->required()
                    ->live()
                    ->visible(function (Get $get) {
                        $type = $get('reason_type');

                        if ($type == ReasonTypeEnum::SAKIT || $type == ReasonTypeEnum::IZIN) {
                            return true;
                        }

                        return false;
                    })
                    ->afterOrEqual('start_date')
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

                        if ($type == ReasonTypeEnum::PULANG_CEPAT || $type == ReasonTypeEnum::KEGIATAN_LUAR) {
                            return true;
                        }

                        return false;
                    })
                    ->label('Jam Pergi'),

                Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->label('Keterangan Alasan'),

                FileUpload::make('attachment_photo')
                    ->image()
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                    ->directory('leave_attachments')
                    ->label('Lampiran / Surat Dokter'),

                Select::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->required()
                    ->disabled(fn () => !in_array($role, ['Wali Kelas', 'Guru BK', 'Waka Kurikulum']))
                    ->dehydrated()
                    ->label('Status Verifikasi'),
            ]);
    }
}
