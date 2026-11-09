<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserArchives\Pages;

use App\Filament\Resources\UserArchives\UserArchiveResource;
use Filament\Resources\Pages\ListRecords;

class ListUserArchives extends ListRecords
{
    protected static string $resource = UserArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
