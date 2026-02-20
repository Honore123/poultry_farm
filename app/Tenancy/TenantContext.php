<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class TenantContext
{
    protected ?Tenant $tenant = null;

    public function resolve(Request $request): ?Tenant
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if ($user->is_super_admin) {
            $selectedTenantId = $request->session()->get('tenant_id');
            if (!$selectedTenantId) {
                return null;
            }

            $tenant = Tenant::query()->find($selectedTenantId);

            if (!$tenant) {
                $request->session()->forget('tenant_id');
            }

            return $tenant;
        }

        return $user->tenant_id
            ? $user->tenant
            : null;
    }

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function applyPermissionContext(?Tenant $tenant): void
    {
        if (!class_exists(PermissionRegistrar::class)) {
            return;
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant?->id);

        $baseKey = config('permission.cache.key', 'spatie.permission.cache');
        $cacheKey = $baseKey . '.tenant.' . ($tenant?->id ?: 'all');

        if ($registrar->cacheKey !== $cacheKey) {
            $registrar->cacheKey = $cacheKey;
            $registrar->clearPermissionsCollection();
        }
    }

    public function currentTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function currentTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function isSuperAdmin(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return (bool) ($user?->is_super_admin);
    }

    public function shouldBlockWithoutTenant(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        if (!auth()->check()) {
            return false;
        }

        return !$this->isSuperAdmin();
    }

    public function runForTenant(Tenant $tenant, callable $callback): void
    {
        $previousTenant = $this->tenant;
        $this->tenant = $tenant;

        $this->applyPermissionContext($tenant);

        try {
            $callback();
        } finally {
            $this->tenant = $previousTenant;

            $this->applyPermissionContext($previousTenant);
        }
    }
}
