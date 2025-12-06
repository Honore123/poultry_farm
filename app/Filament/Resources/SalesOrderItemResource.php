<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderItemResource\Pages;
use App\Models\SalesOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalesOrderItemResource extends Resource
{
    protected static ?string $model = SalesOrderItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Sales & Finance';

    protected static ?int $navigationSort = 20;

    protected static bool $shouldRegisterNavigation = false; // Hide from nav since managed via SalesOrderResource

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Details')
                    ->schema([
                        Forms\Components\Select::make('sales_order_id')
                            ->relationship('salesOrder', 'id')
                            ->label('Order #')
                            ->required(),
                        Forms\Components\TextInput::make('product')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('qty')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('uom')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('RWF '),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('salesOrder.id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty')
                    ->numeric(),
                Tables\Columns\TextColumn::make('uom'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('RWF')
                    ->sortable(),
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
            'index' => Pages\ListSalesOrderItems::route('/'),
            'create' => Pages\CreateSalesOrderItem::route('/create'),
            'edit' => Pages\EditSalesOrderItem::route('/{record}/edit'),
        ];
    }
}
