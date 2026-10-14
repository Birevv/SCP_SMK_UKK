<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('ViewAny:User')
            || $user?->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($user?->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->can('Create:User')
            || $user?->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($user?->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($record && ($record->hasRole('Super Admin') || $record->role?->name === 'Super Admin')) {
            return $user?->hasRole('Super Admin') || $user?->role?->name === 'Super Admin';
        }

        return $user?->can('Update:User')
            || $user?->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($user?->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public static function canDelete($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($record && ($record->hasRole('Super Admin') || $record->role?->name === 'Super Admin')) {
            return false;
        }

        return $user?->can('Delete:User')
            || $user?->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($user?->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['role', 'classrooms']);
    }

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static string|UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $pluralLabel = 'Data Pengguna';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
