<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WeightSamplesRelationManager extends RelationManager
{
    protected static string $relationship = 'weightSamples';

    protected static ?string $title = 'Weight Samples';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),
                Forms\Components\TextInput::make('sample_size')
                    ->label('Sample Size')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(1000)
                    ->suffix('birds'),
                Forms\Components\TextInput::make('avg_weight_g')
                    ->label('Avg Weight')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(10000)
                    ->step(0.01)
                    ->suffix('g'),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sample_size')
                    ->label('Sample')
                    ->numeric(),
                Tables\Columns\TextColumn::make('avg_weight_g')
                    ->label('Avg Weight (g)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Sample'),
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
