<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyFeedIntakeResource\Pages;
use App\Models\DailyFeedIntake;
use App\Models\InventoryLot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyFeedIntakeResource extends Resource
{
    protected static ?string $model = DailyFeedIntake::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feed Intake Record')
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
                        Forms\Components\Select::make('inventory_lot_id')
                            ->label('Feed Lot (Inventory)')
                            ->options(function () {
                                return InventoryLot::whereHas('item', fn ($q) => $q->where('category', 'feed'))
                                    ->where('qty_on_hand', '>', 0)
                                    ->with('item')
                                    ->get()
                                    ->mapWithKeys(fn ($lot) => [
                                        $lot->id => "{$lot->item->name} - {$lot->lot_code} ({$lot->qty_on_hand} kg available)"
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $lot = InventoryLot::find($state);
                                    if ($lot) {
                                        $set('feed_item_id', $lot->item_id);
                                        $set('available_stock', $lot->qty_on_hand);
                                    }
                                } else {
                                    $set('feed_item_id', null);
                                    $set('available_stock', null);
                                }
                            })
                            ->helperText('Select from available feed inventory - quantity will be deducted'),
                        Forms\Components\Hidden::make('feed_item_id'),
                        Forms\Components\Hidden::make('available_stock'),
                        Forms\Components\TextInput::make('kg_given')
                            ->label('Amount Given')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn (Get $get) => $get('available_stock') ?: 10000)
                            ->step(0.01)
                            ->suffix('kg')
                            ->helperText(fn (Get $get) => $get('available_stock') 
                                ? 'Available: ' . number_format($get('available_stock'), 1) . ' kg'
                                : 'Select a feed lot first'),
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
                Tables\Columns\TextColumn::make('feedItem.name')
                    ->label('Feed')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kg_given')
                    ->label('Kg Given')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
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
                Tables\Actions\DeleteAction::make()
                    ->before(function (DailyFeedIntake $record) {
                        // Find and restore inventory
                        $movement = \App\Models\InventoryMovement::where('batch_id', $record->batch_id)
                            ->where('reference', 'feed_consumption')
                            ->whereDate('ts', $record->date)
                            ->where('qty', $record->kg_given)
                            ->first();
                        
                        if ($movement) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($movement, $record) {
                                $lot = InventoryLot::lockForUpdate()->find($movement->lot_id);
                                
                                if ($lot) {
                                    $lot->qty_on_hand += $record->kg_given;
                                    $lot->save();
                                    
                                    $movement->delete();
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('Inventory restored')
                                        ->body("{$record->kg_given} kg restored to {$lot->item->name}")
                                        ->success()
                                        ->send();
                                }
                            });
                        }
                    }),
            ])
            ->bulkActions([
                // Bulk delete disabled to ensure proper inventory management
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
            'index' => Pages\ListDailyFeedIntakes::route('/'),
            'create' => Pages\CreateDailyFeedIntake::route('/create'),
            'edit' => Pages\EditDailyFeedIntake::route('/{record}/edit'),
        ];
    }
}
