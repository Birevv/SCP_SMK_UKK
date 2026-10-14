<?php

namespace App\Filament\Resources\SystemConfigurations\Pages;

use App\Filament\Resources\SystemConfigurations\SystemConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemConfiguration extends CreateRecord
{
    protected static string $resource = SystemConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
