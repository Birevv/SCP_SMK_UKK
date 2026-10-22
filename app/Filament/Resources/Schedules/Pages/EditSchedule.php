<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = ScheduleResource::class;

    protected static ?string $title = 'Edit Jadwal';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
