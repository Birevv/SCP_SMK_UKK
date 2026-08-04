<?php

namespace App\Filament\Resources\SystemConfigurations\Pages;

use App\Filament\Resources\SystemConfigurations\SystemConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSystemConfigurations extends ListRecords
{
    protected static string $resource = SystemConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => static::getResource()::canCreate()),
        ];
    }
}
