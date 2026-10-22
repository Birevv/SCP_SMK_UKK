<?php

namespace App\Filament\Resources\SchoolSessions\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\SchoolSessions\SchoolSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolSession extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = SchoolSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
