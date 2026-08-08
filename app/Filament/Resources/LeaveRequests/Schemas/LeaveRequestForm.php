<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                    ->disabled(fn () => $role === 'Siswa'),

                Hidden::make('submitted_by')
                    ->default(Auth::id()),

                DatePicker::make('start_date')
                    ->required()
                    ->label('Tanggal Mulai'),

                DatePicker::make('end_date')
                    ->required()
                    ->afterOrEqual('start_date')
                    ->label('Tanggal Selesai'),

                Select::make('reason_type')
                    ->options([
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                    ])
                    ->required()
                    ->label('Alasan'),
                
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
