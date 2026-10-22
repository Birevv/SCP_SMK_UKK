<?php

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubject extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = SubjectResource::class;

    protected static ?string $title = 'Edit Mata Pelajaran';

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
