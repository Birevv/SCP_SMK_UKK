<?php

namespace App\Filament\Resources\Rooms\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Rooms\RoomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoom extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = RoomResource::class;

    protected static ?string $title = 'Edit Ruangan';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
