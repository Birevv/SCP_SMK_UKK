<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAcademicYear extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = AcademicYearResource::class;

    protected static ?string $title = 'Tambah Tahun Ajaran';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
