<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Tenant;
use App\Support\DefaultRolePermissions;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncDefaultRolePermissionsCommand extends Command
{
    protected $signature = 'farm:sync-default-role-permissions
        {--tenant= : Tenant ID to limit the sync}
        {--dry-run : Show counts without writing changes}';

    protected $description = 'Ensure default admin/manager/staff permissions exist for tenants.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $tenantId = $this->option('tenant');

        $permissions = DefaultRolePermissions::all();
        if (!$dryRun) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        }

        $tenants = $tenantId
            ? Tenant::query()->whereKey($tenantId)->get()
            : Tenant::query()->orderBy('name')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found to sync.');
            return self::SUCCESS;
        }

        $synced = 0;
        $context = app(TenantContext::class);

        foreach ($tenants as $tenant) {
            $context->runForTenant($tenant, function () use ($tenant, $dryRun, &$synced): void {
                app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

                $adminRole = Role::query()->firstOrCreate([
                    'name' => 'admin',
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);

                $managerRole = Role::query()->firstOrCreate([
                    'name' => 'manager',
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);

                $staffRole = Role::query()->firstOrCreate([
                    'name' => 'staff',
                    'tenant_id' => $tenant->id,
                    'guard_name' => 'web',
                ]);

                if (!$dryRun) {
                    $adminRole->givePermissionTo(Permission::all());
                    $managerRole->givePermissionTo(DefaultRolePermissions::manager());
                    $staffRole->givePermissionTo(DefaultRolePermissions::staff());
                }

                $synced++;
            });
        }

        $this->info("Synced default role permissions for {$synced} tenant(s).");
        if ($dryRun) {
            $this->warn('Dry run only. No changes were written.');
        }

        return self::SUCCESS;
    }
}
