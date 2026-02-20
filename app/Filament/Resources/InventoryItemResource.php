<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use App\Tenancy\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule) {
                                    $tenantId = app(TenantContext::class)->currentTenantId()
                                        ?? auth()->user()?->tenant_id;

                                    if ($tenantId) {
                                        $rule->where('tenant_id', $tenantId);
                                    }

                                    return $rule;
                                }
                            ),
                        Forms\Components\Select::make('category')
                            ->options([
                                'feed' => 'Feed',
                                'drug' => 'Drug',
                                'packaging' => 'Packaging',
                                'equipment' => 'Equipment',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('uom')
                            ->label('Unit of Measure')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('e.g., kg, bag, piece, ml'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->colors([
                        'success' => 'feed',
                        'warning' => 'drug',
                        'info' => 'packaging',
                        'gray' => 'equipment',
                        'secondary' => 'other',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('uom')
                    ->label('UoM'),
                Tables\Columns\TextColumn::make('inventory_lots_count')
                    ->counts('inventoryLots')
                    ->label('Lots'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'feed' => 'Feed',
                        'drug' => 'Drug',
                        'packaging' => 'Packaging',
                        'equipment' => 'Equipment',
                        'other' => 'Other',
                    ]),
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
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
