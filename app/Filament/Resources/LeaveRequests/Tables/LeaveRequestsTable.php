<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use App\Models\SchoolSession;
use App\Support\PermissionHelper;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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

                TextColumn::make('leave_time')
                    ->label('Jam / Sesi Pergi')
                    ->placeholder('-')
                    ->formatStateUsing(function (?string $state, LeaveRequest $record) {
                        if (! $state) {
                            return '-';
                        }
                        $timeStr = substr((string) $state, 0, 5);
                        try {
                            $day = $record->start_date ? Carbon::parse($record->start_date)->locale('id')->isoFormat('dddd') : 'Senin';
                            $sessions = SchoolSession::getSessionsForDay($day);
                            foreach ($sessions as $session) {
                                if (substr((string) $session['start'], 0, 5) === $timeStr) {
                                    return "{$session['label']} ({$timeStr})";
                                }
                            }
                        } catch (\Throwable $e) {
                        }

                        return $timeStr;
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('reason_type')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower(str_replace(' ', '_', $state))) {
                        'sakit' => 'warning',
                        'izin' => 'info',
                        'pulang_cepat' => 'danger',
                        'kegiatan_luar' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match (strtolower(str_replace(' ', '_', $state))) {
                        'sakit' => 'Sakit',
                        'izin' => 'Izin',
                        'pulang_cepat' => 'Pulang Cepat',
                        'kegiatan_luar' => 'Kegiatan Luar',
                        default => $state,
                    })
                    ->label('Jenis'),

                ImageColumn::make('attachment_photo')
                    ->label('Bukti Foto')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(null)
                    ->toggleable(),

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

                        if (PermissionHelper::exists('approve_leave_request')) {
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

                        if (PermissionHelper::exists('approve_leave_request')) {
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
