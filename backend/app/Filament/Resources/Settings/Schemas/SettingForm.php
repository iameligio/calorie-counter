<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                \Filament\Schemas\Components\TextInput::make('value')
                    ->required()
                    ->numeric(),
            ]);
    }
}
