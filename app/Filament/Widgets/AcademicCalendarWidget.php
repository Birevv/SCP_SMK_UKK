<?php

namespace App\Filament\Widgets;

use App\Enums\AcademicEventCategory;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Saade\FilamentFullCalendar\Data\DateSelectInfo;
use Saade\FilamentFullCalendar\Data\FetchInfo;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class AcademicCalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = AcademicEvent::class;

    protected ?string $startAttribute = 'start_date';

    protected ?string $endAttribute = 'end_date';

    protected static bool $isDiscovered = false;

    public ?int $selectedYearId = null;

    public ?string $selectedGrade = null; // null (Semua), 'X', 'XI', 'XII'

    public function mount(?int $selectedYearId = null, ?string $selectedGrade = null): void
    {
        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $this->selectedYearId = $selectedYearId ?? $activeYear?->id;
        $this->selectedGrade = $selectedGrade;
    }

    public function canManage(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole(['Super Admin', 'Waka Kurikulum'])
            || $user->can('Create:AcademicYear')
            || $user->can('Update:AcademicYear');
    }

    #[On('calendar-year-changed')]
    public function onYearChanged(int $yearId): void
    {
        $this->selectedYearId = $yearId;
        $academicYear = AcademicYear::find($yearId);
        if ($academicYear && $academicYear->start_date) {
            $this->goToDate($academicYear->start_date->toDateString());
        }
        $this->refreshRecords();
    }

    #[On('calendar-grade-changed')]
    public function onGradeChanged(?string $grade): void
    {
        $this->selectedGrade = $grade ?: null;
        $this->refreshRecords();
    }

    protected function toolbarButtons(): ?array
    {
        return [
            'start' => [['prev', 'next'], 'today'],
            'center' => ['title'],
            'end' => [
                ['dayGridMonth', 'timeGridWeek', 'listMonth'],
            ],
        ];
    }

    public function config(): array
    {
        return [
            'firstDay' => 1, // Senin
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listMonth',
            ],
            'buttonText' => [
                'today' => 'Hari Ini',
                'month' => 'Bulan',
                'week' => 'Minggu',
                'list' => 'Daftar Agenda',
            ],
            'initialView' => 'dayGridMonth',
            'navLinks' => true,
            'editable' => $this->canManage(),
            'selectable' => $this->canManage(),
            'dayMaxEvents' => 3,
            'moreLinkText' => 'agenda lainnya',
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'meridiem' => false,
                'hour12' => false,
            ],
        ];
    }

    /**
     * Mengambil daftar event sesuai batas tampilan FullCalendar & filter aktif.
     */
    public function fetchEvents(FetchInfo $info): iterable | Builder | Relation
    {
        $startDate = Carbon::parse($info->start);
        $endDate = Carbon::parse($info->end);

        $query = AcademicEvent::query()
            ->where('start_date', '<=', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString());

        if ($this->selectedYearId) {
            $query->where('academic_year_id', $this->selectedYearId);
        }

        if ($this->selectedGrade) {
            $query->where(function ($q) {
                $q->whereNull('target_grades')
                    ->orWhereJsonContains('target_grades', $this->selectedGrade);
            });
        }

        return $query->get()->map(function (AcademicEvent $event): array {
            $category = $event->category instanceof AcademicEventCategory
                ? $event->category
                : AcademicEventCategory::tryFrom($event->category);

            // Warna semantik hex sesuai kategori
            $color = match ($category?->getColor()) {
                'danger' => '#ef4444',
                'warning' => '#f59e0b',
                'rose' => '#f43f5e',
                'info' => '#0284c7',
                'teal' => '#0d9488',
                'purple' => '#9333ea',
                'success' => '#10b981',
                'primary' => '#4f46e5',
                default => '#64748b',
            };

            // FullCalendar treats allDay end date as exclusive (end_date + 1 day)
            $endDate = $event->end_date->copy();
            if ($event->is_all_day) {
                $endDate = $endDate->addDay();
            }

            $titlePrefix = '';
            if (! empty($event->target_grades)) {
                $titlePrefix = '[' . implode(',', $event->target_grades) . '] ';
            }

            $icon = '';
            if ($event->category === AcademicEventCategory::UPACARA) {
                $icon = '📢 ';
            } elseif ($event->is_holiday) {
                $icon = '🔴 ';
            }

            return [
                'id' => $event->id,
                'title' => $icon . $titlePrefix . $event->title,
                'start' => $event->is_all_day
                    ? $event->start_date->toDateString()
                    : $event->start_date->toDateString() . 'T' . ($event->start_time ?? '00:00:00'),
                'end' => $event->is_all_day
                    ? $endDate->toDateString()
                    : $event->end_date->toDateString() . 'T' . ($event->end_time ?? '23:59:59'),
                'allDay' => $event->is_all_day,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'category' => $category?->getLabel(),
                    'target_grades' => $event->target_grades,
                    'is_holiday' => $event->is_holiday,
                    'disable_kbm' => $event->disable_kbm,
                    'requires_attendance' => $event->requires_attendance,
                    'description' => $event->description,
                ],
            ];
        })->toArray();
    }

    /**
     * Normalisasi atribut tanggal saat user melakukan seleksi tanggal pada kalender.
     */
    protected function getSelectionRecordAttributes(DateSelectInfo $info): array
    {
        $start = Carbon::parse($info->start)->toDateString();
        $end = $info->end
            ? ($info->allDay ? Carbon::parse($info->end)->subDay()->toDateString() : Carbon::parse($info->end)->toDateString())
            : $start;

        if ($end < $start) {
            $end = $start;
        }

        return [
            'start_date' => $start,
            'end_date' => $end,
            'is_all_day' => $info->allDay,
            'academic_year_id' => $this->selectedYearId ?? AcademicYear::getActive()?->id,
            'requires_attendance' => true,
        ];
    }

    /**
     * Definisi Form Schema untuk Create / Edit / View Action.
     */
    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('Nama / Judul Agenda')
                ->required()
                ->placeholder('Contoh: Asesmen Sumatif Akhir Semester (ASAS)')
                ->columnSpanFull(),

            Forms\Components\Select::make('category')
                ->label('Kategori Acara')
                ->options(AcademicEventCategory::class)
                ->required()
                ->default(AcademicEventCategory::KEGIATAN_SISWA->value)
                ->live()
                ->afterStateUpdated(function ($state, callable $set): void {
                    $cat = $state instanceof AcademicEventCategory
                        ? $state
                        : AcademicEventCategory::tryFrom((string) $state);

                    if ($cat) {
                        $set('is_holiday', $cat->defaultIsHoliday());
                        $set('disable_kbm', $cat->defaultDisableKbm());
                        $set('requires_attendance', $cat->defaultRequiresAttendance());
                        if ($cat === AcademicEventCategory::UKK) {
                            $set('target_grades', ['XII']);
                        } elseif ($cat === AcademicEventCategory::PKL) {
                            $set('target_grades', ['XI', 'XII']);
                        }
                    }
                }),

            Forms\Components\CheckboxList::make('target_grades')
                ->label('Target Tingkat Siswa Terdampak')
                ->options([
                    'X' => 'Kelas X',
                    'XI' => 'Kelas XI',
                    'XII' => 'Kelas XII',
                ])
                ->columns(3)
                ->helperText('Kosongkan jika agenda berlaku untuk seluruh sekolah')
                ->columnSpanFull(),

            Section::make('Ketentuan Operasional')
                ->description('Pengaturan otomatisasi KBM guru dan presensi kehadiran siswa')
                ->schema([
                    Forms\Components\Toggle::make('is_holiday')
                        ->label('Hari Libur Resmi')
                        ->helperText('Menandai tanggal merah pada kalender akademik sekolah'),
                    Forms\Components\Toggle::make('disable_kbm')
                        ->label('Nonaktifkan KBM Guru')
                        ->helperText('Meniadakan jam mengajar & tagihan jurnal mengajar guru untuk kelas terkait'),
                    Forms\Components\Toggle::make('requires_attendance')
                        ->label('Wajib Hadir / Presensi Siswa')
                        ->helperText('Siswa tetap wajib absen gerbang (aktif untuk Upacara 17 Agustus atau UKK/PKL)')
                        ->default(true),
                ])
                ->columns(1)
                ->collapsible()
                ->columnSpanFull(),

            Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->afterOrEqual('start_date'),
                ]),

            Forms\Components\Toggle::make('is_all_day')
                ->label('Berlangsung Seharian Penuh (All Day)')
                ->default(true)
                ->live(),

            Grid::make(2)
                ->schema([
                    Forms\Components\TimePicker::make('start_time')
                        ->label('Jam Mulai')
                        ->seconds(false),
                    Forms\Components\TimePicker::make('end_time')
                        ->label('Jam Selesai')
                        ->seconds(false),
                ])
                ->visible(fn ($get): bool => ! (bool) $get('is_all_day')),

            Forms\Components\Textarea::make('description')
                ->label('Catatan / Keterangan Tambahan')
                ->placeholder('Pakaian seragam, instruksi panitia, tempat acara...')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\Hidden::make('academic_year_id')
                ->default(fn () => $this->selectedYearId ?? AcademicYear::getActive()?->id),

            Forms\Components\Hidden::make('created_by')
                ->default(fn () => Auth::id()),
        ];
    }

    protected function createAction(): Action
    {
        return CreateAction::make()
            ->label('Tambah Agenda')
            ->modalHeading('Tambah Agenda Akademik')
            ->visible(fn (): bool => $this->canManage())
            ->mutateFormDataUsing(function (array $data): array {
                $data['created_by'] = Auth::id();
                if (empty($data['academic_year_id'])) {
                    $data['academic_year_id'] = $this->selectedYearId ?? AcademicYear::getActive()?->id;
                }
                return $data;
            })
            ->after(fn () => $this->refreshRecords());
    }

    protected function modalActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah Agenda')
                ->modalHeading('Ubah Agenda Akademik')
                ->visible(fn (): bool => $this->canManage())
                ->cancelParentActions()
                ->after(fn () => $this->refreshRecords()),

            DeleteAction::make()
                ->label('Hapus Agenda')
                ->modalHeading('Hapus Agenda Akademik')
                ->visible(fn (): bool => $this->canManage())
                ->cancelParentActions()
                ->after(fn () => $this->refreshRecords()),
        ];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()
            ->modalHeading(fn (AcademicEvent $record) => $record->title)
            ->modalFooterActions(fn (ViewAction $action): array => [
                ...$this->getCachedFormActions(),
                $action->getModalCancelAction()->label('Tutup'),
            ]);
    }

    protected function headerActions(): array
    {
        return [
            Action::make('syncHolidays')
                ->label('Tarik Libur Nasional')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => $this->canManage())
                ->modalHeading('Tarik Libur Nasional (API Sync)')
                ->modalDescription('Sinkronisasi otomatis hari libur resmi SKB 3 Menteri RI ke dalam Tahun Ajaran aktif.')
                ->form([
                    Forms\Components\Select::make('year')
                        ->label('Pilih Tahun Kalender')
                        ->options([
                            2024 => 'Tahun 2024',
                            2025 => 'Tahun 2025',
                            2026 => 'Tahun 2026',
                            2027 => 'Tahun 2027',
                            2028 => 'Tahun 2028',
                        ])
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data, AcademicCalendarService $service): void {
                    $yearModel = AcademicYear::find($this->selectedYearId) ?? AcademicYear::getActive();
                    if (! $yearModel) {
                        Notification::make()
                            ->danger()
                            ->title('Tahun Ajaran Tidak Ditemukan')
                            ->body('Silakan pilih Tahun Ajaran aktif terlebih dahulu.')
                            ->send();
                        return;
                    }

                    try {
                        $result = $service->syncNationalHolidays($yearModel, (int) $data['year']);
                        Notification::make()
                            ->success()
                            ->title('Sinkronisasi Berhasil')
                            ->body("Berhasil mengimpor {$result['total_synced']} hari libur nasional untuk periode {$result['semester']}.")
                            ->send();

                        $this->refreshRecords();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Sinkronisasi Gagal')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            $this->createAction()
                ->label('Tambah Agenda')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
