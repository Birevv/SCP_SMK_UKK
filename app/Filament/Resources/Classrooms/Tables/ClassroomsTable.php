<?php

namespace App\Filament\Resources\Classrooms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassroomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('classLevel.name')
                    ->label('Tingkat')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('major.code')
                    ->label('Jurusan')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('rombel.name')
                    ->label('Rombel')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('class_level_id')
                    ->label('Tingkat Kelas')
                    ->relationship('classLevel', 'name'),

                SelectFilter::make('major_id')
                    ->label('Jurusan')
                    ->relationship('major', 'code'),

                SelectFilter::make('rombel_id')
                    ->label('Rombel')
                    ->relationship('rombel', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
