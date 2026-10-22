<?php

namespace App\Filament\Resources\Journals\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Siswa'),

                SelectColumn::make('status')
                    ->label('Status Kehadiran')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                    ])
                    ->selectablePlaceholder(false)
                    ->rules(['required', 'in:Hadir,Sakit,Izin,Alpa']),

                TextInputColumn::make('notes')
                    ->label('Catatan / Keterangan')
                    ->placeholder('Tambahkan catatan...'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Ubah Status Presensi')
                    ->modalWidth('md'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markHadir')
                        ->label('Set Hadir (Terpilih)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Ubah Presensi ke Hadir')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status presensi seluruh siswa yang dipilih menjadi Hadir?')
                        ->modalSubmitActionLabel('Ya, Set Hadir')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'Hadir'])),

                    BulkAction::make('markSakit')
                        ->label('Set Sakit (Terpilih)')
                        ->icon('heroicon-o-plus-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Ubah Presensi ke Sakit')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status presensi seluruh siswa yang dipilih menjadi Sakit?')
                        ->modalSubmitActionLabel('Ya, Set Sakit')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'Sakit'])),

                    BulkAction::make('markIzin')
                        ->label('Set Izin (Terpilih)')
                        ->icon('heroicon-o-information-circle')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Ubah Presensi ke Izin')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status presensi seluruh siswa yang dipilih menjadi Izin?')
                        ->modalSubmitActionLabel('Ya, Set Izin')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'Izin'])),

                    BulkAction::make('markAlpa')
                        ->label('Set Alpa (Terpilih)')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Ubah Presensi ke Alpa')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status presensi seluruh siswa yang dipilih menjadi Alpa?')
                        ->modalSubmitActionLabel('Ya, Set Alpa')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'Alpa'])),
                ]),
            ]);
    }
}
