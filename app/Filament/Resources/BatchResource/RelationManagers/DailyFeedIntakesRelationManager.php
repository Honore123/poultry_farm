<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DailyFeedIntakesRelationManager extends RelationManager
{
    protected static string $relationship = 'dailyFeedIntakes';

    protected static ?string $title = 'Feed Intake';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),
                Forms\Components\Select::make('feed_item_id')
                    ->relationship('feedItem', 'name', fn ($query) => $query->where('category', 'feed'))
                    ->label('Feed Type')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('kg_given')
                    ->label('Kg Given')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(10000)
                    ->step(0.01)
                    ->suffix('kg'),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('feedItem.name')
                    ->label('Feed'),
                Tables\Columns\TextColumn::make('kg_given')
                    ->label('Kg')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Feed'),
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
