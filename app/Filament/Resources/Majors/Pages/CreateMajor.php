<?php

namespace App\Filament\Resources\Majors\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Majors\MajorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMajor extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = MajorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
