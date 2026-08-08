<?php

namespace App\Filament\Resources\Journals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal'),
                    
                TextColumn::make('teacher.full_name')
                    ->label('Guru'),

                TextColumn::make('activity_photo')
                    ->label('Bukti Foto'),

                TextColumn::make('schedule.subject_name')
                    ->label('Mata Pelajaran'),

                TextColumn::make('schedule.classroom.name')
                    ->label('Kelas'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada'),
                    
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
