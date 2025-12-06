<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLotResource\Pages;
use App\Models\InventoryLot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryLotResource extends Resource
{
    protected static ?string $model = InventoryLot::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lot Details')
                    ->schema([
                        Forms\Components\Select::make('item_id')
                            ->relationship('item', 'name')
                            ->label('Item')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Supplier')
                            ->searchable(),
                        Forms\Components\TextInput::make('lot_code')
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('expiry')
                            ->label('Expiry Date'),
                        Forms\Components\TextInput::make('qty_on_hand')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->step(0.001)
                            ->label('Qty on Hand'),
                        Forms\Components\TextInput::make('uom')
                            ->label('Unit of Measure')
                            ->required()
                            ->maxLength(50),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('lot_code')
                    ->label('Lot Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('qty_on_hand')
                    ->label('Qty')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('uom')
                    ->label('UoM'),
                Tables\Columns\TextColumn::make('expiry')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->expiry && $record->expiry->isPast() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item')
                    ->relationship('item', 'name'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListInventoryLots::route('/'),
            'create' => Pages\CreateInventoryLot::route('/create'),
            'edit' => Pages\EditInventoryLot::route('/{record}/edit'),
        ];
    }
}
