<?php

namespace App\Filament\Resources\SystemConfigurations;

use App\Filament\Resources\SystemConfigurations\Pages\CreateSystemConfiguration;
use App\Filament\Resources\SystemConfigurations\Pages\EditSystemConfiguration;
use App\Filament\Resources\SystemConfigurations\Pages\ListSystemConfigurations;
use App\Filament\Resources\SystemConfigurations\Schemas\SystemConfigurationForm;
use App\Filament\Resources\SystemConfigurations\Tables\SystemConfigurationsTable;
use App\Models\SystemConfiguration;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SystemConfigurationResource extends Resource
{
    protected static ?string $model = SystemConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = "Settings";

      protected static ?string $modelLabel = "Konfigurasi Sistem";

    protected static ?string $navigationLabel = "Konfigurasi Sistem";

    protected static ?string $pluralLabel = "Konfigurasi Sistem";

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return SystemConfiguration::count() < 1;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }


    public static function form(Schema $schema): Schema
    {
        return SystemConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemConfigurationsTable::configure($table);
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
            'index' => ListSystemConfigurations::route('/'),
            'create' => CreateSystemConfiguration::route('/create'),
            'edit' => EditSystemConfiguration::route('/{record}/edit'),
        ];
    }
}
