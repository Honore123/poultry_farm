<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DailyProductionsRelationManager extends RelationManager
{
    protected static string $relationship = 'dailyProductions';

    protected static ?string $title = 'Egg Production';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),
                Forms\Components\TextInput::make('eggs_total')
                    ->label('Total Eggs')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->maxValue(100000)
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('eggs_cracked')
                    ->label('Cracked')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->lte('eggs_total'),
                Forms\Components\TextInput::make('eggs_dirty')
                    ->label('Dirty')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->lte('eggs_total'),
                Forms\Components\TextInput::make('eggs_soft')
                    ->label('Soft Shell')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->lte('eggs_total'),
                Forms\Components\TextInput::make('egg_weight_avg_g')
                    ->label('Avg Weight (g)')
                    ->numeric()
                    ->minValue(20)
                    ->maxValue(120)
                    ->step(0.01),
                Forms\Components\TextInput::make('lighting_hours')
                    ->label('Light Hours')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(24)
                    ->step(0.1),
            ])->columns(4);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eggs_total')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eggs_cracked')
                    ->label('Cracked')
                    ->numeric(),
                Tables\Columns\TextColumn::make('eggs_dirty')
                    ->label('Dirty')
                    ->numeric(),
                Tables\Columns\TextColumn::make('eggs_soft')
                    ->label('Soft')
                    ->numeric(),
                Tables\Columns\TextColumn::make('egg_weight_avg_g')
                    ->label('Avg g')
                    ->numeric(decimalPlaces: 1),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Production'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
