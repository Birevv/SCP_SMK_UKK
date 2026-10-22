<?php

namespace App\Filament\Resources\ClassLevels\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\ClassLevels\ClassLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClassLevel extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = ClassLevelResource::class;

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
