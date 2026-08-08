<?php

namespace App\Filament\Resources\Journals;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\Journals\Pages\EditJournal;
use App\Filament\Resources\Journals\Pages\ListJournals;
use App\Filament\Resources\Journals\Schemas\JournalForm;
use App\Filament\Resources\Journals\Tables\JournalsTable;
use App\Models\Journal;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JournalResource extends Resource
{
    public static function canViewAny(): bool
    {
        $role = Auth::user()?->role?->name;
        return in_array($role, ['Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK']);
    }
    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Akademik';
    
    protected static ?string $navigationLabel = 'Jurnal Mengajar';

    protected static ?string $pluralLabel = 'Jurnal Mengajar';

    public static function form(Schema $schema): Schema
    {
        return JournalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalsTable::configure($table);
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
            'index' => ListJournals::route('/'),
            'create' => CreateJournal::route('/create'),
            'edit' => EditJournal::route('/{record}/edit'),
        ];
    }
}
