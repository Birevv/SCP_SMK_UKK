<?php

namespace App\Filament\Resources\Journals\RelationManagers;

use App\Filament\Resources\Journals\Schemas\AttendanceForm;
use App\Filament\Resources\Journals\Tables\AttendancesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Daftar Presensi Siswa';

    public function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }
}
