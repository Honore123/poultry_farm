<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchResource\Pages;
use App\Filament\Resources\BatchResource\RelationManagers;
use App\Models\Batch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Farm Management';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch Details')
                    ->schema([
                        Forms\Components\Select::make('farm_id')
                            ->relationship('farm', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('house_id')
                            ->relationship('house', 'name')
                            ->searchable(),
                        Forms\Components\TextInput::make('code')
                            ->label('Batch Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('breed')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('source')
                            ->label('Source (hatchery)')
                            ->maxLength(150),
                        Forms\Components\DatePicker::make('placement_date')
                            ->required(),
                        Forms\Components\TextInput::make('placement_qty')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'brooding' => 'Brooding',
                                'growing'  => 'Growing',
                                'laying'   => 'Laying',
                                'culled'   => 'Culled',
                                'closed'   => 'Closed',
                            ])
                            ->default('brooding')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Batch')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('farm.name')
                    ->label('Farm')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('house.name')
                    ->label('House')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('breed')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('placement_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('placement_qty')
                    ->label('Placed')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'brooding',
                        'info'    => 'growing',
                        'success' => 'laying',
                        'danger'  => 'culled',
                        'gray'    => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'brooding' => 'Brooding',
                        'growing'  => 'Growing',
                        'laying'   => 'Laying',
                        'culled'   => 'Culled',
                        'closed'   => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('farm')
                    ->relationship('farm', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('dashboard')
                    ->label('Dashboard')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('info')
                    ->url(fn (Batch $record) => static::getUrl('dashboard', ['record' => $record])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DailyProductionsRelationManager::class,
            RelationManagers\DailyFeedIntakesRelationManager::class,
            RelationManagers\DailyWaterUsagesRelationManager::class,
            RelationManagers\WeightSamplesRelationManager::class,
            RelationManagers\MortalityLogsRelationManager::class,
            RelationManagers\VaccinationEventsRelationManager::class,
            RelationManagers\HealthTreatmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
            'view' => Pages\ViewBatch::route('/{record}'),
            'dashboard' => Pages\BatchDashboard::route('/{record}/dashboard'),
        ];
    }
}
