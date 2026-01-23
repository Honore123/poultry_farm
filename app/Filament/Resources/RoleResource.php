<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 101;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Group permissions by their module/category
     * Returns array with permission IDs as keys and names as values
     */
    protected static function getGroupedPermissions(): array
    {
        $permissions = Permission::all();
        
        $groups = [
            'Farm Management' => ['farms', 'houses', 'batches'],
            'Daily Operations' => ['daily_productions', 'daily_feed_intakes', 'daily_water_usages', 'weight_samples', 'mortality_logs'],
            'Health' => ['vaccination_events', 'health_treatments'],
            'Inventory' => ['suppliers', 'inventory_items', 'inventory_lots', 'inventory_movements'],
            'Sales & Finance' => ['customers', 'sales_orders', 'sales_order_payments', 'expenses', 'egg_stock_adjustments'],
            'Payroll' => ['employee_salaries', 'salary_payments'],
            'Targets & Settings' => ['feed_intake_targets', 'production_targets', 'rearing_targets'],
            'System' => ['activity_logs', 'reports', 'dashboard', 'roles', 'permissions'],
        ];
        
        $grouped = [];
        
        foreach ($groups as $groupName => $keywords) {
            $grouped[$groupName] = [];
            foreach ($permissions as $permission) {
                foreach ($keywords as $keyword) {
                    if (str_contains($permission->name, $keyword)) {
                        // Use ID as key, name as value for proper relationship handling
                        $grouped[$groupName][$permission->id] = $permission->name;
                        break;
                    }
                }
            }
        }
        
        // Add any ungrouped permissions to "Other"
        $allGroupedIds = [];
        foreach ($grouped as $perms) {
            $allGroupedIds = array_merge($allGroupedIds, array_keys($perms));
        }
        $ungrouped = $permissions->whereNotIn('id', $allGroupedIds);
        if ($ungrouped->isNotEmpty()) {
            $grouped['Other'] = $ungrouped->pluck('name', 'id')->toArray();
        }
        
        // Remove empty groups
        return array_filter($grouped, fn($perms) => !empty($perms));
    }

    public static function form(Form $form): Form
    {
        $groupedPermissions = static::getGroupedPermissions();
        
        $permissionSections = [];
        foreach ($groupedPermissions as $groupName => $permissions) {
            // $permissions is now [id => name] array
            $formattedOptions = collect($permissions)->mapWithKeys(
                fn($name, $id) => [$id => static::formatPermissionName($name)]
            )->toArray();
            
            $permissionSections[] = Forms\Components\Section::make($groupName)
                ->schema([
                    Forms\Components\CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->options($formattedOptions)
                        ->columns(4)
                        ->gridDirection('row')
                        ->bulkToggleable(),
                ])
                ->collapsible()
                ->collapsed(fn (string $operation): bool => $operation === 'edit');
        }
        
        return $form
            ->schema([
                Forms\Components\Section::make('Role Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->alphaDash()
                            ->helperText('Use lowercase letters, numbers, dashes, and underscores only')
                            ->disabled(fn (?Role $record): bool => $record?->name === 'admin'),
                        Forms\Components\Placeholder::make('guard_name_display')
                            ->label('Guard')
                            ->content('web'),
                        Forms\Components\Placeholder::make('admin_notice')
                            ->label('')
                            ->content('⚠️ The admin role cannot be renamed and always has all permissions.')
                            ->visible(fn (?Role $record): bool => $record?->name === 'admin'),
                    ])->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->description('Select the permissions for this role. Use the toggles to quickly enable/disable all permissions in a group.')
                    ->schema($permissionSections)
                    ->visible(fn (?Role $record): bool => $record?->name !== 'admin'),
                    
                Forms\Components\Section::make('Permissions')
                    ->description('Admin role has all permissions by default.')
                    ->schema([
                        Forms\Components\Placeholder::make('all_permissions')
                            ->label('')
                            ->content('✅ This role has access to all permissions automatically.'),
                    ])
                    ->visible(fn (?Role $record): bool => $record?->name === 'admin'),
            ]);
    }

    /**
     * Format permission name for display
     */
    protected static function formatPermissionName(string $permission): string
    {
        // view_daily_productions -> View Daily Productions
        return str($permission)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->badge()
                    ->colors([
                        'danger' => 'admin',
                        'warning' => 'manager',
                        'success' => 'staff',
                        'gray' => fn ($state) => !in_array($state, ['admin', 'manager', 'staff']),
                    ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record) {
                        // Prevent deleting admin role
                        if ($record->name === 'admin') {
                            Notification::make()
                                ->title('Cannot delete admin role')
                                ->body('The admin role is protected and cannot be deleted.')
                                ->danger()
                                ->send();
                            throw new \Exception('Cannot delete admin role');
                        }
                        
                        // Check if role has users
                        if ($record->users()->count() > 0) {
                            Notification::make()
                                ->title('Cannot delete role')
                                ->body('This role has users assigned to it. Remove users first.')
                                ->danger()
                                ->send();
                            throw new \Exception('Role has users assigned');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->name === 'admin') {
                                    Notification::make()
                                        ->title('Cannot delete admin role')
                                        ->body('The admin role is protected and cannot be deleted.')
                                        ->danger()
                                        ->send();
                                    throw new \Exception('Cannot delete admin role');
                                }
                                if ($record->users()->count() > 0) {
                                    Notification::make()
                                        ->title('Cannot delete role: ' . $record->name)
                                        ->body('This role has users assigned to it.')
                                        ->danger()
                                        ->send();
                                    throw new \Exception('Role has users assigned');
                                }
                            }
                        }),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
