<?php

namespace App\Filament\Resources\Classrooms\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\Classrooms\ClassroomResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClassroom extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = ClassroomResource::class;

    protected static ?string $title = 'Tambah Kelas';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
