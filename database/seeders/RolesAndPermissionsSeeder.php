<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = DefaultRolePermissions::all();

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
            $managerRole->givePermissionTo(DefaultRolePermissions::manager());

            // STAFF - Can create daily entries, but not delete/edit old ones
            $staffRole = Role::firstOrCreate([
                'name' => 'staff',
                'tenant_id' => $tenant->id,
                'guard_name' => 'web',
            ]);
            $staffRole->givePermissionTo(DefaultRolePermissions::staff());

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
