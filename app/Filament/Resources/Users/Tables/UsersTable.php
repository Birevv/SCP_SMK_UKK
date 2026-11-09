<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
                Tables\Columns\TextColumn::make('activeClassrooms.name')
                    ->badge()
                    ->label('Kelas')
                    ->placeholder(fn (User $record): string => $record->classroom?->name ? $record->classroom->name.' (Alumni)' : '-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Lulus' => 'info',
                        'Keluar' => 'warning',
                        'DO' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state, $record): string => match ($state) {
                        'Lulus' => 'Lulus (Alumni)'.($record->graduation_year ? " '{$record->graduation_year}" : ''),
                        default => (string) $state,
                    })
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('graduation_year')
                    ->label('Angkatan Alumni')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('alumni_notes')
                    ->label('Catatan Alumni')
                    ->placeholder('-')
                    ->limit(30)
                    ->tooltip(fn (User $record): ?string => $record->alumni_notes)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('activeClassrooms')
                    ->label('Filter Kelas (Aktif)')
                    ->relationship('activeClassrooms', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('name'))
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
                SelectFilter::make('graduation_year')
                    ->label('Filter Angkatan Alumni')
                    ->options(fn () => User::where('status', 'Lulus')
                        ->whereNotNull('graduation_year')
                        ->distinct()
                        ->orderByDesc('graduation_year')
                        ->pluck('graduation_year', 'graduation_year')
                        ->toArray()
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('graduate')
                    ->label('Luluskan')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status === 'Aktif' && ($record->hasRole('Siswa') || $record->role?->name === 'Siswa'))
                    ->modalHeading('Luluskan Siswa ke Alumni')
                    ->modalDescription(fn (User $record): string => "Luluskan {$record->full_name} menjadi Alumni?")
                    ->modalSubmitActionLabel('Luluskan')
                    ->form([
                        TextInput::make('graduation_year')
                            ->label('Tahun Kelulusan / Angkatan')
                            ->default(date('Y'))
                            ->required(),
                        DatePicker::make('graduated_at')
                            ->label('Tanggal Resmi Kelulusan')
                            ->default(now()->toDateString())
                            ->required(),
                        Textarea::make('alumni_notes')
                            ->label('Catatan Alumni (Opsional)')
                            ->placeholder('Contoh: Melanjutkan kuliah di UGM / Bekerja di PT XYZ'),
                    ])
                    ->action(function (User $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => 'Lulus',
                                'graduation_year' => $data['graduation_year'],
                                'graduated_at' => $data['graduated_at'],
                                'alumni_notes' => $data['alumni_notes'] ?? null,
                            ]);

                            DB::table('user_class')
                                ->where('user_id', $record->id)
                                ->where('status', 'Aktif')
                                ->update(['status' => 'Lulus']);

                            DB::table('sessions')->where('user_id', $record->id)->delete();
                            $record->tokens()->delete();
                        });

                        Notification::make()
                            ->title('Siswa Berhasil Diluluskan')
                            ->body("{$record->full_name} kini berstatus Alumni ({$data['graduation_year']}).")
                            ->success()
                            ->send();
                    }),

                Action::make('revert_graduation')
                    ->label('Batalkan Alumni')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (User $record): bool => $record->status === 'Lulus')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Status Alumni')
                    ->modalDescription(fn (User $record): string => "Kembalikan {$record->full_name} ke status Siswa Aktif?")
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->action(function (User $record): void {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => 'Aktif',
                                'graduation_year' => null,
                                'graduated_at' => null,
                                'alumni_notes' => null,
                            ]);

                            DB::table('user_class')
                                ->where('user_id', $record->id)
                                ->where('status', 'Lulus')
                                ->update(['status' => 'Aktif']);
                        });

                        Notification::make()
                            ->title('Status Dikembalikan')
                            ->body("{$record->full_name} kini kembali berstatus Aktif.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Arsipkan Pengguna')
                    ->modalDescription(fn (User $record): string => "Pindahkan '{$record->full_name}' ke Arsip Terhapus (Backup)? Data jurnal dan presensi tetap aman serta dapat dipulihkan kapan saja oleh Super Admin.")
                    ->successNotificationTitle('Pengguna berhasil dipindahkan ke Arsip Terhapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('graduate_bulk')
                        ->label('Luluskan Terpilih ke Alumni')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->modalHeading('Luluskan Siswa Terpilih')
                        ->modalDescription('Ubah status siswa terpilih menjadi Alumni?')
                        ->modalSubmitActionLabel('Luluskan')
                        ->form([
                            TextInput::make('graduation_year')
                                ->label('Tahun Kelulusan / Angkatan')
                                ->default(date('Y'))
                                ->required(),
                            DatePicker::make('graduated_at')
                                ->label('Tanggal Resmi Kelulusan')
                                ->default(now()->toDateString())
                                ->required(),
                            Textarea::make('alumni_notes')
                                ->label('Catatan Alumni Bersama (Opsional)')
                                ->placeholder('Contoh: Angkatan Kelulusan 2026'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            DB::transaction(function () use ($records, $data, &$count) {
                                foreach ($records as $user) {
                                    if ($user->status === 'Aktif') {
                                        $user->update([
                                            'status' => 'Lulus',
                                            'graduation_year' => $data['graduation_year'],
                                            'graduated_at' => $data['graduated_at'],
                                            'alumni_notes' => $data['alumni_notes'] ?? null,
                                        ]);

                                        DB::table('user_class')
                                            ->where('user_id', $user->id)
                                            ->where('status', 'Aktif')
                                            ->update(['status' => 'Lulus']);

                                        DB::table('sessions')->where('user_id', $user->id)->delete();
                                        $user->tokens()->delete();

                                        $count++;
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Kelulusan Berhasil')
                                ->body("{$count} siswa berhasil diproses menjadi Alumni ({$data['graduation_year']}).")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('deleteAny', User::class))
                        ->authorizeIndividualRecords()
                        ->modalHeading('Arsipkan Pengguna Terpilih')
                        ->modalDescription('Pindahkan seluruh pengguna terpilih ke Arsip Terhapus (Backup)? Data akademik tetap aman.')
                        ->successNotificationTitle('Pengguna terpilih berhasil dipindahkan ke Arsip Terhapus'),
                ]),
            ]);
    }
}
