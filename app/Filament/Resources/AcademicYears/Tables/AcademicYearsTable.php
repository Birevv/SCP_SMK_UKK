<?php

namespace App\Filament\Resources\AcademicYears\Tables;

use App\Models\AcademicYear;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('setActive')
                    ->label('Set Aktif')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AcademicYear $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan Tahun Ajaran')
                    ->modalDescription(fn (AcademicYear $record) => "Aktifkan {$record->name}? Tahun ajaran lain akan dinonaktifkan dan kolom Tahun Ajaran di tabel Jadwal serta data kelas akan otomatis diperbarui.")
                    ->action(function (AcademicYear $record) {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('Tahun Ajaran Diaktifkan')
                            ->body("{$record->name} sekarang menjadi tahun ajaran aktif di seluruh sistem.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
