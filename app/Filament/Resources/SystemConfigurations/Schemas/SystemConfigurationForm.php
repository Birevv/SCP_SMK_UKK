<?php

namespace App\Filament\Resources\SystemConfigurations\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class SystemConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('active_cycle')
                    ->label('Putaran Aktif')
                    ->options(['Putaran 1' => 'Putaran 1', 'Putaran 2' => 'Putaran 2'])
                    ->default('Putaran 1')
                    ->required(),
                    
                Toggle::make('is_schedule_locked')
                    ->label('Kunci Jadwal Mengajar')
                    ->helperText('Jika diaktifkan, Guru tidak dapat menambah/mengedit jadwal.'),

                Select::make('updated_by')
                    ->label('Diubah Oleh')
                    ->relationship('updatedBy', 'full_name')
                    ->default(fn () => Auth::id())
                    ->disabled()
                    ->dehydrated(),
            ]);
    }
}
