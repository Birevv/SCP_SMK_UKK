<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                    Tables\Columns\TextColumn::make('classroom.name')
                        ->label('Kelas')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('full_name')
                        ->label('Nama Lengkap')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('username')
                        ->label('Username')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('email')
                        ->label('Email')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Dibuat Pada')
                        ->dateTime('d F Y H:i')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('updated_at')
                        ->label('Diperbarui Pada')
                        ->dateTime('d F Y H:i')
                        ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
           
    }
}
