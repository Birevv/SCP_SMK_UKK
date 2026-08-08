<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
            ])

            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'status' => 'Approved',
                        'verified_by' => Auth::id(),
                    ]))
                    ->visible(fn (LeaveRequest $record): bool => 
                        $record->status === 'Pending' &&
                        in_array(Auth::user()?->role?->name, ['Wali Kelas', 'Guru BK', 'Waka Kurikulum']) &&
                        $record->submitted_by !== Auth::id() &&
                        $record->student_id !== Auth::id()
                    ),

                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'status' => 'Rejected',
                        'verified_by' => Auth::id(),
                    ]))
                    ->visible(fn (LeaveRequest $record): bool => 
                        $record->status === 'Pending' &&
                        in_array(Auth::user()?->role?->name, ['Wali Kelas', 'Guru BK', 'Waka Kurikulum']) &&
                        $record->submitted_by !== Auth::id() &&
                        $record->student_id !== Auth::id()
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
