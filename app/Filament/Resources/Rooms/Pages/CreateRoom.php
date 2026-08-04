<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Resources\Rooms\RoomResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Redirect;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected static ?string $title = "Tambah Ruangan";

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
