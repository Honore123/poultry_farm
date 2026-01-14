<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EggStockAdjustmentResource\Pages;
use App\Models\EggStockAdjustment;
use App\Models\SalesOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EggStockAdjustmentResource extends Resource
{
    protected static ?string $model = EggStockAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Sales & Finance';

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationLabel = 'Egg Stock Adjustments';

    protected static ?string $modelLabel = 'Stock Adjustment';

    protected static ?string $pluralModelLabel = 'Stock Adjustments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stock Adjustment Details')
                    ->description('Record a stock adjustment when physical egg count differs from system records.')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Adjustment Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->maxDate(now()),

                        Forms\Components\Placeholder::make('current_system_stock')
                            ->label('Current System Stock')
                            ->content(function () {
                                $available = SalesOrder::getAvailableEggs();
                                $trays = floor($available / 30);
                                return number_format($available) . " eggs ({$trays} trays)";
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('physical_count')
                            ->label('Physical Count (eggs)')
                            ->helperText('Enter the actual number of eggs you physically counted')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?int $state) {
                                if ($state !== null) {
                                    $systemCount = SalesOrder::getAvailableEggs();
                                    $set('system_count', $systemCount);
                                    
                                    $difference = $state - $systemCount;
                                    if ($difference > 0) {
                                        $set('adjustment_type', 'increase');
                                        $set('quantity', $difference);
                                    } elseif ($difference < 0) {
                                        $set('adjustment_type', 'decrease');
                                        $set('quantity', abs($difference));
                                    } else {
                                        $set('quantity', 0);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('system_count')
                            ->label('System Count at Adjustment')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Automatically calculated from system records'),

                        Forms\Components\Select::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'increase' => 'Increase (Add eggs)',
                                'decrease' => 'Decrease (Remove eggs)',
                            ])
                            ->required()
                            ->live()
                            ->default('decrease'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Adjustment Quantity (eggs)')
                            ->helperText(fn (Get $get) => $get('adjustment_type') === 'increase' 
                                ? 'Number of eggs to ADD to stock' 
                                : 'Number of eggs to REMOVE from stock')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?int $state) {
                                if ($state !== null && $get('system_count')) {
                                    $systemCount = (int) $get('system_count');
                                    $adjustmentType = $get('adjustment_type');
                                    
                                    if ($adjustmentType === 'increase') {
                                        $set('physical_count', $systemCount + $state);
                                    } else {
                                        $set('physical_count', max(0, $systemCount - $state));
                                    }
                                }
                            }),

                        Forms\Components\Placeholder::make('new_stock_level')
                            ->label('New Stock Level After Adjustment')
                            ->content(function (Get $get) {
                                $systemCount = SalesOrder::getAvailableEggs();
                                $quantity = (int) $get('quantity');
                                $type = $get('adjustment_type');
                                
                                if ($type === 'increase') {
                                    $newCount = $systemCount + $quantity;
                                } else {
                                    $newCount = max(0, $systemCount - $quantity);
                                }
                                
                                $trays = floor($newCount / 30);
                                $color = $type === 'increase' ? 'text-green-600' : 'text-red-600';
                                $sign = $type === 'increase' ? '+' : '-';
                                
                                return new \Illuminate\Support\HtmlString(
                                    "<span class='font-bold'>" . number_format($newCount) . " eggs ({$trays} trays)</span> " .
                                    "<span class='{$color}'>({$sign}" . number_format($quantity) . ")</span>"
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Reason & Notes')
                    ->schema([
                        Forms\Components\Select::make('reason')
                            ->label('Reason for Adjustment')
                            ->options(EggStockAdjustment::REASONS)
                            ->required()
                            ->searchable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->helperText('Provide any additional context or explanation for this adjustment')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Hidden::make('adjusted_by')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('adjustment_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'success' => 'increase',
                        'danger' => 'decrease',
                    ])
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty (eggs)')
                    ->formatStateUsing(fn ($state, $record) => 
                        ($record->adjustment_type === 'increase' ? '+' : '-') . number_format($state)
                    )
                    ->color(fn ($record) => $record->adjustment_type === 'increase' ? 'success' : 'danger')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('trays')
                    ->label('Qty (trays)')
                    ->getStateUsing(fn ($record) => 
                        ($record->adjustment_type === 'increase' ? '+' : '-') . 
                        number_format($record->quantity / 30, 1)
                    )
                    ->color(fn ($record) => $record->adjustment_type === 'increase' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('physical_count')
                    ->label('Physical Count')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('system_count')
                    ->label('System Count')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->formatStateUsing(fn ($state) => EggStockAdjustment::REASONS[$state] ?? $state)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('adjustedBy.name')
                    ->label('Adjusted By')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('adjustment_type')
                    ->options([
                        'increase' => 'Increase',
                        'decrease' => 'Decrease',
                    ]),

                Tables\Filters\SelectFilter::make('reason')
                    ->options(EggStockAdjustment::REASONS),

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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading('No stock adjustments')
            ->emptyStateDescription('Stock adjustments are used to reconcile physical egg counts with system records.')
            ->emptyStateIcon('heroicon-o-adjustments-horizontal');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEggStockAdjustments::route('/'),
            'create' => Pages\CreateEggStockAdjustment::route('/create'),
            'view' => Pages\ViewEggStockAdjustment::route('/{record}'),
            'edit' => Pages\EditEggStockAdjustment::route('/{record}/edit'),
        ];
    }
}

