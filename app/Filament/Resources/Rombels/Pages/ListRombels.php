<?php

namespace App\Filament\Resources\Rombels\Pages;

use App\Filament\Resources\Rombels\RombelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRombels extends ListRecords
{
    protected static string $resource = RombelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
