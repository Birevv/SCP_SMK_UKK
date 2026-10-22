<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Concerns\HasCreateConfirmation;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLeaveRequest extends CreateRecord
{
    use HasCreateConfirmation;

    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        if ($user && ($user->hasRole('Siswa') || $user->role?->name === 'Siswa')) {
            $data['student_id'] = $user->id;
            $data['status'] = 'Pending';
        }
        $data['submitted_by'] = Auth::id();

        if (($data['status'] ?? 'Pending') !== 'Rejected') {
            $data['rejection_note'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
