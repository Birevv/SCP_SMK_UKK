<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Resources\Journals\JournalResource;
use App\Models\Schedule;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['teacher_id']) && ! empty($data['schedule_id'])) {
            $schedule = Schedule::find($data['schedule_id']);
            if ($schedule) {
                $data['teacher_id'] = $schedule->teacher_id;
            }
        }

        return $data;
    }
}
