<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedIntakeTargetResource\Pages;
use App\Models\FeedIntakeTarget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeedIntakeTargetResource extends Resource
{
    protected static ?string $model = FeedIntakeTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feed Target Details')
                    ->schema([
                        Forms\Components\TextInput::make('stage')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Chick, Grower, Pre-lay, Layer'),
                        Forms\Components\TextInput::make('min_week')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->label('Min Week'),
                        Forms\Components\TextInput::make('max_week')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->label('Max Week'),
                        Forms\Components\TextInput::make('grams_per_bird_per_day_min')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->label('Min g/bird/day')
                            ->suffix('g')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                $set('min_kg_per_week_display', $state ? round($state * 7 / 1000, 3) : null)
                            ),
                        Forms\Components\TextInput::make('grams_per_bird_per_day_max')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->label('Max g/bird/day')
                            ->suffix('g')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                $set('max_kg_per_week_display', $state ? round($state * 7 / 1000, 3) : null)
                            ),
                    ])->columns(2),
                Forms\Components\Section::make('Weekly Projection (per bird)')
                    ->description('Calculated based on daily intake × 7 days')
                    ->schema([
                        Forms\Components\Placeholder::make('min_kg_per_week_display')
                            ->label('Min kg/bird/week')
                            ->content(fn ($record) => $record 
                                ? number_format($record->grams_per_bird_per_day_min * 7 / 1000, 3) . ' kg'
                                : 'Enter min g/bird/day to calculate'),
                        Forms\Components\Placeholder::make('max_kg_per_week_display')
                            ->label('Max kg/bird/week')
                            ->content(fn ($record) => $record 
                                ? number_format($record->grams_per_bird_per_day_max * 7 / 1000, 3) . ' kg'
                                : 'Enter max g/bird/day to calculate'),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_week')
                    ->label('Min Week')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_week')
                    ->label('Max Week')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grams_per_bird_per_day_min')
                    ->label('Min g/bird/day')
                    ->numeric()
                    ->suffix(' g'),
                Tables\Columns\TextColumn::make('grams_per_bird_per_day_max')
                    ->label('Max g/bird/day')
                    ->numeric()
                    ->suffix(' g'),
                Tables\Columns\TextColumn::make('min_kg_per_week')
                    ->label('Min kg/bird/week')
                    ->state(fn ($record) => round($record->grams_per_bird_per_day_min * 7 / 1000, 3))
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' kg')
                    ->color('info'),
                Tables\Columns\TextColumn::make('max_kg_per_week')
                    ->label('Max kg/bird/week')
                    ->state(fn ($record) => round($record->grams_per_bird_per_day_max * 7 / 1000, 3))
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' kg')
                    ->color('info'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('min_week', 'asc');
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
            'index' => Pages\ListFeedIntakeTargets::route('/'),
            'create' => Pages\CreateFeedIntakeTarget::route('/create'),
            'edit' => Pages\EditFeedIntakeTarget::route('/{record}/edit'),
        ];
    }
}
