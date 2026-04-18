<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FoodResource\Pages;
use App\Models\Food;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FoodResource extends Resource
{
    protected static ?string $model = Food::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-beaker';
    // protected static string | \UnitEnum | null $navigationGroup = 'Food Management';
    protected static ?int $navigationSort = 1;



    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Food Information')
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('calories_per_100g')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('kcal / 100g'),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Macronutrients (per 100g)')
                    ->components([
                        Forms\Components\TextInput::make('protein')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->default(0),
                        Forms\Components\TextInput::make('carbs')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->default(0),
                        Forms\Components\TextInput::make('fat')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->default(0),
                    ])->columns(3),

                \Filament\Schemas\Components\Section::make('Status')
                    ->components([
                        Forms\Components\Select::make('source')
                            ->options([
                                'manual' => 'System (Manual)',
                                'usda' => 'USDA',
                                'user' => 'User Submitted',
                            ])
                            ->default('manual')
                            ->required(),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Verified')
                            ->default(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('calories_per_100g')
                    ->label('Calories / 100g')
                    ->sortable()
                    ->suffix(' kcal'),
                Tables\Columns\TextColumn::make('protein')
                    ->suffix('g')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('carbs')
                    ->suffix('g')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fat')
                    ->suffix('g')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'primary',
                        'usda' => 'success',
                        'user' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->default('System')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'manual' => 'System (Manual)',
                        'usda' => 'USDA',
                        'user' => 'User Submitted',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Food $record): bool => !$record->is_verified)
                    ->action(fn (Food $record) => $record->update(['is_verified' => true])),
                Actions\Action::make('unverify')
                    ->label('Unverify')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Food $record): bool => $record->is_verified)
                    ->action(fn (Food $record) => $record->update(['is_verified' => false])),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('verify')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_verified' => true])),
                    Actions\BulkAction::make('unverify')
                        ->label('Unverify Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_verified' => false])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFoods::route('/'),
            'create' => Pages\CreateFood::route('/create'),
            'edit' => Pages\EditFood::route('/{record}/edit'),
        ];
    }
}
