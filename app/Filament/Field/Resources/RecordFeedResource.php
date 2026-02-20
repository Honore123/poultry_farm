<?php

namespace App\Filament\Field\Resources;

use App\Filament\Field\Resources\RecordFeedResource\Pages;
use App\Models\Batch;
use App\Models\DailyFeedIntake;
use App\Models\InventoryLot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecordFeedResource extends Resource
{
    protected static ?string $model = DailyFeedIntake::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Record Feed';

    protected static ?string $modelLabel = 'Feed Record';

    protected static ?string $pluralModelLabel = 'Feed Records';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🌾 Record Feed Consumption')
                    ->description('Enter today\'s feed given to batch - will be deducted from inventory')
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

                        Forms\Components\Select::make('inventory_lot_id')
                            ->label('Select Feed Lot')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
                                    ->where('qty_on_hand', '>', 0)
                                    ->where(function ($query) use ($search) {
                                        $query->where('lot_code', 'like', "%{$search}%")
                                            ->orWhereHas('item', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                                    })
                                    ->with('item')
                                    ->orderByDesc('qty_on_hand')
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($lot) => [
                                        $lot->id => "{$lot->item->name} - {$lot->lot_code} ({$lot->qty_on_hand} kg available)"
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $lot = InventoryLot::with('item')->find($value);
                                if (!$lot) {
                                    return null;
                                }
                                return "{$lot->item->name} - {$lot->lot_code} ({$lot->qty_on_hand} kg available)";
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $lot = InventoryLot::find($state);
                                    if ($lot) {
                                        $set('feed_item_id', $lot->item_id);
                                        $set('available_stock', $lot->qty_on_hand);
                                    }
                                }
                            })
                            ->helperText('Select from available feed inventory')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('feed_item_id'),

                        Forms\Components\Placeholder::make('available_stock_display')
                            ->label('Available Stock')
                            ->content(fn (Get $get) => $get('available_stock') 
                                ? number_format($get('available_stock'), 1) . ' kg' 
                                : 'Select a feed lot first')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('available_stock'),

                        Forms\Components\TextInput::make('kg_given')
                            ->label('Feed Given (kg)')
                            ->numeric()
                            ->required()
                            ->minValue(0.1)
                            ->maxValue(fn (Get $get) => $get('available_stock') ?: 10000)
                            ->step(0.01)
                            ->suffix('kg')
                            ->autofocus()
                            ->extraInputAttributes(['class' => 'text-2xl font-bold'])
                            ->helperText(fn (Get $get) => $get('available_stock') 
                                ? 'Max: ' . number_format($get('available_stock'), 1) . ' kg'
                                : '')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                DailyFeedIntake::query()->orderByDesc('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('feedItem.name')
                    ->label('Feed Type'),
                Tables\Columns\TextColumn::make('kg_given')
                    ->label('Kg Given')
                    ->numeric(decimalPlaces: 1)
                    ->size('lg')
                    ->weight('bold')
                    ->suffix(' kg'),
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
                    ->visible(fn (DailyFeedIntake $record) => $record->date->isToday()),
            ])
            ->bulkActions([])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No feed records yet')
            ->emptyStateDescription('Start recording feed consumption for your batches')
            ->emptyStateIcon('heroicon-o-beaker');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecordFeed::route('/'),
            'create' => Pages\CreateRecordFeed::route('/create'),
            'edit' => Pages\EditRecordFeed::route('/{record}/edit'),
        ];
    }
}
