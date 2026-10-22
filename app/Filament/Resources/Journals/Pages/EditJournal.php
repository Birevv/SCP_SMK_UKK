<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Journals\JournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = JournalResource::class;

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
