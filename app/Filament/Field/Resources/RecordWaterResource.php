<?php

namespace App\Filament\Field\Resources;

use App\Filament\Field\Resources\RecordWaterResource\Pages;
use App\Models\Batch;
use App\Models\DailyWaterUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecordWaterResource extends Resource
{
    protected static ?string $model = DailyWaterUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Record Water';

    protected static ?string $modelLabel = 'Water Record';

    protected static ?string $pluralModelLabel = 'Water Records';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💧 Record Water Usage')
                    ->description('Enter today\'s water consumption')
                    ->schema([
                        Forms\Components\Select::make('batch_id')
                            ->label('Select Batch')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Batch::whereIn('status', ['brooding', 'growing', 'laying'])
                                ->where('code', 'like', "%{$search}%")
                                ->orderBy('code')
                                ->limit(20)
                                ->pluck('code', 'id')
                                ->toArray())
                            ->getOptionLabelUsing(fn ($value) => Batch::whereKey($value)->value('code'))
                            ->default(request()->query('batch'))
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->native(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('liters_used')
                            ->label('Water Used (Liters)')
                            ->numeric()
                            ->required()
                            ->minValue(0.1)
                            ->maxValue(100000)
                            ->step(0.01)
                            ->suffix('L')
                            ->autofocus()
                            ->extraInputAttributes(['class' => 'text-2xl font-bold'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                DailyWaterUsage::query()->orderByDesc('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('liters_used')
                    ->label('Liters')
                    ->numeric(decimalPlaces: 1)
                    ->size('lg')
                    ->weight('bold')
                    ->suffix(' L'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'code'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (DailyWaterUsage $record) => $record->date->isToday()),
            ])
            ->bulkActions([])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No water records yet')
            ->emptyStateDescription('Start recording water consumption for your batches')
            ->emptyStateIcon('heroicon-o-beaker');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordWater::route('/'),
            'create' => Pages\CreateRecordWater::route('/create'),
            'edit' => Pages\EditRecordWater::route('/{record}/edit'),
        ];
    }
}
