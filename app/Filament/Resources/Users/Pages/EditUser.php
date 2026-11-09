<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasEditConfirmation;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use HasEditConfirmation;

    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Edit Pengguna';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Arsipkan Pengguna')
                ->modalDescription(fn (User $record): string => "Pindahkan '{$record->full_name}' ke Arsip Terhapus (Backup)? Data jurnal dan presensi tetap aman serta dapat dipulihkan kapan saja oleh Super Admin.")
                ->successNotificationTitle('Pengguna berhasil dipindahkan ke Arsip Terhapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
