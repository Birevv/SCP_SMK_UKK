<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Widgets\JournalHistoryOverview;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Support\PermissionHelper;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class JournalHistory extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Riwayat Isi Jurnal';

    protected static ?string $title = 'Riwayat Isi Jurnal Mengajar';

    protected ?string $subheading = 'Rekapitulasi dan riwayat lengkap pengisian jurnal pembelajaran serta presensi siswa.';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Akademik';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.journal-history';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()?->user() ?? Auth::user();

        if (! $user) {
            return false;
        }

        if (! PermissionHelper::exists('View:JournalHistory')) {
            return false;
        }

        return $user->hasPermissionTo('View:JournalHistory')
            || (bool) $user->role?->hasPermissionTo('View:JournalHistory');
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            JournalHistoryOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * @return array{totalJournals: int, thisMonthJournals: int, uniqueClassesCount: int}
     */
    public function getStats(): array
    {
        return (new JournalHistoryOverview)->getStatsData();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();

        $query = Journal::query()
            ->with([
                'schedule.classroom.classLevel',
                'schedule.classroom.major',
                'schedule.classroom.rombel',
                'schedule.subject',
                'schedule.room',
                'teacher',
                'attendances',
            ]);

        $isTeacherOnly = $user && ! ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin' || $user->hasRole('Waka Kurikulum') || $user->role?->name === 'Waka Kurikulum');

        if ($isTeacherOnly) {
            $query->where('journals.teacher_id', $user->id);
        }

        return $table
            ->query($query)
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal KBM')
                    ->date('d M Y')
                    ->description(fn (Journal $record): string => $record->date ? $record->date->locale('id')->isoFormat('dddd') : '-')
                    ->sortable(),

                TextColumn::make('schedule.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schedule.subject.name')
                    ->label('Mata Pelajaran')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Journal $record): ?string => $record->schedule?->room?->name ? 'Ruang: '.$record->schedule->room->name : null),

                TextColumn::make('session_time')
                    ->label('Jam / Sesi')
                    ->state(function (Journal $record): string {
                        $sch = $record->schedule;
                        if (! $sch) {
                            return '-';
                        }
                        $sessionStr = $sch->start_session && $sch->end_session
                            ? ($sch->start_session === $sch->end_session ? "Jam ke-{$sch->start_session}" : "Jam {$sch->start_session}-{$sch->end_session}")
                            : null;
                        $timeStr = substr((string) $sch->start_time, 0, 5).' - '.substr((string) $sch->end_time, 0, 5);

                        return $sessionStr ? "{$sessionStr} ({$timeStr})" : $timeStr;
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('teaching_material')
                    ->label('Materi Pembelajaran')
                    ->limit(35)
                    ->tooltip(fn (Journal $record): ?string => $record->teaching_material)
                    ->searchable(),

                TextColumn::make('attendance_summary')
                    ->label('Presensi Siswa')
                    ->html()
                    ->state(function (Journal $record): string {
                        $attendances = $record->attendances;
                        $hadir = $attendances->where('status', 'Hadir')->count();
                        $sakit = $attendances->where('status', 'Sakit')->count();
                        $izin = $attendances->where('status', 'Izin')->count();
                        $alpa = $attendances->where('status', 'Alpa')->count();

                        return '<div class="inline-flex items-center gap-1 font-mono text-[11px]">'
                            .'<span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 font-semibold" title="Hadir">H: '.$hadir.'</span>'
                            .'<span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 font-semibold" title="Sakit">S: '.$sakit.'</span>'
                            .'<span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 font-semibold" title="Izin">I: '.$izin.'</span>'
                            .'<span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 font-semibold" title="Alpa">A: '.$alpa.'</span>'
                            .'</div>';
                    }),

                ImageColumn::make('activity_photo')
                    ->label('Foto KBM')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(null),

                TextColumn::make('created_at')
                    ->label('Waktu Simpan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from_date')->label('Dari Tanggal'),
                        DatePicker::make('until_date')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from_date'] ?? null),
                                fn (Builder $q) => $q->whereDate('date', '>=', $data['from_date']),
                            )
                            ->when(
                                filled($data['until_date'] ?? null),
                                fn (Builder $q) => $q->whereDate('date', '<=', $data['until_date']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (filled($data['from_date'] ?? null)) {
                            $indicators['from_date'] = 'Dari: '.Carbon::parse($data['from_date'])->translatedFormat('d M Y');
                        }
                        if (filled($data['until_date'] ?? null)) {
                            $indicators['until_date'] = 'Sampai: '.Carbon::parse($data['until_date'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->searchable()
                    ->preload()
                    ->options(function () use ($user, $isTeacherOnly): array {
                        $query = Classroom::query()->orderBy('name');
                        if ($isTeacherOnly && $user) {
                            $teacherClassroomIds = Schedule::where('teacher_id', $user->id)
                                ->pluck('classroom_id')
                                ->unique();
                            $query->whereIn('id', $teacherClassroomIds);
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('schedule', fn (Builder $q) => $q->where('classroom_id', $data['value']))
                        : $query
                    ),

                SelectFilter::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->preload()
                    ->options(function () use ($user, $isTeacherOnly): array {
                        $query = Subject::query()->orderBy('name');
                        if ($isTeacherOnly && $user) {
                            $teacherSubjectIds = Schedule::where('teacher_id', $user->id)
                                ->pluck('subject_id')
                                ->unique();
                            $query->whereIn('id', $teacherSubjectIds);
                        }

                        return $query->pluck('name', 'id')->toArray();
                    })
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('schedule', fn (Builder $q) => $q->where('subject_id', $data['value']))
                        : $query
                    ),
            ])
            ->recordActions([
                Action::make('view_detail')
                    ->label('Detail Jurnal')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn (Journal $record): string => 'Detail Jurnal: '.($record->schedule?->subject?->name ?? 'KBM').' - '.($record->schedule?->classroom?->name ?? ''))
                    ->modalSubheading(fn (Journal $record): string => ($record->date ? $record->date->locale('id')->isoFormat('dddd, D MMMM Y') : '').' | Jam Mengajar: '.($record->schedule?->start_time ? substr((string) $record->schedule->start_time, 0, 5).' - '.substr((string) $record->schedule->end_time, 0, 5) : '-'))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Journal $record) => view('filament.pages.partials.journal-detail-modal', [
                        'journal' => $record->loadMissing(['attendances.student', 'schedule.classroom.classLevel', 'schedule.classroom.major', 'schedule.subject', 'schedule.room', 'teacher']),
                    ])),
            ]);
    }
}
