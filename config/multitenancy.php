<?php

return [
    'tenant_finder' => App\Tenancy\LoginTenantFinder::class,

    'tenant_model' => App\Models\Tenant::class,

    'current_tenant_container' => Spatie\Multitenancy\CurrentTenantContainer::class,

    'switch_tenant_tasks' => [
        // Single-database tenancy: no connection switching needed.
    ],

    'tenant_artisan_commands' => [
        // Add tenant-aware commands here if needed.
    ],
];
