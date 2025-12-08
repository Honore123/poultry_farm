<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionTargetResource\Pages;
use App\Models\ProductionTarget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionTargetResource extends Resource
{
    protected static ?string $model = ProductionTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Production Targets (Week 18+)';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Week')
                    ->schema([
                        Forms\Components\TextInput::make('week')
                            ->required()
                            ->numeric()
                            ->minValue(18)
                            ->unique(ignoreRecord: true)
                            ->label('Week'),
                    ])->columns(1),

                Forms\Components\Section::make('Production Performance')
                    ->schema([
                        Forms\Components\TextInput::make('hen_day_production_pct')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('Hen Day Production (%)')
                            ->suffix('%'),
                        Forms\Components\TextInput::make('avg_egg_weight_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Avg Egg Weight')
                            ->suffix('g'),
                        Forms\Components\TextInput::make('egg_mass_per_day_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Egg Mass/Day')
                            ->suffix('g'),
                        Forms\Components\TextInput::make('livability_pct')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->label('Livability (%)')
                            ->suffix('%'),
                    ])->columns(2),

                Forms\Components\Section::make('Feed Intake')
                    ->schema([
                        Forms\Components\TextInput::make('feed_intake_per_day_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Feed Intake (g/bird/day)')
                            ->suffix('g')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                $set('kg_per_week_display', $state ? round($state * 7 / 1000, 3) : null)
                            ),
                        Forms\Components\TextInput::make('fcr_week')
                            ->numeric()
                            ->minValue(0)
                            ->label('FCR (Week)')
                            ->step(0.01),
                    ])->columns(2),

                Forms\Components\Section::make('Weekly Projection (per bird)')
                    ->description('Calculated: daily intake × 7 days')
                    ->schema([
                        Forms\Components\Placeholder::make('kg_per_week_display')
                            ->label('kg/bird/week')
                            ->content(fn ($record) => $record && $record->feed_intake_per_day_g
                                ? number_format($record->feed_intake_per_day_g * 7 / 1000, 3) . ' kg'
                                : 'Enter feed intake g/bird/day'),
                    ])->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Cumulative Data')
                    ->schema([
                        Forms\Components\TextInput::make('cum_eggs_hh')
                            ->numeric()
                            ->minValue(0)
                            ->label('Cumulative Eggs (HH)'),
                        Forms\Components\TextInput::make('cum_egg_mass_kg')
                            ->numeric()
                            ->minValue(0)
                            ->label('Cumulative Egg Mass')
                            ->suffix('kg'),
                        Forms\Components\TextInput::make('cum_feed_kg')
                            ->numeric()
                            ->minValue(0)
                            ->label('Cumulative Feed')
                            ->suffix('kg'),
                        Forms\Components\TextInput::make('cum_fcr')
                            ->numeric()
                            ->minValue(0)
                            ->label('Cumulative FCR')
                            ->step(0.01),
                    ])->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Body Weight')
                    ->schema([
                        Forms\Components\TextInput::make('body_weight_g')
                            ->numeric()
                            ->minValue(0)
                            ->label('Body Weight')
                            ->suffix('g'),
                    ])->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('week')
                    ->label('Week')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('hen_day_production_pct')
                    ->label('HD Prod %')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('feed_intake_per_day_g')
                    ->label('g/bird/day')
                    ->numeric()
                    ->suffix(' g'),
                Tables\Columns\TextColumn::make('kg_per_week')
                    ->label('kg/bird/week')
                    ->state(fn ($record) => $record->feed_intake_per_day_g 
                        ? round($record->feed_intake_per_day_g * 7 / 1000, 3) 
                        : null)
                    ->numeric(decimalPlaces: 3)
                    ->suffix(' kg')
                    ->color('info'),
                Tables\Columns\TextColumn::make('avg_egg_weight_g')
                    ->label('Egg Wt')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' g')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fcr_week')
                    ->label('FCR')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('livability_pct')
                    ->label('Livability')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('body_weight_g')
                    ->label('Body Wt')
                    ->numeric()
                    ->suffix(' g')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cum_feed_kg')
                    ->label('Cum Feed')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' kg')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('week', 'asc');
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
            'index' => Pages\ListProductionTargets::route('/'),
            'create' => Pages\CreateProductionTarget::route('/create'),
            'edit' => Pages\EditProductionTarget::route('/{record}/edit'),
        ];
    }
}

