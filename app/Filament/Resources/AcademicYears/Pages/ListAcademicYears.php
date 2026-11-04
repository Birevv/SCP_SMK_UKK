<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Enums\AcademicEventCategory;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class ListAcademicYears extends ListRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected string $view = 'filament.resources.academic-years.pages.list-academic-years';

    public string $currentViewTab = 'calendar'; // 'calendar' or 'years'

    // Filter & State Kalender
    public ?int $selectedYearId = null;

    public ?string $selectedGrade = null; // null (Semua), 'X', 'XI', 'XII'

    public int $currentMonth = 1;

    public int $currentYear = 2026;

    public string $searchQuery = '';

    public array $selectedCategories = []; // array of category values for interactive legend filter

    public string $viewMode = 'month'; // 'month', 'week', 'list'

    // Form Modal Agenda
    public bool $showEventModal = false;

    public ?int $editingEventId = null;

    public string $eventTitle = '';

    public string $eventCategory = 'kegiatan_siswa';

    public array $eventTargetGrades = [];

    public bool $eventIsHoliday = false;

    public bool $eventDisableKbm = false;

    public bool $eventRequiresAttendance = true;

    public string $eventStartDate = '';

    public string $eventEndDate = '';

    public bool $eventIsAllDay = true;

    public ?string $eventStartTime = null;

    public ?string $eventEndTime = null;

    public ?string $eventDescription = null;

    // Modal Sinkronisasi Libur Nasional
    public bool $showSyncModal = false;

    public int $syncYear = 2026;

    public bool $isSyncing = false;

    public function mount(): void
    {
        parent::mount();

        $activeYear = AcademicYear::getActive() ?? AcademicYear::first();
        $this->selectedYearId = $activeYear?->id;

        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
        $this->syncYear = $now->year;

        // Jika sekarang di luar rentang tanggal tahun ajaran aktif, sesuaikan ke start_date
        if ($activeYear && $activeYear->start_date) {
            if ($now->lt($activeYear->start_date) || ($activeYear->end_date && $now->gt($activeYear->end_date))) {
                $this->currentMonth = $activeYear->start_date->month;
                $this->currentYear = $activeYear->start_date->year;
            }
        }
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

    public function switchTab(string $tab): void
    {
        $this->currentViewTab = $tab;
    }

    public function prevMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function goToToday(): void
    {
        $now = Carbon::now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
    }

    public function resetToActivePeriod(): void
    {
        $year = $this->selectedYear;
        if ($year && $year->start_date) {
            $this->currentMonth = $year->start_date->month;
            $this->currentYear = $year->start_date->year;
        } else {
            $this->goToToday();
        }
    }

    public function toggleCategoryFilter(string $category): void
    {
        if (in_array($category, $this->selectedCategories, true)) {
            $this->selectedCategories = array_values(array_diff($this->selectedCategories, [$category]));
        } else {
            $this->selectedCategories[] = $category;
        }
    }

    public function updatedSelectedYearId($value): void
    {
        $year = AcademicYear::find($value);
        if ($year && $year->start_date) {
            $currentDate = Carbon::create($this->currentYear, $this->currentMonth, 1);
            if ($currentDate->lt($year->start_date) || ($year->end_date && $currentDate->gt($year->end_date))) {
                $this->currentMonth = $year->start_date->month;
                $this->currentYear = $year->start_date->year;
            }
        }
    }

    public function updatedEventCategory($value): void
    {
        $categoryEnum = AcademicEventCategory::tryFrom($value);
        if ($categoryEnum) {
            $this->eventIsHoliday = $categoryEnum->defaultIsHoliday();
            $this->eventDisableKbm = $categoryEnum->defaultDisableKbm();
            $this->eventRequiresAttendance = $categoryEnum->defaultRequiresAttendance();
            if ($categoryEnum === AcademicEventCategory::UKK) {
                $this->eventTargetGrades = ['XII'];
            } elseif ($categoryEnum === AcademicEventCategory::PKL) {
                $this->eventTargetGrades = ['XI', 'XII'];
            }
        }
    }

    public function openCreateEventModal(?string $date = null, ?string $endDate = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $defaultStartDate = $date ?? Carbon::create($this->currentYear, $this->currentMonth, 1)->toDateString();
        $defaultEndDate = $endDate ?? $defaultStartDate;

        // Pastikan urutan tanggal mulai <= tanggal selesai jika diswipe terbalik
        if ($date && $endDate && $date > $endDate) {
            $temp = $defaultStartDate;
            $defaultStartDate = $defaultEndDate;
            $defaultEndDate = $temp;
        }

        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventCategory = AcademicEventCategory::KEGIATAN_SISWA->value;
        $this->eventTargetGrades = [];
        $this->eventIsHoliday = false;
        $this->eventDisableKbm = false;
        $this->eventRequiresAttendance = true;
        $this->eventStartDate = $defaultStartDate;
        $this->eventEndDate = $defaultEndDate;
        $this->eventIsAllDay = true;
        $this->eventStartTime = null;
        $this->eventEndTime = null;
        $this->eventDescription = null;

        $this->showEventModal = true;
    }

    public function openEditEventModal(int $id): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->mountAction('editEvent', ['id' => $id]);
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->resetErrorBag();
    }

    public function saveEvent(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->validate([
            'eventTitle' => 'required|string|max:255',
            'eventCategory' => 'required|string',
            'eventStartDate' => 'required|date',
            'eventEndDate' => 'required|date|after_or_equal:eventStartDate',
        ], [
            'eventTitle.required' => 'Judul agenda wajib diisi.',
            'eventStartDate.required' => 'Tanggal mulai wajib diisi.',
            'eventEndDate.required' => 'Tanggal selesai wajib diisi.',
            'eventEndDate.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $yearId = $this->selectedYearId ?? AcademicYear::getActive()?->id;

        $payload = [
            'academic_year_id' => $yearId,
            'title' => $this->eventTitle,
            'category' => $this->eventCategory,
            'target_grades' => ! empty($this->eventTargetGrades) ? $this->eventTargetGrades : null,
            'is_holiday' => $this->eventIsHoliday,
            'disable_kbm' => $this->eventDisableKbm,
            'requires_attendance' => $this->eventRequiresAttendance,
            'start_date' => $this->eventStartDate,
            'end_date' => $this->eventEndDate,
            'is_all_day' => $this->eventIsAllDay,
            'start_time' => $this->eventIsAllDay ? null : $this->eventStartTime,
            'end_time' => $this->eventIsAllDay ? null : $this->eventEndTime,
            'description' => $this->eventDescription,
            'created_by' => Auth::id(),
        ];

        if ($this->editingEventId) {
            AcademicEvent::where('id', $this->editingEventId)->update($payload);
            Notification::make()->success()->title('Agenda Berhasil Diperbarui')->send();
        } else {
            AcademicEvent::create($payload);
            Notification::make()->success()->title('Agenda Baru Berhasil Ditambahkan')->send();
        }

        $this->closeEventModal();
    }

    public function editEventAction(): Action
    {
        return EditAction::make('editEvent')
            ->record(fn (array $arguments) => AcademicEvent::find($arguments['id'] ?? null))
            ->icon('heroicon-m-pencil-square')
            ->iconButton()
            ->color('gray')
            ->tooltip('Edit Agenda')
            ->visible(fn (): bool => $this->canManage())
            ->modalHeading('Edit Agenda Akademik')
            ->modalWidth('2xl')
            ->form($this->getEventFormSchema())
            ->successNotificationTitle('Agenda Berhasil Diperbarui');
    }

    public function getEventFormSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Nama / Judul Agenda')
                    ->required()
                    ->placeholder('Contoh: Asesmen Sumatif Akhir Semester'),

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
            ]),

            Grid::make(2)->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->afterOrEqual('start_date'),
            ]),

            Grid::make(2)->schema([
                Forms\Components\CheckboxList::make('target_grades')
                    ->label('Target Tingkat Siswa')
                    ->options([
                        'X' => 'Kelas X',
                        'XI' => 'Kelas XI',
                        'XII' => 'Kelas XII',
                    ])
                    ->columns(3),

                Grid::make(1)->schema([
                    Forms\Components\Toggle::make('is_all_day')
                        ->label('Seharian Penuh (All Day)')
                        ->default(true)
                        ->live(),

                    Grid::make(2)->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->seconds(false),
                    ])->visible(fn ($get): bool => ! (bool) $get('is_all_day')),
                ]),
            ]),

            Grid::make(3)->schema([
                Forms\Components\Toggle::make('is_holiday')
                    ->label('Hari Libur'),

                Forms\Components\Toggle::make('disable_kbm')
                    ->label('Nonaktifkan KBM'),

                Forms\Components\Toggle::make('requires_attendance')
                    ->label('Wajib Presensi')
                    ->default(true),
            ]),

            Forms\Components\TextInput::make('description')
                ->label('Catatan / Keterangan')
                ->placeholder('Catatan atau keterangan opsional...')
                ->columnSpanFull(),
        ];
    }

    public function deleteEventAction(): Action
    {
        return DeleteAction::make('deleteEvent')
            ->record(fn (array $arguments) => AcademicEvent::find($arguments['id'] ?? $this->editingEventId))
            ->icon('heroicon-m-trash')
            ->iconButton()
            ->color('danger')
            ->tooltip('Hapus Agenda')
            ->visible(fn (): bool => $this->canManage())
            ->modalHeading('Konfirmasi Hapus agenda')
            ->successNotificationTitle('Agenda Berhasil Dihapus')
            ->after(function (): void {
                $this->closeEventModal();
            });
    }

    public function deleteEvent(?int $id = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $eventId = $id ?? $this->editingEventId;
        if ($eventId) {
            AcademicEvent::where('id', $eventId)->delete();
            Notification::make()->success()->title('Agenda Berhasil Dihapus')->send();
            $this->closeEventModal();
        }
    }

    public function openSyncModal(): void
    {
        $this->syncYear = $this->currentYear;
        $this->showSyncModal = true;
    }

    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
    }

    public function syncNationalHolidays(AcademicCalendarService $service): void
    {
        if (! $this->canManage()) {
            return;
        }

        $academicYear = $this->selectedYear;
        if (! $academicYear) {
            Notification::make()->danger()->title('Tahun Ajaran Tidak Ditemukan')->send();
            return;
        }

        $this->isSyncing = true;

        try {
            $result = $service->syncNationalHolidays($academicYear, $this->syncYear);

            Notification::make()
                ->success()
                ->title('Sinkronisasi Berhasil')
                ->body("Berhasil mengimpor {$result['total_synced']} hari libur nasional resmi ke semester {$result['semester']}.")
                ->send();

            $this->closeSyncModal();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Sinkronisasi Gagal')
                ->body($e->getMessage())
                ->send();
        } finally {
            $this->isSyncing = false;
        }
    }

    public function getAcademicYearsProperty()
    {
        return AcademicYear::orderByDesc('is_active')->orderByDesc('created_at')->get();
    }

    public function getSelectedYearProperty(): ?AcademicYear
    {
        return AcademicYear::find($this->selectedYearId) ?? AcademicYear::getActive();
    }

    public function getIsOutOfPeriodProperty(): bool
    {
        $year = $this->selectedYear;
        if (! $year || ! $year->start_date || ! $year->end_date) {
            return false;
        }

        $firstDay = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $lastDay = $firstDay->copy()->endOfMonth();

        return $lastDay->lt($year->start_date) || $firstDay->gt($year->end_date);
    }

    public function getCategoryCountsProperty(): array
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $events = AcademicEvent::query()
            ->where('start_date', '<=', $endOfMonth->toDateString())
            ->where('end_date', '>=', $startOfMonth->toDateString())
            ->when($this->selectedYearId, fn ($q) => $q->where('academic_year_id', $this->selectedYearId))
            ->when($this->selectedGrade, fn ($q) => $q->where(fn ($sub) => $sub->whereNull('target_grades')->orWhereJsonContains('target_grades', $this->selectedGrade)))
            ->get();

        $counts = [];
        foreach (AcademicEventCategory::cases() as $cat) {
            $counts[$cat->value] = $events->where('category', $cat)->count();
        }

        return $counts;
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getCalendarWeeksProperty(): array
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        if ($this->viewMode === 'week') {
            $today = Carbon::today();
            if ($today->year === $this->currentYear && $today->month === $this->currentMonth) {
                $calendarStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            } else {
                $calendarStart = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
            }
            $calendarEnd = $calendarStart->copy()->endOfWeek(Carbon::SUNDAY);
        } else {
            $calendarStart = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
            $calendarEnd = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);
        }

        // Tarik semua agenda dalam 1 query tunggal (mencegah N+1)
        $eventsQuery = AcademicEvent::query()
            ->where('start_date', '<=', $calendarEnd->toDateString())
            ->where('end_date', '>=', $calendarStart->toDateString());

        if ($this->selectedYearId) {
            $eventsQuery->where('academic_year_id', $this->selectedYearId);
        }

        if ($this->selectedGrade) {
            $eventsQuery->where(function ($q) {
                $q->whereNull('target_grades')
                    ->orWhereJsonContains('target_grades', $this->selectedGrade);
            });
        }

        if (! empty($this->selectedCategories)) {
            $eventsQuery->whereIn('category', $this->selectedCategories);
        }

        if (filled($this->searchQuery)) {
            $search = '%' . trim($this->searchQuery) . '%';
            $eventsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $allEvents = $eventsQuery
            ->orderByRaw('CASE WHEN start_date != end_date THEN 0 ELSE 1 END')
            ->orderBy('start_date')
            ->orderBy('end_date', 'desc')
            ->orderBy('id')
            ->get();

        $weeks = [];
        $today = Carbon::today();
        $currentDay = $calendarStart->copy();

        while ($currentDay->lte($calendarEnd)) {
            $weekDays = [];

            for ($i = 0; $i < 7; $i++) {
                $dateStr = $currentDay->toDateString();
                $isCurrentMonth = $currentDay->month === $this->currentMonth;
                $isToday = $currentDay->isSameDay($today);
                $isWeekend = $currentDay->isWeekend();
                $isSunday = $currentDay->dayOfWeek === Carbon::SUNDAY;

                // Filter memori O(1)
                $dayEvents = $allEvents->filter(function ($ev) use ($currentDay) {
                    return $currentDay->between($ev->start_date, $ev->end_date);
                });

                $weekDays[] = [
                    'date' => $dateStr,
                    'day_number' => $currentDay->day,
                    'is_current_month' => $isCurrentMonth,
                    'is_today' => $isToday,
                    'is_weekend' => $isWeekend,
                    'is_sunday' => $isSunday,
                    'events' => $dayEvents,
                ];

                $currentDay->addDay();
            }

            $weeks[] = ['days' => $weekDays];
        }

        return $weeks;
    }

    public function getMonthEventsProperty()
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $query = AcademicEvent::query()
            ->where('start_date', '<=', $endOfMonth->toDateString())
            ->where('end_date', '>=', $startOfMonth->toDateString())
            ->when($this->selectedYearId, fn ($q) => $q->where('academic_year_id', $this->selectedYearId))
            ->when($this->selectedGrade, fn ($q) => $q->where(fn ($sub) => $sub->whereNull('target_grades')->orWhereJsonContains('target_grades', $this->selectedGrade)))
            ->when(! empty($this->selectedCategories), fn ($q) => $q->whereIn('category', $this->selectedCategories))
            ->when(filled($this->searchQuery), function ($q) {
                $search = '%' . trim($this->searchQuery) . '%';
                $q->where(fn ($sub) => $sub->where('title', 'like', $search)->orWhere('description', 'like', $search));
            });

        return $query->orderBy('start_date')->get();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
