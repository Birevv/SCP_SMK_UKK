<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Models\LeaveRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user?->hasRole('Super Admin') || $user?->role?->name === 'Super Admin') {
            return true;
        }

        return $user?->hasAnyRole(['Wali Kelas', 'Guru BK', 'Siswa'])
            || in_array($user?->role?->name, ['Wali Kelas', 'Guru BK', 'Siswa']);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user?->hasRole('Super Admin') || $user?->role?->name === 'Super Admin') {
            return true;
        }

        return $user?->hasAnyRole(['Siswa', 'Wali Kelas'])
            || in_array($user?->role?->name, ['Siswa', 'Wali Kelas']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student', 'submittedBy', 'verifiedBy']);
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && ($user->hasRole('Siswa') || $user->role?->name === 'Siswa')) {
            $query->where('student_id', $user->id);
        }

        return $query;
    }

    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Presensi & Perizinan';

    protected static ?string $navigationLabel = 'Surat Izin / Sakit';

    protected static ?string $pluralLabel = 'Pengajuan Izin';

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
