<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->searchable()
                    ->label('Nama Siswa'),

                TextColumn::make('start_date')
                    ->date()
                    ->label('Mulai'),

                TextColumn::make('end_date')
                    ->date()
                    ->label('Selesai'),

                TextColumn::make('reason_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sakit' => 'warning',
                        'Izin' => 'info',
                        default => 'gray',
                    })
                    ->label('Jenis'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),

                TextColumn::make('rejection_note')
                    ->label('Keterangan Ditolak')
                    ->placeholder('-')
                    ->limit(40)
                    ->tooltip(fn (LeaveRequest $record): ?string => $record->rejection_note)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('verifiedBy.full_name')
                    ->label('Diverifikasi Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Izin')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui pengajuan izin ini? Presensi pada jurnal terkait akan otomatis tersinkronisasi.')
                    ->modalSubmitActionLabel('Setujui')
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status' => 'Approved',
                            'rejection_note' => null,
                            'verified_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Pengajuan Izin Disetujui')
                            ->body('Pengajuan izin siswa berhasil disetujui.')
                            ->success()
                            ->send();
                    })
                    ->visible(function (LeaveRequest $record): bool {
                        if ($record->status !== 'Pending' || $record->submitted_by === Auth::id() || $record->student_id === Auth::id()) {
                            return false;
                        }

                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
                            return true;
                        }

                        if (Permission::where('name', 'approve_leave_request')->exists()) {
                            return $user->can('approve_leave_request');
                        }

                        return $user->hasAnyRole(['Wali Kelas', 'Guru BK']) || in_array($user->role?->name, ['Wali Kelas', 'Guru BK']);
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->modalHeading('Tolak Pengajuan Izin')
                    ->modalDescription('Silakan masukkan keterangan atau alasan penolakan sebelum mereject pengajuan izin ini.')
                    ->modalSubmitActionLabel('Tolak Izin')
                    ->form([
                        Textarea::make('rejection_note')
                            ->label('Keterangan Reject')
                            ->placeholder('Contoh: Dokumen surat sakit tidak jelas / izin tidak valid')
                            ->required()
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'Rejected',
                            'rejection_note' => $data['rejection_note'],
                            'verified_by' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title('Pengajuan Izin Ditolak')
                            ->body('Pengajuan izin siswa berhasil ditolak dengan keterangan.')
                            ->danger()
                            ->send();
                    })
                    ->visible(function (LeaveRequest $record): bool {
                        if ($record->status !== 'Pending' || $record->submitted_by === Auth::id() || $record->student_id === Auth::id()) {
                            return false;
                        }

                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }

                        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
                            return true;
                        }

                        if (Permission::where('name', 'approve_leave_request')->exists()) {
                            return $user->can('approve_leave_request');
                        }

                        return $user->hasAnyRole(['Wali Kelas', 'Guru BK']) || in_array($user->role?->name, ['Wali Kelas', 'Guru BK']);
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
