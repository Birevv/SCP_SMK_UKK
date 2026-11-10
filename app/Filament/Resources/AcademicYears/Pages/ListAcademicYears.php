<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Enums\AcademicEventCategory;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\KaldikImportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListAcademicYears extends ListRecords
{
    use WithFileUploads;

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

    public bool $showCulturalDates = true; // Toggle tampilan penanggalan Jawa & Hijriah pada grid

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

    // Modal Sinkronisasi Libur Nasional & Kalender Budaya
    public bool $showSyncModal = false;

    public int $syncYear = 2026;

    public bool $isSyncing = false;

    public bool $syncNationalHolidaysOption = true;

    public bool $syncIslamicHolidaysOption = true;

    public bool $syncJavaneseCulturalOption = true;

    public bool $includeKamisPahingOption = false;

    public bool $includeGagrakOption = true;

    public string $gagrakHariOption = 'Kamis';

    public string $gagrakPasaranOption = 'Pon';

    public string $gagrakLabelOption = 'Gagrak Surakarta';

    public bool $syncFullYearOption = true;

    // Modal & State Import Kaldik
    public bool $showImportKaldikModal = false;

    public $kaldikFile = null;

    public ?array $kaldikPreviewData = null;

    public string|int|null $targetKaldikYearId = 'auto';

    public bool $autoSyncKaldikDates = true;

    public bool $isImportingKaldik = false;

    public ?string $kaldikImportError = null;

    public string $kaldikPreviewTab = 'events'; // 'events' or 'effective_weeks'

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

        if (! $this->canManage()) {
            $this->currentViewTab = 'calendar';
        }
    }

    public function canManage(): bool
    {
        /** @var User|null $user */
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
        if ($tab === 'years' && ! $this->canManage()) {
            $this->currentViewTab = 'calendar';

            return;
        }

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

    public function deleteAllMonthEventsAction(): Action
    {
        return Action::make('deleteAllMonthEvents')
            ->label('Hapus Semua Agenda Bulan Ini')
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->size('sm')
            ->outlined()
            ->visible(fn (): bool => $this->canManage() && $this->monthEvents->isNotEmpty())
            ->requiresConfirmation()
            ->modalHeading(fn () => 'Hapus Semua Agenda Bulan '.Carbon::create($this->currentYear, $this->currentMonth, 1)->locale('id')->translatedFormat('F Y'))
            ->modalDescription(function () {
                $count = $this->monthEvents->count();
                $monthName = Carbon::create($this->currentYear, $this->currentMonth, 1)->locale('id')->translatedFormat('F Y');

                return "Apakah Anda yakin ingin menghapus seluruh {$count} agenda pada bulan {$monthName}? Data agenda yang dihapus tidak dapat dipulihkan.";
            })
            ->modalSubmitActionLabel('Ya, Hapus Semua')
            ->action(function (): void {
                $this->deleteAllMonthEvents();
            });
    }

    public function deleteAllMonthEvents(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $events = $this->monthEvents;
        $count = $events->count();

        if ($count === 0) {
            Notification::make()
                ->warning()
                ->title('Tidak Ada Agenda')
                ->body('Tidak ada agenda yang dapat dihapus pada bulan ini.')
                ->send();

            return;
        }

        AcademicEvent::whereIn('id', $events->pluck('id'))->delete();

        $monthName = Carbon::create($this->currentYear, $this->currentMonth, 1)->locale('id')->translatedFormat('F Y');

        Notification::make()
            ->success()
            ->title('Semua Agenda Berhasil Dihapus')
            ->body("Sebanyak {$count} agenda pada bulan {$monthName} telah berhasil dihapus.")
            ->send();
    }

    public function openSyncModal(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->syncYear = $this->currentYear;
        $this->syncNationalHolidaysOption = true;
        $this->syncIslamicHolidaysOption = true;
        $this->syncJavaneseCulturalOption = true;
        $this->includeKamisPahingOption = false;
        $this->includeGagrakOption = true;
        $this->syncFullYearOption = true;
        $this->showSyncModal = true;
    }

    public function setGagrakPreset(string $preset): void
    {
        if ($preset === 'surakarta' || $preset === 'kamis_pon') {
            $this->gagrakHariOption = 'Kamis';
            $this->gagrakPasaranOption = 'Pon';
            $this->gagrakLabelOption = 'Gagrak Surakarta';
        } elseif ($preset === 'ngayogyakarta' || $preset === 'kamis_pahing') {
            $this->gagrakHariOption = 'Kamis';
            $this->gagrakPasaranOption = 'Pahing';
            $this->gagrakLabelOption = 'Gagrak Ngayogyakarta';
        }
    }

    public function closeSyncModal(): void
    {
        $this->showSyncModal = false;
    }

    public function toggleCulturalDates(): void
    {
        $this->showCulturalDates = ! $this->showCulturalDates;
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
            $totalSynced = 0;
            $summaryParts = [];

            if ($this->syncNationalHolidaysOption) {
                $natResult = $service->syncNationalHolidays($academicYear, $this->syncYear, $this->syncFullYearOption);
                $totalSynced += $natResult['total_synced'];
                $summaryParts[] = "{$natResult['total_synced']} Libur Nasional";
            }

            if ($this->syncIslamicHolidaysOption || $this->syncJavaneseCulturalOption) {
                $cultResult = $service->syncCulturalEvents(
                    $academicYear,
                    $this->syncYear,
                    $this->syncIslamicHolidaysOption,
                    $this->syncJavaneseCulturalOption,
                    includeGagrak: $this->includeGagrakOption,
                    gagrakHari: $this->gagrakHariOption,
                    gagrakPasaran: $this->gagrakPasaranOption,
                    gagrakTitle: "{$this->gagrakHariOption} {$this->gagrakPasaranOption} (Busana Adat Jawa {$this->gagrakLabelOption})",
                    forceFullYear: $this->syncFullYearOption
                );
                $totalSynced += $cultResult['total_synced'];
                if ($this->syncIslamicHolidaysOption) {
                    $summaryParts[] = "{$cultResult['islam_synced']} Hari Besar Islam";
                }
                if ($this->syncJavaneseCulturalOption) {
                    $summaryParts[] = "{$cultResult['jawa_synced']} Tradisi Jawa";
                }
            }

            $summaryText = ! empty($summaryParts) ? implode(', ', $summaryParts) : '0 agenda';

            Notification::make()
                ->success()
                ->title('Sinkronisasi Kalender Berhasil')
                ->body("Berhasil menyinkronkan {$summaryText} ({$totalSynced} total agenda) ke semester {$academicYear->name}.")
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

    public function openImportKaldikModal(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->resetImportKaldikState();
        $this->showImportKaldikModal = true;
    }

    public function closeImportKaldikModal(): void
    {
        $this->showImportKaldikModal = false;
        $this->resetImportKaldikState();
    }

    public function resetImportKaldikState(): void
    {
        $this->kaldikFile = null;
        $this->kaldikPreviewData = null;
        $this->targetKaldikYearId = 'auto';
        $this->autoSyncKaldikDates = true;
        $this->isImportingKaldik = false;
        $this->kaldikImportError = null;
        $this->kaldikPreviewTab = 'events';
        $this->resetErrorBag();
    }

    public function switchKaldikPreviewTab(string $tab): void
    {
        $this->kaldikPreviewTab = $tab;
    }

    public function updatedKaldikFile(): void
    {
        if (! $this->kaldikFile) {
            return;
        }

        $this->validate([
            'kaldikFile' => 'required|file|max:20480',
        ], [
            'kaldikFile.required' => 'File Kaldik wajib diunggah.',
            'kaldikFile.file' => 'File yang diunggah tidak valid.',
            'kaldikFile.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        try {
            $service = app(KaldikImportService::class);
            $realPath = $this->kaldikFile->getRealPath();
            $this->kaldikPreviewData = $service->parse($realPath);
            $this->kaldikImportError = null;

            if (empty($this->kaldikPreviewData['events'])) {
                $this->kaldikImportError = 'Tidak ada agenda yang terdeteksi dari file. Pastikan format file sesuai dengan Kalender Pendidikan resmi SMKN 1 Bantul atau format standar.';
            }
        } catch (\Throwable $e) {
            $this->kaldikImportError = 'Gagal membaca file: '.$e->getMessage();
            $this->kaldikPreviewData = null;
        }
    }

    public function executeImportKaldik(KaldikImportService $service): void
    {
        if (! $this->canManage()) {
            return;
        }

        if (! $this->kaldikPreviewData || empty($this->kaldikPreviewData['events'])) {
            if ($this->kaldikFile) {
                $this->updatedKaldikFile();
            } else {
                Notification::make()
                    ->danger()
                    ->title('Pilih File Terlebih Dahulu')
                    ->body('Silakan pilih file Kalender Pendidikan (Kaldik) yang ingin diimpor.')
                    ->send();

                return;
            }
        }

        if (empty($this->kaldikPreviewData['events'])) {
            Notification::make()
                ->danger()
                ->title('Impor Gagal')
                ->body('Tidak ada data agenda yang dapat diimpor dari file tersebut.')
                ->send();

            return;
        }

        $this->isImportingKaldik = true;

        try {
            $result = $service->import(
                $this->kaldikPreviewData,
                $this->targetKaldikYearId,
                $this->autoSyncKaldikDates,
                Auth::id()
            );

            $affectedStr = implode(', ', $result['academic_years_affected']);

            Notification::make()
                ->success()
                ->title('Import Kaldik Berhasil!')
                ->body("Berhasil mengimpor {$result['total_events']} agenda ({$result['created_count']} baru, {$result['updated_count']} diperbarui) ke Tahun Ajaran {$affectedStr}.")
                ->send();

            // Refresh tampilan & aktifkan semester yang relevan jika ada
            $importedYear = null;
            if (is_numeric($this->targetKaldikYearId) && (int) $this->targetKaldikYearId > 0) {
                $importedYear = AcademicYear::find($this->targetKaldikYearId);
            } else {
                $importedYear = AcademicYear::whereIn('name', $result['academic_years_affected'])->first();
            }

            if ($importedYear) {
                $this->selectedYearId = $importedYear->id;
                if ($importedYear->start_date) {
                    $this->currentMonth = $importedYear->start_date->month;
                    $this->currentYear = $importedYear->start_date->year;
                }
            }

            $this->closeImportKaldikModal();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Import Kaldik Gagal')
                ->body($e->getMessage())
                ->send();
        } finally {
            $this->isImportingKaldik = false;
        }
    }

    public function downloadSampleKaldik(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Format_Kaldik_SMKN1_Bantul_2026_2027.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            $sampleLines = [
                'KALENDER PENDIDIKAN TAHUN AJARAN 2026/2027,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
                'SMKN 1 BANTUL,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
                ',,,,,,,,,,,,,,,,,,,,,,,,,,,,',
                'JULI 2026,,,,,,,,AGUSTUS 2026,,,,,,,,SEPTEMBER 2026,,,,,,,,,,,,',
                'AHAD,,5,12,19,26,,,AHAD,,2,9,16,23,30,,AHAD,,6,13,20,27,,,,,,,',
                'SENIN,,6,13,20,27,,,SENIN,,3,10,17,24,31,,SENIN,,7,14,21,28,,,,,,,',
                'SELASA,,7,14,21,28,,,SELASA,,4,11,18,25,,,SELASA,1,8,15,22,29,,,,,,,',
                'RABU,1,8,15,22,29,,,RABU,,5,12,19,26,,,RABU,2,9,16,23,30,,,,,,,',
                'KAMIS,2,9,16,23,30,,,KAMIS,,6,13,20,27,,,KAMIS,3,10,17,24,,,,,,,,',
                'JUMÁT,3,10,17,24,31,,,JUMÁT,,7,14,21,28,,,JUMÁT,4,11,18,25,,,,,,,,',
                'SABTU,4,11,18,25,,,,SABTU,1,8,15,22,29,,,SABTU,5,12,19,26,,,,,,,,',
                '1 - 10 Juli 2025 : Libur Akhir Tahun Ajaran 2025/2026,,,,,,,,17 Agustus 2026 : Hari Kemerdekaan RI ke-81,,,,,,,,"14 s.d 18 Sept. 2026: PSTS Ganjil kelas X, XI ",,,,,,,,,,,,',
                '13 Juli 2026 : Hari Pertama Masuk Sekolah 2026/2027,,,,,,,,25 Agustus 2026 : Maulid Nabi Muhammad SAW,,,,,,,,& PSAS Ganjil kelas XII,,,,,,,,,,,,',
                '13 - 17 Juli 2026 : MPLS Tahun Ajaran Baru 2026/2027,,,,,,,,,,,,,,,,21 s.d 27 Sept 2026 : Simulasi TKA/AN,,,,,,,,,,,,',
                '27-31 Juli 2026 : Rentang pelaksanaan TOEIC,,,,,,,,,,,,,,,,,,,,,,,,,,,,',
                'OKTOBER 2026,,,,,,,,NOVEMBER 2026,,,,,,,,DESEMBER 2026,,,,,,,,,,,,',
                'AHAD,,4,11,18,25,,,AHAD,,1,8,15,22,29,,AHAD,,6,13,20,27,,,,,,,22000',
                'SENIN,,5,12,19,26,,,SENIN,,2,9,16,23,30,,SENIN,,7,14,21,28,,,,,,,',
                'SELASA,,6,13,20,27,,,SELASA,,3,10,17,24,,,SELASA,1,8,15,22,29,,,,,,,',
                'RABU,,7,14,21,28,,,RABU,,4,11,18,25,,,RABU,2,9,16,23,30,,,,,,,78700',
                'KAMIS,1,8,15,22,29,,,KAMIS,,5,12,19,26,,,KAMIS,3,10,17,24,31,,,,,,,',
                'JUMÁT,2,9,16,23,30,,,JUMÁT,,6,13,20,27,,,JUMÁT,4,11,18,25,,,,,,,,',
                'SABTU,3,10,17,24,31,,,SABTU,,7,14,21,28,,,SABTU,5,12,19,26,,,,,,,,',
                '26-29 Oktober 2026: TKA & AN kelas XII,,,,,,,,9-13 November 2026 : UKK Mandiri ,,,,,,,,1-4 Desember 2026 : USEK  & PSAS ,,,,,,,,,,,,',
                '5-8 Oktober 2026 : Gladi Bersih TKA/AN,,,,,,,,16-19 November 2026 : Tes Susulan TKA & AN (Tentatif),,,,,,,,18 Desember 2026 : Penerimaan Rapor Sem. 1,,,,,,,,,,,,',
                ',,,,,,,,25 November 2026 : Hari Guru Nasional,,,,,,,,21 - 31 Desember 2026 : Libur Akhir Semester 1,,,,,,,,,,,,',
                ',,,,,,,,26-28 November 2026 : USEK & PSAS Ganjil ,,,,,,,,25 Desember 2026 : Hari Raya Natal,,,,,,,,,,,,',
                ',,,,,,,,,,,,,,,,26 Desember 2026 : Cuti Bersama Hari Natal,,,,,,,,,,,,',
                ',,,,,,,,,,,,,,,,14 Desember 2026 : Penerjunan siswa PKL,,,,,,,,,,,,',
            ];
            foreach ($sampleLines as $line) {
                fwrite($handle, $line."\r\n");
            }
            fclose($handle);
        }, 200, $headers);
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
            $search = '%'.trim($this->searchQuery).'%';
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

        /** @var \App\Services\CulturalCalendarService $culturalService */
        $culturalService = app(\App\Services\CulturalCalendarService::class);
        $culturalMonthData = $culturalService->getMonthlyCalendarData($this->currentYear, $this->currentMonth);

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

                $cData = $culturalMonthData[$dateStr] ?? $culturalService->getHijriAndJawaDate($currentDay);

                $weekDays[] = [
                    'date' => $dateStr,
                    'day_number' => $currentDay->day,
                    'is_current_month' => $isCurrentMonth,
                    'is_today' => $isToday,
                    'is_weekend' => $isWeekend,
                    'is_sunday' => $isSunday,
                    'events' => $dayEvents,
                    'pasaran' => $cData['pasaran'] ?? '',
                    'weton' => $cData['weton'] ?? '',
                    'hijri_day' => $cData['hijri_day'] ?? null,
                    'hijri_short' => $cData['hijri_short'] ?? '',
                    'hijri_formatted' => $cData['hijri_formatted'] ?? '',
                    'jawa_month_name' => $cData['jawa_month_name'] ?? '',
                    'is_gagrak' => $cData['is_gagrak'] ?? ($cData['is_kamis_pahing'] ?? false),
                    'is_kamis_pahing' => $cData['is_kamis_pahing'] ?? false,
                    'is_suro' => $cData['is_suro'] ?? false,
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
                $search = '%'.trim($this->searchQuery).'%';
                $q->where(fn ($sub) => $sub->where('title', 'like', $search)->orWhere('description', 'like', $search));
            });

        return $query->orderBy('start_date')->get();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
