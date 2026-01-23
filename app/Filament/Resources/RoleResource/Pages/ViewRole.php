<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Permission\Models\Permission;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $groupedPermissions = $this->getGroupedPermissions();
        
        $permissionEntries = [];
        foreach ($groupedPermissions as $groupName => $permissions) {
            $rolePermissions = $this->record->permissions->pluck('name')->toArray();
            $permissionList = collect($permissions)->map(function ($p) use ($rolePermissions) {
                $hasPermission = in_array($p, $rolePermissions);
                $icon = $hasPermission ? '✅' : '❌';
                $formatted = str($p)->replace('_', ' ')->title()->toString();
                return "{$icon} {$formatted}";
            })->join(', ');
            
            $permissionEntries[] = Infolists\Components\TextEntry::make($groupName)
                ->label($groupName)
                ->state($permissionList)
                ->columnSpanFull();
        }

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Role Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'admin' => 'danger',
                                'manager' => 'warning',
                                'staff' => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('guard_name')
                            ->label('Guard'),
                        Infolists\Components\TextEntry::make('users_count')
                            ->label('Users with this role')
                            ->state(fn () => $this->record->users()->count())
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(4),
                    
                Infolists\Components\Section::make('Permissions')
                    ->description(fn () => $this->record->name === 'admin' 
                        ? 'Admin role has all permissions automatically.' 
                        : 'Permissions assigned to this role.')
                    ->schema($permissionEntries),
            ]);
    }

    protected function getGroupedPermissions(): array
    {
        $permissions = Permission::all()->pluck('name')->toArray();
        
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
                    if (str_contains($permission, $keyword)) {
                        $grouped[$groupName][] = $permission;
                        break;
                    }
                }
            }
        }
        
        $allGrouped = array_merge(...array_values($grouped));
        $ungrouped = array_diff($permissions, $allGrouped);
        if (!empty($ungrouped)) {
            $grouped['Other'] = $ungrouped;
        }
        
        return array_filter($grouped, fn($perms) => !empty($perms));
    }
}
