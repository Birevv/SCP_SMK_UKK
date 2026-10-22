<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Resources\Journals\Schemas\JournalForm;
use App\Models\Attendance;
use App\Models\Schedule;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = JournalResource::class;

    /**
     * @var array<int, array{student_id: int, status: string, notes: ?string}>
     */
    protected array $customAttendances = [];

    public function mount(): void
    {
        parent::mount();

        $scheduleId = request()->query('schedule_id');
        if ($scheduleId) {
            $date = $this->data['date'] ?? now()->toDateString();
            $this->form->fill([
                'schedule_id' => (int) $scheduleId,
                'date' => $date,
                'attendances' => JournalForm::getStudentsAttendanceData((int) $scheduleId, $date),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
