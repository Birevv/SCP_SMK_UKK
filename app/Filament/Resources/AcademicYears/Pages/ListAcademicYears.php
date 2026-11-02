<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Enums\AcademicEventCategory;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
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

    public function openCreateEventModal(?string $date = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $defaultDate = $date ?? Carbon::create($this->currentYear, $this->currentMonth, 1)->toDateString();

        $this->editingEventId = null;
        $this->eventTitle = '';
        $this->eventCategory = AcademicEventCategory::KEGIATAN_SISWA->value;
        $this->eventTargetGrades = [];
        $this->eventIsHoliday = false;
        $this->eventDisableKbm = false;
        $this->eventRequiresAttendance = true;
        $this->eventStartDate = $defaultDate;
        $this->eventEndDate = $defaultDate;
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

        $event = AcademicEvent::findOrFail($id);

        $this->editingEventId = $event->id;
        $this->eventTitle = $event->title;
        $this->eventCategory = $event->category instanceof AcademicEventCategory ? $event->category->value : $event->category;
        $this->eventTargetGrades = $event->target_grades ?? [];
        $this->eventIsHoliday = (bool) $event->is_holiday;
        $this->eventDisableKbm = (bool) $event->disable_kbm;
        $this->eventRequiresAttendance = (bool) $event->requires_attendance;
        $this->eventStartDate = $event->start_date->toDateString();
        $this->eventEndDate = $event->end_date->toDateString();
        $this->eventIsAllDay = (bool) $event->is_all_day;
        $this->eventStartTime = $event->start_time;
        $this->eventEndTime = $event->end_time;
        $this->eventDescription = $event->description;

        $this->showEventModal = true;
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

        $allEvents = $eventsQuery->orderBy('start_date')->get();

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
