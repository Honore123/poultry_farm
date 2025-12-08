<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Movement Details')
                    ->schema([
                        Forms\Components\Select::make('lot_id')
                            ->relationship('lot', 'lot_code')
                            ->label('Inventory Lot')
                            ->searchable()
                            ->required(),
                        Forms\Components\DateTimePicker::make('ts')
                            ->label('Timestamp')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('direction')
                            ->options([
                                'in' => 'In (Receipt)',
                                'out' => 'Out (Issue)',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('qty')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->step(0.001),
                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255)
                            ->placeholder('PO#, SO#, etc.'),
                        Forms\Components\Select::make('batch_id')
                            ->relationship('batch', 'code')
                            ->label('Related Batch')
                            ->searchable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lot.item.name')
                    ->label('Item')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lot.lot_code')
                    ->label('Lot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ts')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('direction')
                    ->badge()
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ]),
                Tables\Columns\TextColumn::make('qty')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('batch.code')
                    ->label('Batch')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (InventoryMovement $record) {
                        // Reverse the movement effect before deleting
                        DB::transaction(function () use ($record) {
                            $lot = InventoryLot::lockForUpdate()->find($record->lot_id);
                            
                            if ($lot) {
                                if ($record->direction === 'in') {
                                    $lot->qty_on_hand -= $record->qty;
                                } else {
                                    $lot->qty_on_hand += $record->qty;
                                }
                                $lot->save();
                            }
                        });
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        // Reverse all movement effects before bulk deleting
                        DB::transaction(function () use ($records) {
                            foreach ($records as $record) {
                                $lot = InventoryLot::lockForUpdate()->find($record->lot_id);
                                
                                if ($lot) {
                                    if ($record->direction === 'in') {
                                        $lot->qty_on_hand -= $record->qty;
                                    } else {
                                        $lot->qty_on_hand += $record->qty;
                                    }
                                    $lot->save();
                                }
                            }
                        });
                    }),
            ])
            ->defaultSort('ts', 'desc');
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
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}
