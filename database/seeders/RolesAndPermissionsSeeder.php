<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions by module
        $permissions = [
            // Farm Management
            'view_farms', 'create_farms', 'edit_farms', 'delete_farms',
            'view_houses', 'create_houses', 'edit_houses', 'delete_houses',
            'view_batches', 'create_batches', 'edit_batches', 'delete_batches',

            // Daily Operations
            'view_daily_productions', 'create_daily_productions', 'edit_daily_productions', 'delete_daily_productions',
            'view_daily_feed_intakes', 'create_daily_feed_intakes', 'edit_daily_feed_intakes', 'delete_daily_feed_intakes',
            'view_daily_water_usages', 'create_daily_water_usages', 'edit_daily_water_usages', 'delete_daily_water_usages',
            'view_weight_samples', 'create_weight_samples', 'edit_weight_samples', 'delete_weight_samples',
            'view_mortality_logs', 'create_mortality_logs', 'edit_mortality_logs', 'delete_mortality_logs',

            // Health
            'view_vaccination_events', 'create_vaccination_events', 'edit_vaccination_events', 'delete_vaccination_events',
            'view_health_treatments', 'create_health_treatments', 'edit_health_treatments', 'delete_health_treatments',

            // Inventory
            'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
            'view_inventory_items', 'create_inventory_items', 'edit_inventory_items', 'delete_inventory_items',
            'view_inventory_lots', 'create_inventory_lots', 'edit_inventory_lots', 'delete_inventory_lots',
            'view_inventory_movements', 'create_inventory_movements', 'edit_inventory_movements', 'delete_inventory_movements',

            // Sales & Finance
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
            'view_sales_orders', 'create_sales_orders', 'edit_sales_orders', 'delete_sales_orders',
            'view_sales_order_payments', 'create_sales_order_payments', 'edit_sales_order_payments', 'delete_sales_order_payments',
            'view_expenses', 'create_expenses', 'edit_expenses', 'delete_expenses',
            'view_egg_stock_adjustments', 'create_egg_stock_adjustments', 'edit_egg_stock_adjustments', 'delete_egg_stock_adjustments',

            // Payroll (admin only for management, users can view own)
            'view_employee_salaries', 'create_employee_salaries', 'edit_employee_salaries', 'delete_employee_salaries',
            'view_salary_payments', 'create_salary_payments', 'edit_salary_payments', 'delete_salary_payments',

            // Settings & Targets
            'view_feed_intake_targets', 'create_feed_intake_targets', 'edit_feed_intake_targets', 'delete_feed_intake_targets',
            'view_production_targets', 'create_production_targets', 'edit_production_targets', 'delete_production_targets',
            'view_rearing_targets', 'create_rearing_targets', 'edit_rearing_targets', 'delete_rearing_targets',

            // Activity Logs (admin only)
            'view_activity_logs',

            // Reports & Dashboard
            'view_reports', 'export_reports',
            'view_dashboard',

            // Roles & Permissions Management (admin only)
            'view_roles', 'create_roles', 'edit_roles', 'delete_roles',
            'view_permissions', 'create_permissions', 'edit_permissions', 'delete_permissions',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        // ============================================

        $tenants = Tenant::query()->orderBy('name')->get();

        foreach ($tenants as $tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

            // ADMIN - Full access to everything
            $adminRole = Role::firstOrCreate([
                'name' => 'admin',
                'tenant_id' => $tenant->id,
                'guard_name' => 'web',
            ]);
            $adminRole->givePermissionTo(Permission::all());

            // MANAGER - Operations, Inventory, Reports (no farm/house/batch management)
            $managerRole = Role::firstOrCreate([
                'name' => 'manager',
                'tenant_id' => $tenant->id,
                'guard_name' => 'web',
            ]);
            $managerRole->givePermissionTo([
                // Can view farm structure but not modify
                'view_farms', 'view_houses', 'view_batches',

                // Full access to daily operations
                'view_daily_productions', 'create_daily_productions', 'edit_daily_productions', 'delete_daily_productions',
                'view_daily_feed_intakes', 'create_daily_feed_intakes', 'edit_daily_feed_intakes', 'delete_daily_feed_intakes',
                'view_daily_water_usages', 'create_daily_water_usages', 'edit_daily_water_usages', 'delete_daily_water_usages',
                'view_weight_samples', 'create_weight_samples', 'edit_weight_samples', 'delete_weight_samples',
                'view_mortality_logs', 'create_mortality_logs', 'edit_mortality_logs', 'delete_mortality_logs',

                // Full access to health
                'view_vaccination_events', 'create_vaccination_events', 'edit_vaccination_events', 'delete_vaccination_events',
                'view_health_treatments', 'create_health_treatments', 'edit_health_treatments', 'delete_health_treatments',

                // Full access to inventory
                'view_suppliers', 'create_suppliers', 'edit_suppliers', 'delete_suppliers',
                'view_inventory_items', 'create_inventory_items', 'edit_inventory_items', 'delete_inventory_items',
                'view_inventory_lots', 'create_inventory_lots', 'edit_inventory_lots', 'delete_inventory_lots',
                'view_inventory_movements', 'create_inventory_movements', 'edit_inventory_movements', 'delete_inventory_movements',

                // View sales & expenses
                'view_customers', 'view_sales_orders', 'view_expenses',

                // Egg stock adjustments - managers can view and create
                'view_egg_stock_adjustments', 'create_egg_stock_adjustments',

                // Reports
                'view_reports', 'export_reports',
                'view_dashboard',
            ]);

            // STAFF - Can create daily entries, but not delete/edit old ones
            $staffRole = Role::firstOrCreate([
                'name' => 'staff',
                'tenant_id' => $tenant->id,
                'guard_name' => 'web',
            ]);
            $staffRole->givePermissionTo([
                // View only for farm structure
                'view_farms', 'view_houses', 'view_batches',

                // Daily operations - view and create only (edit/delete restricted to same-day in policy)
                'view_daily_productions', 'create_daily_productions', 'edit_daily_productions',
                'view_daily_feed_intakes', 'create_daily_feed_intakes', 'edit_daily_feed_intakes',
                'view_daily_water_usages', 'create_daily_water_usages', 'edit_daily_water_usages',
                'view_weight_samples', 'create_weight_samples', 'edit_weight_samples',
                'view_mortality_logs', 'create_mortality_logs', 'edit_mortality_logs',

                // Health - view and create
                'view_vaccination_events', 'create_vaccination_events',
                'view_health_treatments', 'create_health_treatments',

                // Inventory - view only
                'view_suppliers', 'view_inventory_items', 'view_inventory_lots', 'view_inventory_movements',

                // Dashboard
                'view_dashboard',
            ]);

            $this->command->info("Roles created for tenant: {$tenant->name}");
            $this->command->table(
                ['Role', 'Permissions Count'],
                [
                    ['admin', $adminRole->permissions->count()],
                    ['manager', $managerRole->permissions->count()],
                    ['staff', $staffRole->permissions->count()],
                ]
            );
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
