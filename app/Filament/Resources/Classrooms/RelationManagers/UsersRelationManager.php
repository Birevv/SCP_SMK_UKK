<?php

namespace App\Filament\Resources\Classrooms\RelationManagers;

use App\Models\AcademicYear;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Anggota Kelas (Wali Kelas & Siswa)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Status Keanggotaan di Kelas Ini')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Tinggal Kelas' => 'Tinggal Kelas',
                        'Mutasi' => 'Mutasi',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Wali Kelas' => 'warning',
                        'Siswa' => 'primary',
                        'Guru' => 'info',
                        'Guru BK' => 'success',
                        'Waka Kurikulum' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('identity_number')
                    ->label('NIP / NISN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),

                TextColumn::make('class_status')
                    ->label('Status Kelas')
                    ->state(fn ($record): string => (string) ($record->pivot?->status ?? 'Aktif'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Tinggal Kelas' => 'danger',
                        'Mutasi' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label('Filter Role')
                    ->relationship('role', 'name')
                    ->preload(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Tambah / Lampirkan Anggota')
                    ->preloadRecordSelect()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['academic_year_id'] = AcademicYear::where('is_active', true)->first()?->id ?? AcademicYear::first()?->id;
                        $data['status'] = 'Aktif';

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah Status'),
                DetachAction::make()->label('Keluarkan dari Kelas'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Keluarkan Terpilih'),
                ]),
            ]);
    }
}
