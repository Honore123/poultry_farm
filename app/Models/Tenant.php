<?php

namespace App\Models;

use App\Models\Role;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;
use Spatie\Permission\Models\Permission;

class Tenant extends SpatieTenant
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant): void {
            $context = app(TenantContext::class);

            $context->runForTenant($tenant, function () use ($tenant): void {
                $defaultRoles = ['admin', 'manager', 'staff'];

                foreach ($defaultRoles as $roleName) {
                    Role::query()->firstOrCreate(
                        [
                            'name' => $roleName,
                            'guard_name' => 'web',
                            'tenant_id' => $tenant->id,
                        ]
                    );
                }

                $adminRole = Role::query()
                    ->where('name', 'admin')
                    ->first();

                if ($adminRole) {
                    $adminRole->syncPermissions(Permission::all());
                }
            });

            static::seedTargetTemplates($tenant);
        });
    }

    protected static function seedTargetTemplates(Tenant $tenant): void
    {
        $targets = [
            'feed_intake_targets' => null,
            'production_targets' => 'week',
            'rearing_targets' => 'week',
            'egg_grading_targets' => 'week',
            'production_cycle_targets' => 'cycle_end_week',
        ];

        foreach ($targets as $table => $uniqueColumn) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $columns = array_values(array_diff($columns, ['id', 'tenant_id']));

            $templates = DB::table($table)
                ->whereNull('tenant_id')
                ->get($columns);

            if ($templates->isEmpty()) {
                continue;
            }

            foreach ($templates as $template) {
                $data = (array) $template;
                $data['tenant_id'] = $tenant->id;

                if ($uniqueColumn && isset($data[$uniqueColumn])) {
                    $exists = DB::table($table)
                        ->where('tenant_id', $tenant->id)
                        ->where($uniqueColumn, $data[$uniqueColumn])
                        ->exists();

                    if ($exists) {
                        continue;
                    }
                }

                DB::table($table)->insert($data);
            }
        }
    }
}
