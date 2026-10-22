<?php

namespace App\Filament\Resources\Majors\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Majors\MajorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMajor extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = MajorResource::class;

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
