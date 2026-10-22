<?php

namespace App\Filament\Resources\SchoolSessions\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\SchoolSessions\SchoolSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolSession extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = SchoolSessionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
