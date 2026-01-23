<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 102;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Get the category for a permission based on its name
     */
    protected static function getPermissionCategory(string $permission): string
    {
        $categories = [
            'Farm Management' => ['farms', 'houses', 'batches'],
            'Daily Operations' => ['daily_productions', 'daily_feed_intakes', 'daily_water_usages', 'weight_samples', 'mortality_logs'],
            'Health' => ['vaccination_events', 'health_treatments'],
            'Inventory' => ['suppliers', 'inventory_items', 'inventory_lots', 'inventory_movements'],
            'Sales & Finance' => ['customers', 'sales_orders', 'sales_order_payments', 'expenses', 'egg_stock_adjustments'],
            'Payroll' => ['employee_salaries', 'salary_payments'],
            'Targets & Settings' => ['feed_intake_targets', 'production_targets', 'rearing_targets'],
            'System' => ['activity_logs', 'reports', 'dashboard', 'roles', 'permissions'],
        ];
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($permission, $keyword)) {
                    return $category;
                }
            }
        }
        
        return 'Other';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Permission Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Use format: action_resource (e.g., view_orders, create_users)')
                            ->regex('/^[a-z_]+$/')
                            ->validationMessages([
                                'regex' => 'Permission name must be lowercase with underscores only.',
                            ]),
                        Forms\Components\Placeholder::make('guard_name_display')
                            ->label('Guard')
                            ->content('web'),
                        Forms\Components\Placeholder::make('category')
                            ->label('Category')
                            ->content(fn (?Permission $record): string => $record ? static::getPermissionCategory($record->name) : 'Will be auto-detected')
                            ->visible(fn (string $operation): bool => $operation !== 'create'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Roles with this Permission')
                    ->schema([
                        Forms\Components\Placeholder::make('roles_list')
                            ->label('')
                            ->content(function (?Permission $record): string {
                                if (!$record) {
                                    return 'Save the permission first to assign roles.';
                                }
                                $roles = $record->roles->pluck('name')->toArray();
                                if (empty($roles)) {
                                    return 'No roles have this permission yet.';
                                }
                                return implode(', ', $roles);
                            }),
                    ])
                    ->visible(fn (string $operation): bool => $operation !== 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->state(fn (Permission $record): string => static::getPermissionCategory($record->name))
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Farm Management' => 'success',
                        'Daily Operations' => 'info',
                        'Health' => 'danger',
                        'Inventory' => 'warning',
                        'Sales & Finance' => 'primary',
                        'Payroll' => 'gray',
                        'Targets & Settings' => 'success',
                        'System' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('roles_count')
                    ->counts('roles')
                    ->label('Roles')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Assigned to Roles')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Farm Management' => 'Farm Management',
                        'Daily Operations' => 'Daily Operations',
                        'Health' => 'Health',
                        'Inventory' => 'Inventory',
                        'Sales & Finance' => 'Sales & Finance',
                        'Payroll' => 'Payroll',
                        'Targets & Settings' => 'Targets & Settings',
                        'System' => 'System',
                        'Other' => 'Other',
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $categories = [
                            'Farm Management' => ['farms', 'houses', 'batches'],
                            'Daily Operations' => ['daily_productions', 'daily_feed_intakes', 'daily_water_usages', 'weight_samples', 'mortality_logs'],
                            'Health' => ['vaccination_events', 'health_treatments'],
                            'Inventory' => ['suppliers', 'inventory_items', 'inventory_lots', 'inventory_movements'],
                            'Sales & Finance' => ['customers', 'sales_orders', 'sales_order_payments', 'expenses', 'egg_stock_adjustments'],
                            'Payroll' => ['employee_salaries', 'salary_payments'],
                            'Targets & Settings' => ['feed_intake_targets', 'production_targets', 'rearing_targets'],
                            'System' => ['activity_logs', 'reports', 'dashboard', 'roles', 'permissions'],
                        ];
                        
                        if (isset($categories[$data['value']])) {
                            return $query->where(function ($q) use ($categories, $data) {
                                foreach ($categories[$data['value']] as $keyword) {
                                    $q->orWhere('name', 'like', "%{$keyword}%");
                                }
                            });
                        }
                        
                        // "Other" category - exclude all known categories
                        $allKeywords = array_merge(...array_values($categories));
                        return $query->where(function ($q) use ($allKeywords) {
                            foreach ($allKeywords as $keyword) {
                                $q->where('name', 'not like', "%{$keyword}%");
                            }
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this permission? This will remove it from all roles.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these permissions? This will remove them from all roles.'),
                ]),
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
