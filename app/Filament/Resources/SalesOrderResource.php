<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesOrderResource\Pages;
use App\Filament\Resources\SalesOrderResource\RelationManagers;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
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
                            ->native(false)
                            ->minDate(fn ($record) => $record ? null : now()->startOfDay())
                            ->maxDate(now())
                            ->helperText(fn ($record) => $record 
                                ? 'Changing dates on existing orders may affect stock calculations' 
                                : 'Past dates not allowed to maintain accurate stock tracking'),
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
                    ->getStateUsing(fn (SalesOrder $record) => $record->total_amount)
                    ->formatStateUsing(fn ($state) => 'RWF ' . number_format($state, 0))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum('items', \Illuminate\Support\Facades\DB::raw('qty * unit_price'))
                            ->orderBy('items_sum_qty__unit_price', $direction);
                    })
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Paid')
                    ->getStateUsing(fn (SalesOrder $record) => $record->total_paid)
                    ->formatStateUsing(fn ($state) => 'RWF ' . number_format($state, 0))
                    ->color('success'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Remaining')
                    ->getStateUsing(fn (SalesOrder $record) => $record->remaining_amount)
                    ->formatStateUsing(fn ($state) => 'RWF ' . number_format($state, 0))
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                        default => $state,
                    })
                    ->colors([
                        'danger' => 'unpaid',
                        'warning' => 'partial',
                        'success' => 'paid',
                    ]),
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
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ])
                    ->label('Payment Status'),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('record_payment')
                    ->label('Pay')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form(function (SalesOrder $record) {
                        return [
                            Forms\Components\DatePicker::make('payment_date')
                                ->required()
                                ->default(now())
                                ->native(false),
                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue($record->remaining_amount)
                                ->prefix('RWF')
                                ->default($record->remaining_amount)
                                ->helperText("Remaining: RWF " . number_format($record->remaining_amount, 0)),
                            Forms\Components\Select::make('payment_method')
                                ->options([
                                    'cash' => 'Cash',
                                    'bank_transfer' => 'Bank Transfer',
                                    'mobile_money' => 'Mobile Money',
                                ])
                                ->default('cash')
                                ->required(),
                            Forms\Components\TextInput::make('reference')
                                ->label('Transaction Reference'),
                            Forms\Components\Textarea::make('notes')
                                ->rows(2),
                        ];
                    })
                    ->action(function (SalesOrder $record, array $data) {
                        $record->payments()->create([
                            ...$data,
                            'received_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Payment Recorded')
                            ->body("Payment of RWF " . number_format($data['amount'], 0) . " has been recorded.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SalesOrder $record) => $record->remaining_amount > 0),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('order_date', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Order #'),
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Customer'),
                        Infolists\Components\TextEntry::make('order_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'draft' => 'gray',
                                'confirmed' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])->columns(4),

                Infolists\Components\Section::make('Payment Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Total Amount')
                            ->money('RWF')
                            ->weight('bold')
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('total_paid')
                            ->label('Amount Paid')
                            ->money('RWF')
                            ->weight('bold')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('remaining_amount')
                            ->label('Remaining')
                            ->money('RWF')
                            ->weight('bold')
                            ->color(fn (SalesOrder $record): string => $record->remaining_amount > 0 ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'unpaid' => 'Unpaid',
                                'partial' => 'Partial',
                                'paid' => 'Paid',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match($state) {
                                'unpaid' => 'danger',
                                'partial' => 'warning',
                                'paid' => 'success',
                                default => 'gray',
                            }),
                    ])->columns(4),

                Infolists\Components\Section::make('Order Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('product'),
                                Infolists\Components\TextEntry::make('qty')
                                    ->label('Quantity'),
                                Infolists\Components\TextEntry::make('uom')
                                    ->label('Unit'),
                                Infolists\Components\TextEntry::make('unit_price')
                                    ->money('RWF'),
                                Infolists\Components\TextEntry::make('line_total')
                                    ->label('Line Total')
                                    ->getStateUsing(fn ($record) => $record->qty * $record->unit_price)
                                    ->money('RWF')
                                    ->weight('bold'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'view' => Pages\ViewSalesOrder::route('/{record}'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}
