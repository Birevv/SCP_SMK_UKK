<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class StudentPromotion extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Kenaikan & Kelulusan';

    protected static ?string $title = 'Kenaikan Kelas & Kelulusan Siswa';

    protected ?string $subheading = 'Pusat pengelolaan kenaikan tingkat kelas dan kelulusan siswa kelas XII.';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Akademik';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.student-promotion';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($user->role?->name, ['Super Admin', 'Waka Kurikulum'])
            || $user->can('View:StudentPromotion');
    }

    protected function getHeaderActions(): array
    {
        return [
            // =================================================================
            // TOMBOL UTAMA: PROSES KENAIKAN & KELULUSAN
            // =================================================================
            Action::make('promote_and_graduate')
                ->label('Proses Kenaikan & Kelulusan')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kenaikan & Kelulusan')
                ->modalDescription('Proses kenaikan tingkat kelas dan kelulusan siswa kelas XII akan dijalankan. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Proses')
                ->action(function (): void {
                    $activeId = AcademicYear::getActiveId();
                    $nextYear = AcademicYear::where('id', '>', (int) $activeId)->first();
                    $targetAcademicYearId = (int) ($nextYear?->id ?? $activeId);

                    $now = now();
                    $gradYear = (string) date('Y');
                    $gradDate = $now->toDateString();
                    $notes = "Alumni Lulusan {$gradYear}";

                    $promotedG10 = 0;
                    $promotedG11 = 0;
                    $graduatedCount = 0;

                    DB::transaction(function () use ($targetAcademicYearId, $now, $gradYear, $gradDate, $notes, &$promotedG10, &$promotedG11, &$graduatedCount) {
                        // -----------------------------------------------------
                        // 1. KELULUSAN SISWA KELAS 12 DILAKUKAN PERTAMA
                        // -----------------------------------------------------
                        $grade12Students = User::where('status', 'Aktif')
                            ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                            ->whereHas('classrooms', fn ($q) => $q->forGrade('12')->where('user_class.status', 'Aktif'))
                            ->whereDoesntHave('classrooms', fn ($q) => $q->forGrade('11')->where('user_class.status', 'Naik Kelas')->whereDate('user_class.updated_at', today()))
                            ->get();

                        foreach ($grade12Students as $student) {
                            $student->update([
                                'status' => 'Lulus',
                                'graduation_year' => $gradYear,
                                'graduated_at' => $gradDate,
                                'alumni_notes' => $notes,
                            ]);

                            DB::table('user_class')
                                ->where('user_id', $student->id)
                                ->where('status', 'Aktif')
                                ->update(['status' => 'Lulus', 'updated_at' => $now]);

                            DB::table('sessions')->where('user_id', $student->id)->delete();
                            $student->tokens()->delete();

                            $graduatedCount++;
                        }

                        // -----------------------------------------------------
                        // 2. KENAIKAN SELURUH KELAS 11 KE KELAS 12
                        // -----------------------------------------------------
                        $g11Classes = Classroom::forGrade('11')->get();
                        foreach ($g11Classes as $source) {
                            $target = Classroom::where('major_id', $source->major_id)
                                ->where('rombel_id', $source->rombel_id)
                                ->whereHas('classLevel', fn ($q) => $q->where('level', 12))
                                ->first()
                                ?? Classroom::where('major_id', $source->major_id)
                                    ->whereHas('classLevel', fn ($q) => $q->where('level', 12))
                                    ->first();

                            if (! $target) {
                                continue;
                            }

                            $students = User::where('status', 'Aktif')
                                ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                                ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $source->id)->where('user_class.status', 'Aktif'))
                                ->whereDoesntHave('classrooms', fn ($q) => $q->forGrade('10')->where('user_class.status', 'Naik Kelas')->whereDate('user_class.updated_at', today()))
                                ->get();

                            foreach ($students as $student) {
                                DB::table('user_class')
                                    ->where('user_id', $student->id)
                                    ->where('classroom_id', $source->id)
                                    ->where('status', 'Aktif')
                                    ->update(['status' => 'Naik Kelas', 'updated_at' => $now]);

                                $existing = DB::table('user_class')
                                    ->where('user_id', $student->id)
                                    ->where('classroom_id', $target->id)
                                    ->where('academic_year_id', $targetAcademicYearId)
                                    ->first();

                                if ($existing) {
                                    DB::table('user_class')->where('id', $existing->id)->update(['status' => 'Aktif', 'updated_at' => $now]);
                                } else {
                                    DB::table('user_class')->insert([
                                        'user_id' => $student->id,
                                        'classroom_id' => $target->id,
                                        'academic_year_id' => $targetAcademicYearId,
                                        'status' => 'Aktif',
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }

                                $student->update(['classroom_id' => $target->id]);
                                $promotedG11++;
                            }
                        }

                        // -----------------------------------------------------
                        // 3. KENAIKAN SELURUH KELAS 10 KE KELAS 11
                        // -----------------------------------------------------
                        $g10Classes = Classroom::forGrade('10')->get();
                        foreach ($g10Classes as $source) {
                            $target = Classroom::where('major_id', $source->major_id)
                                ->where('rombel_id', $source->rombel_id)
                                ->whereHas('classLevel', fn ($q) => $q->where('level', 11))
                                ->first()
                                ?? Classroom::where('major_id', $source->major_id)
                                    ->whereHas('classLevel', fn ($q) => $q->where('level', 11))
                                    ->first();

                            if (! $target) {
                                continue;
                            }

                            $students = User::where('status', 'Aktif')
                                ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                                ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $source->id)->where('user_class.status', 'Aktif'))
                                ->whereDoesntHave('classrooms', fn ($q) => $q->forGrade('10')->where('user_class.status', 'Naik Kelas')->whereDate('user_class.updated_at', today()))
                                ->get();

                            foreach ($students as $student) {
                                DB::table('user_class')
                                    ->where('user_id', $student->id)
                                    ->where('classroom_id', $source->id)
                                    ->where('status', 'Aktif')
                                    ->update(['status' => 'Naik Kelas', 'updated_at' => $now]);

                                $existing = DB::table('user_class')
                                    ->where('user_id', $student->id)
                                    ->where('classroom_id', $target->id)
                                    ->where('academic_year_id', $targetAcademicYearId)
                                    ->first();

                                if ($existing) {
                                    DB::table('user_class')->where('id', $existing->id)->update(['status' => 'Aktif', 'updated_at' => $now]);
                                } else {
                                    DB::table('user_class')->insert([
                                        'user_id' => $student->id,
                                        'classroom_id' => $target->id,
                                        'academic_year_id' => $targetAcademicYearId,
                                        'status' => 'Aktif',
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }

                                $student->update(['classroom_id' => $target->id]);
                                $promotedG10++;
                            }
                        }
                    });

                    $msgParts = [];
                    if ($promotedG10 > 0 || $promotedG11 > 0) {
                        $msgParts[] = "Kenaikan Kelas: {$promotedG10} siswa X ke XI, {$promotedG11} siswa XI ke XII";
                    }
                    if ($graduatedCount > 0) {
                        $msgParts[] = "Kelulusan: {$graduatedCount} siswa kelas 12 resmi menjadi Alumni ({$gradYear})";
                    }

                    Notification::make()
                        ->title('Proses Berhasil Selesai!')
                        ->body(implode(' | ', $msgParts) ?: 'Kenaikan kelas massal berhasil diproses.')
                        ->success()
                        ->send();
                }),

            // =================================================================
            // MENU DROPDOWN PEMBATALAN (LEBIH SIMPLE & RAPI)
            // =================================================================
            ActionGroup::make([
                Action::make('revert_all_promotions')
                    ->label('Batalkan Kenaikan & Kelulusan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Kenaikan & Kelulusan')
                    ->modalDescription('Kembalikan seluruh siswa ke kelas asal dan batalkan kelulusan alumni?')
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->action(function (): void {
                        $countPromotedReverted = 0;
                        $countAlumniReverted = 0;

                        DB::transaction(function () use (&$countPromotedReverted, &$countAlumniReverted) {
                            // 1. Batalkan kelulusan alumni
                            $alumniStudents = User::where('status', 'Lulus')
                                ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                                ->get();

                            foreach ($alumniStudents as $student) {
                                $student->update([
                                    'status' => 'Aktif',
                                    'graduation_year' => null,
                                    'graduated_at' => null,
                                    'alumni_notes' => null,
                                ]);

                                DB::table('user_class')
                                    ->where('user_id', $student->id)
                                    ->where('status', 'Lulus')
                                    ->update(['status' => 'Aktif']);

                                $countAlumniReverted++;
                            }

                            // 2. Batalkan kenaikan kelas (kembalikan ke kelas asal)
                            $promotedUserIds = DB::table('user_class')
                                ->where('status', 'Naik Kelas')
                                ->pluck('user_id')
                                ->unique();

                            foreach ($promotedUserIds as $userId) {
                                $student = User::find($userId);
                                if (! $student) {
                                    continue;
                                }

                                $oldClassRecord = DB::table('user_class')
                                    ->where('user_id', $userId)
                                    ->where('status', 'Naik Kelas')
                                    ->latest('id')
                                    ->first();

                                $newClassRecord = DB::table('user_class')
                                    ->where('user_id', $userId)
                                    ->where('status', 'Aktif')
                                    ->where('id', '>', $oldClassRecord?->id ?? 0)
                                    ->first();

                                if ($newClassRecord) {
                                    DB::table('user_class')->where('id', $newClassRecord->id)->delete();
                                }

                                if ($oldClassRecord) {
                                    DB::table('user_class')->where('id', $oldClassRecord->id)->update(['status' => 'Aktif']);
                                    $student->update(['classroom_id' => $oldClassRecord->classroom_id]);
                                    $countPromotedReverted++;
                                }
                            }
                        });

                        if ($countPromotedReverted > 0 || $countAlumniReverted > 0) {
                            Notification::make()
                                ->title('Kenaikan & Kelulusan Berhasil Dibatalkan')
                                ->body("{$countPromotedReverted} siswa dikembalikan ke kelas asal dan {$countAlumniReverted} siswa alumni dikembalikan aktif.")
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak Ada Proses Kenaikan/Kelulusan')
                                ->body('Saat ini tidak ada siswa berstatus Naik Kelas atau Alumni untuk dibatalkan.')
                                ->info()
                                ->send();
                        }
                    }),

                Action::make('revert_all_alumni')
                    ->label('Batalkan Status Alumni Saja')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Status Alumni')
                    ->modalDescription('Kembalikan seluruh alumni menjadi siswa aktif kelas XII?')
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->action(function (): void {
                        $alumniStudents = User::where('status', 'Lulus')
                            ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                            ->get();

                        $count = 0;
                        DB::transaction(function () use ($alumniStudents, &$count) {
                            foreach ($alumniStudents as $student) {
                                $student->update([
                                    'status' => 'Aktif',
                                    'graduation_year' => null,
                                    'graduated_at' => null,
                                    'alumni_notes' => null,
                                ]);

                                DB::table('user_class')
                                    ->where('user_id', $student->id)
                                    ->where('status', 'Lulus')
                                    ->update(['status' => 'Aktif']);

                                $count++;
                            }
                        });

                        if ($count > 0) {
                            Notification::make()
                                ->title('Status Alumni Berhasil Dibatalkan')
                                ->body("{$count} siswa alumni berhasil dikembalikan menjadi Siswa Aktif.")
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Tidak Ada Siswa Alumni')
                                ->body('Saat ini tidak ada siswa dengan status Alumni untuk dibatalkan.')
                                ->info()
                                ->send();
                        }
                    }),
            ])
                ->label('Batalkan')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->button()
                ->outlined(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                    ->where(function (Builder $query) {
                        $query->whereHas('classrooms', fn ($q) => $q->forGrade('12'))
                            ->orWhere('status', 'Lulus');
                    })
                    ->with(['classrooms', 'role'])
            )
            ->defaultSort('full_name', 'asc')
            ->columns([
                TextColumn::make('identity_number')
                    ->label('NISN / Username')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->placeholder('-'),

                TextColumn::make('full_name')
                    ->label('Nama Lengkap Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('classrooms', fn ($q) => $q->where('classrooms.name', 'like', "%{$search}%"));
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Lulus' => 'info',
                        'Keluar' => 'warning',
                        'DO' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state, User $record): string => match ($state) {
                        'Lulus' => 'Alumni'.($record->graduation_year ? " '{$record->graduation_year}" : ''),
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('graduation_year')
                    ->label('Angkatan')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('graduated_at')
                    ->label('Tanggal Lulus')
                    ->date('d F Y')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('alumni_notes')
                    ->label('Catatan Alumni / Tujuan')
                    ->limit(35)
                    ->tooltip(fn (User $record): ?string => $record->alumni_notes)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'Aktif' => 'Siswa Kelas 12 (Aktif)',
                        'Lulus' => 'Alumni (Sudah Lulus)',
                        'Keluar' => 'Keluar / Mutasi',
                        'DO' => 'Drop Out (DO)',
                    ]),

                SelectFilter::make('classroom_id')
                    ->label('Filter Rombel Kelas 12')
                    ->options(fn () => Classroom::forGrade('12')->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $data['value']));
                    })
                    ->preload()
                    ->searchable(),

                SelectFilter::make('graduation_year')
                    ->label('Filter Angkatan Alumni')
                    ->options(function () {
                        return User::where('status', 'Lulus')
                            ->whereNotNull('graduation_year')
                            ->distinct()
                            ->orderByDesc('graduation_year')
                            ->pluck('graduation_year', 'graduation_year')
                            ->toArray();
                    }),
            ])
            ->recordActions([
                Action::make('graduate_single')
                    ->label('Luluskan')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status === 'Aktif')
                    ->modalHeading(fn (User $record): string => "Luluskan {$record->full_name} ke Alumni")
                    ->form([
                        TextInput::make('graduation_year')
                            ->label('Tahun Kelulusan / Angkatan')
                            ->default((string) date('Y'))
                            ->required(),
                        DatePicker::make('graduated_at')
                            ->label('Tanggal Resmi Kelulusan')
                            ->default(now()->toDateString())
                            ->required(),
                        TextInput::make('alumni_notes')
                            ->label('Catatan / Tujuan Alumni')
                            ->placeholder('Contoh: [Kuliah] S1 Informatika UGM / [Kerja] PT Telkom'),
                    ])
                    ->action(function (User $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => 'Lulus',
                                'graduation_year' => $data['graduation_year'],
                                'graduated_at' => $data['graduated_at'],
                                'alumni_notes' => $data['alumni_notes'] ?: 'Alumni',
                            ]);

                            DB::table('user_class')
                                ->where('user_id', $record->id)
                                ->where('status', 'Aktif')
                                ->update(['status' => 'Lulus']);

                            DB::table('sessions')->where('user_id', $record->id)->delete();
                            $record->tokens()->delete();
                        });

                        Notification::make()
                            ->title('Siswa Diluluskan')
                            ->body("{$record->full_name} kini berstatus sebagai Alumni ({$data['graduation_year']}).")
                            ->success()
                            ->send();
                    }),

                Action::make('edit_alumni')
                    ->label('Ubah Data Alumni')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn (User $record): bool => $record->status === 'Lulus')
                    ->fillForm(fn (User $record): array => [
                        'graduation_year' => $record->graduation_year,
                        'graduated_at' => $record->graduated_at?->toDateString(),
                        'alumni_notes' => $record->alumni_notes,
                    ])
                    ->form([
                        TextInput::make('graduation_year')
                            ->label('Tahun Kelulusan / Angkatan')
                            ->required(),
                        DatePicker::make('graduated_at')
                            ->label('Tanggal Resmi Kelulusan')
                            ->required(),
                        Textarea::make('alumni_notes')
                            ->label('Catatan Alumni / Tujuan Setelah Lulus')
                            ->placeholder('Contoh: [Kuliah] S1 Teknik Informatika di UGM / [Kerja] PT Astra')
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'graduation_year' => $data['graduation_year'],
                            'graduated_at' => $data['graduated_at'],
                            'alumni_notes' => $data['alumni_notes'],
                        ]);

                        Notification::make()
                            ->title('Data Alumni Diperbarui')
                            ->body("Data kelulusan {$record->full_name} berhasil disimpan.")
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
                            ->body("{$record->full_name} kini kembali berstatus Siswa Aktif.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_graduate')
                        ->label('Luluskan Siswa Terpilih ke Alumni')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->modalHeading('Luluskan Siswa Terpilih')
                        ->modalDescription('Ubah status siswa terpilih menjadi Alumni?')
                        ->modalSubmitActionLabel('Luluskan')
                        ->form([
                            TextInput::make('graduation_year')
                                ->label('Tahun Kelulusan / Angkatan')
                                ->default((string) date('Y'))
                                ->required(),
                            DatePicker::make('graduated_at')
                                ->label('Tanggal Resmi Kelulusan')
                                ->default(now()->toDateString())
                                ->required(),
                            TextInput::make('alumni_notes')
                                ->label('Catatan Alumni Bersama (Opsional)')
                                ->placeholder('Contoh: Angkatan 2026'),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $count = 0;
                            DB::transaction(function () use ($records, $data, &$count) {
                                foreach ($records as $student) {
                                    if ($student->status === 'Aktif') {
                                        $student->update([
                                            'status' => 'Lulus',
                                            'graduation_year' => $data['graduation_year'],
                                            'graduated_at' => $data['graduated_at'],
                                            'alumni_notes' => $data['alumni_notes'] ?: 'Alumni',
                                        ]);

                                        DB::table('user_class')
                                            ->where('user_id', $student->id)
                                            ->where('status', 'Aktif')
                                            ->update(['status' => 'Lulus']);

                                        DB::table('sessions')->where('user_id', $student->id)->delete();
                                        $student->tokens()->delete();

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

                    BulkAction::make('bulk_revert')
                        ->label('Batalkan Alumni Siswa Terpilih')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Alumni Terpilih')
                        ->modalDescription('Kembalikan siswa yang dipilih ke status Siswa Aktif?')
                        ->modalSubmitActionLabel('Ya, Batalkan')
                        ->action(function (EloquentCollection $records): void {
                            $count = 0;
                            DB::transaction(function () use ($records, &$count) {
                                foreach ($records as $student) {
                                    if ($student->status === 'Lulus') {
                                        $student->update([
                                            'status' => 'Aktif',
                                            'graduation_year' => null,
                                            'graduated_at' => null,
                                            'alumni_notes' => null,
                                        ]);

                                        DB::table('user_class')
                                            ->where('user_id', $student->id)
                                            ->where('status', 'Lulus')
                                            ->update(['status' => 'Aktif']);

                                        $count++;
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Status Berhasil Dibatalkan')
                                ->body("{$count} siswa berhasil dikembalikan menjadi Siswa Aktif.")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
