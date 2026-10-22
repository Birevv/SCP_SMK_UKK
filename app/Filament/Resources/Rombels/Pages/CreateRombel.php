<?php

namespace App\Filament\Resources\Rombels\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Rombels\RombelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRombel extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = RombelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
