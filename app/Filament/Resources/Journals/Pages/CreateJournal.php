<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Resources\Journals\Schemas\JournalForm;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateJournal extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = JournalResource::class;

    protected static ?string $title = 'Buat Jurnal';

    /**
     * @var array<int, array{student_id: int, status: string, notes: ?string}>
     */
    protected array $customAttendances = [];

    public function mount(): void
    {
        parent::mount();

        $scheduleId = request()->query('schedule_id');
        $queryDate = request()->query('date');

        $date = filled($queryDate) ? (string) $queryDate : ($this->data['date'] ?? now()->toDateString());

        if ($scheduleId || filled($queryDate)) {
            $fillData = array_merge($this->data ?? [], [
                'date' => $date,
            ]);

            if ($scheduleId) {
                $fillData['schedule_id'] = (int) $scheduleId;
                $fillData['attendances'] = JournalForm::getStudentsAttendanceData((int) $scheduleId, $date);
            }

            $this->form->fill($fillData);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser && ($currentUser->hasRole('Super Admin') || $currentUser->role?->name === 'Super Admin');

        if (! $isSuperAdmin && ! empty($data['date'])) {
            $journalDate = Carbon::parse($data['date'])->startOfDay();
            $today = Carbon::today();

            if ($journalDate->isAfter($today)) {
                throw ValidationException::withMessages([
                    'date' => 'Jurnal mengajar hanya dapat diisi pada hari pembelajaran (hari ini). Pengisian untuk tanggal mendatang / besok tidak diperbolehkan.',
                ]);
            }

            if ($journalDate->isBefore($today)) {
                throw ValidationException::withMessages([
                    'date' => 'Pengisian jurnal masa lalu tidak diperbolehkan. Jurnal hanya dapat diisi pada hari jadwal mengajar (hari ini).',
                ]);
            }
        }

        if (isset($data['attendances'])) {
            $this->customAttendances = (array) $data['attendances'];
            unset($data['attendances']);
        }

        if (empty($data['teacher_id']) && ! empty($data['schedule_id'])) {
            $schedule = Schedule::find($data['schedule_id']);
            if ($schedule) {
                $data['teacher_id'] = $schedule->teacher_id;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->customAttendances)) {
            foreach ($this->customAttendances as $attendanceData) {
                if (! empty($attendanceData['student_id'])) {
                    Attendance::updateOrCreate(
                        [
                            'journal_id' => $this->record->id,
                            'student_id' => $attendanceData['student_id'],
                        ],
                        [
                            'status' => $attendanceData['status'] ?? 'Hadir',
                            'notes' => $attendanceData['notes'] ?? null,
                            'date' => $this->record->date,
                        ]
                    );
                }
            }
        }
    }
}
