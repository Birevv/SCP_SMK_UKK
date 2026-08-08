<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
