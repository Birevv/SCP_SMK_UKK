<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Edit Pengguna';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
