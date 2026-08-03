<?php

namespace App\Filament\Profile;

use Filament\Auth\Pages\EditProfile as PagesEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
/**
 * @property-read Schema $form
 */
class EditProfile extends PagesEditProfile
{

    protected function getRedirectUrl(): ?string
    {
        return '/admin';
    }
    public static function getLabel(): string
    {
        return "Edit Profile";
    }
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('full_name')
                    ->label('Full Name')
                    ->required(),

                TextInput::make('username')
                    ->required(),
            ]);
    }
}