<?php

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;

    protected static ?string $title = 'Tambah Mata Pelajaran';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
