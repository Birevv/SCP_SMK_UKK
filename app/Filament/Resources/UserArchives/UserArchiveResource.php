<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserArchives;

use App\Filament\Resources\UserArchives\Pages\ListUserArchives;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class UserArchiveResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'user-archives';

    protected static ?string $navigationLabel = 'Arsip Pengguna (Backup)';

    protected static ?string $modelLabel = 'Arsip Pengguna';

    protected static ?string $pluralModelLabel = 'Arsip Pengguna (Backup)';

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->onlyTrashed()
            ->withCount(['journals', 'schedules', 'attendances']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Waka Kurikulum' => 'danger',
                        'Guru' => 'info',
                        'Wali Kelas' => 'warning',
                        'Guru BK' => 'success',
                        'Siswa' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('identity_number')
                    ->label('NISN / NIP')
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable(),

                Tables\Columns\TextColumn::make('academic_records')
                    ->label('Riwayat Akademik')
                    ->badge()
                    ->state(function (User $record): string {
                        $parts = [];
                        if ($record->journals_count > 0) {
                            $parts[] = "{$record->journals_count} Jurnal";
                        }
                        if ($record->schedules_count > 0) {
                            $parts[] = "{$record->schedules_count} Jadwal";
                        }
                        if ($record->attendances_count > 0) {
                            $parts[] = "{$record->attendances_count} Presensi";
                        }

                        return ! empty($parts) ? implode(', ', $parts) : 'Tidak ada data relasi';
                    })
                    ->color(fn (User $record): string => $record->hasAcademicRecords() ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Diarsipkan Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label('Filter Role')
                    ->relationship('role', 'name'),
            ])
            ->recordActions([
                RestoreAction::make()
                    ->label('Pulihkan')
                    ->color('success')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->modalHeading('Pulihkan Pengguna')
                    ->modalDescription(fn (User $record): string => "Kembalikan '{$record->full_name}' ke daftar pengguna aktif di tabel utama Waka? Seluruh data jurnal dan jadwal akan langsung aktif kembali.")
                    ->modalSubmitActionLabel('Ya, Pulihkan')
                    ->successNotificationTitle('Pengguna berhasil dipulihkan ke status Aktif'),

                ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->modalHeading('Hapus Permanen')
                    ->modalDescription('PERINGATAN: Penghapusan permanen tidak dapat dibatalkan. Data yang memiliki riwayat akademik akan ditolak.')
                    ->before(function (ForceDeleteAction $action, User $record) {
                        if ($record->hasAcademicRecords()) {
                            Notification::make()
                                ->title('Penghapusan Ditolak')
                                ->body($record->getAcademicRecordsRestrictionReason())
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih')
                        ->color('success')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->modalHeading('Pulihkan Pengguna Terpilih')
                        ->modalDescription('Kembalikan seluruh pengguna terpilih ke tabel utama?')
                        ->modalSubmitActionLabel('Ya, Pulihkan Semua')
                        ->successNotificationTitle('Seluruh pengguna terpilih berhasil dipulihkan'),

                    ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen Terpilih')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->before(function (ForceDeleteBulkAction $action, Collection $records) {
                            $restricted = $records->filter(fn (User $user) => $user->hasAcademicRecords());
                            if ($restricted->isNotEmpty()) {
                                $names = $restricted->pluck('full_name')->take(3)->join(', ');
                                Notification::make()
                                    ->title('Penghapusan Ditolak')
                                    ->body("Sebagian pengguna ({$names}) memiliki riwayat jurnal/presensi aktif sehingga tidak dapat dihapus permanen.")
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserArchives::route('/'),
        ];
    }
}
