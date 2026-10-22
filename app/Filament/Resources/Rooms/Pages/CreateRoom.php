<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Rooms\RoomResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = RoomResource::class;

    protected static ?string $title = 'Tambah Ruangan';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
