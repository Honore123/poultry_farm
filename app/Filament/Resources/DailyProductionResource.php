<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyProductionResource\Pages;
use App\Models\DailyProduction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyProductionResource extends Resource
{
    protected static ?string $model = DailyProduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Production Record')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'code', fn ($query) => $query->whereIn('status', ['brooding', 'growing', 'laying']))
                            ->label('Batch')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->helperText('Cannot select future dates'),
                        Forms\Components\TextInput::make('eggs_total')
                            ->label('Total Eggs')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100000)
                            ->live(onBlur: true)
                            ->helperText('Total eggs collected'),
                        Forms\Components\TextInput::make('eggs_cracked')
                            ->label('Cracked')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => (int) $get('eggs_total') ?: 100000)
                            ->lte('eggs_total')
                            ->validationMessages([
                                'lte' => 'Cracked eggs cannot exceed total eggs',
                            ]),
                        Forms\Components\TextInput::make('eggs_dirty')
                            ->label('Dirty')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => (int) $get('eggs_total') ?: 100000)
                            ->lte('eggs_total')
                            ->validationMessages([
                                'lte' => 'Dirty eggs cannot exceed total eggs',
                            ]),
                        Forms\Components\TextInput::make('eggs_soft')
                            ->label('Soft Shell')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(fn (Get $get) => (int) $get('eggs_total') ?: 100000)
                            ->lte('eggs_total')
                            ->validationMessages([
                                'lte' => 'Soft shell eggs cannot exceed total eggs',
                            ]),
                        Forms\Components\TextInput::make('egg_weight_avg_g')
                            ->numeric()
                            ->label('Avg Egg Weight (g)')
                            ->minValue(20)
                            ->maxValue(120)
                            ->step(0.01)
                            ->helperText('Typical range: 50-70g'),
                        Forms\Components\TextInput::make('lighting_hours')
                            ->numeric()
                            ->label('Lighting Hours')
                            ->minValue(0)
                            ->maxValue(24)
                            ->step(0.1)
                            ->helperText('Hours of light (0-24)'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eggs_total')
                    ->label('Eggs')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eggs_cracked')
                    ->label('Cracked')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('eggs_dirty')
                    ->label('Dirty')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('eggs_soft')
                    ->label('Soft')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('egg_weight_avg_g')
                    ->label('Avg g')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lighting_hours')
                    ->label('Light hrs')
                    ->numeric(decimalPlaces: 1)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'code'),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('date', today())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListDailyProductions::route('/'),
            'create' => Pages\CreateDailyProduction::route('/create'),
            'edit' => Pages\EditDailyProduction::route('/{record}/edit'),
        ];
    }
}
