<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales & Finance';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Details')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->label('Customer')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('order_date')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'confirmed' => 'Confirmed',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product')
                                    ->options([
                                        'Eggs' => 'Eggs',
                                        'Manure' => 'Manure',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if ($state === 'Eggs') {
                                            $set('uom', 'tray');
                                        } elseif ($state === 'Manure') {
                                            $set('uom', 'kg');
                                        }
                                    }),
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->helperText(function (Get $get) {
                                        $product = $get('product');
                                        $qty = (int) $get('qty');
                                        $uom = strtolower($get('uom') ?? '');
                                        
                                        if ($product === 'Eggs' && ($uom === 'tray' || $uom === 'trays') && $qty > 0) {
                                            $eggs = $qty * SalesOrderItem::EGGS_PER_TRAY;
                                            return "= {$eggs} eggs";
                                        }
                                        return null;
                                    }),
                                Forms\Components\Select::make('uom')
                                    ->options(function (Get $get) {
                                        $product = $get('product');
                                        if ($product === 'Eggs') {
                                            return [
                                                'tray' => 'Tray (30 eggs)',
                                                'piece' => 'Piece (individual)',
                                            ];
                                        } elseif ($product === 'Manure') {
                                            return [
                                                'kg' => 'Kilogram',
                                                'bag' => 'Bag',
                                            ];
                                        }
                                        return [
                                            'tray' => 'Tray',
                                            'piece' => 'Piece',
                                            'kg' => 'Kilogram',
                                            'bag' => 'Bag',
                                        ];
                                    })
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('RWF'),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Add Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'confirmed',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                Tables\Columns\TextColumn::make('total_eggs')
                    ->label('Eggs Sold')
                    ->getStateUsing(fn (SalesOrder $record) => $record->total_eggs)
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '-')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->getStateUsing(fn (SalesOrder $record) => $record->items->sum(fn ($item) => $item->qty * $item->unit_price))
                    ->formatStateUsing(fn ($state) => 'RWF ' . number_format($state, 0))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum('items', \Illuminate\Support\Facades\DB::raw('qty * unit_price'))
                            ->orderBy('items_sum_qty__unit_price', $direction);
                    })
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'confirmed' => 'Confirmed',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('order_date', 'desc');
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
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
