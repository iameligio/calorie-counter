<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                \Filament\Forms\Components\TextInput::make('value')
                    ->required(),
                \Filament\Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }
}
