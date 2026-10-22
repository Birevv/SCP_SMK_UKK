<?php

namespace App\Filament\Resources\SchoolSessions;

use App\Filament\Resources\SchoolSessions\Pages\CreateSchoolSession;
use App\Filament\Resources\SchoolSessions\Pages\EditSchoolSession;
use App\Filament\Resources\SchoolSessions\Pages\ListSchoolSessions;
use App\Filament\Resources\SchoolSessions\Schemas\SchoolSessionForm;
use App\Filament\Resources\SchoolSessions\Tables\SchoolSessionsTable;
use App\Models\SchoolSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SchoolSessionResource extends Resource
{
    protected static ?string $model = SchoolSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Akademik';

    protected static ?string $modelLabel = 'Sesi Jam Pelajaran';

    protected static ?string $navigationLabel = 'Konfigurasi Jam Sesi';

    protected static ?string $pluralLabel = 'Konfigurasi Jam Sesi';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return SchoolSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolSessionsTable::configure($table);
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
            'index' => ListSchoolSessions::route('/'),
            'create' => CreateSchoolSession::route('/create'),
            'edit' => EditSchoolSession::route('/{record}/edit'),
        ];
    }
}
