<?php

namespace App\Filament\Resources\ClassLevels\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\ClassLevels\ClassLevelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassLevel extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = ClassLevelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
