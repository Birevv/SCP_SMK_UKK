<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role Akses')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Waka Kurikulum' => 'danger',
                        'Guru' => 'info',
                        'Wali Kelas' => 'warning',
                        'Guru BK' => 'success',
                        'Siswa' => 'primary',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('classrooms.name')
                    ->badge()
                    ->label('Kelas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('identity_number')
                    ->label('NISN / NIP')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Nomor Telepon')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d F Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d F Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label('Filter Role Akses')
                    ->relationship('role', 'name')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('classrooms')
                    ->label('Filter Kelas')
                    ->relationship('classrooms', 'name')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Lulus' => 'Lulus',
                        'Keluar' => 'Keluar',
                        'DO' => 'DO',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('deleteAny', User::class))
                        ->authorizeIndividualRecords(),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('forceDeleteAny', User::class))
                        ->authorizeIndividualRecords(),
                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('restoreAny', User::class))
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }
}
