<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenantOrTemplate
{
    protected static function bootBelongsToTenantOrTemplate(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $context = app(TenantContext::class);
            $tenantId = $context->currentTenantId();

            if (!$tenantId) {
                $user = auth()->user();
                if ($user && !$user->is_super_admin && $user->tenant_id) {
                    $tenantId = $user->tenant_id;
                    $context->setTenant($user->tenant);
                    $context->applyPermissionContext($user->tenant);
                }
            }

            $table = $builder->getModel()->getTable();

            if ($tenantId) {
                $builder->where($table . '.tenant_id', $tenantId);
                return;
            }

            if ($context->isSuperAdmin()) {
                $builder->whereNull($table . '.tenant_id');
                return;
            }

            if ($context->shouldBlockWithoutTenant()) {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('tenant_id') !== null) {
                return;
            }

            $context = app(TenantContext::class);
            $tenantId = $context->currentTenantId();

            if (!$tenantId) {
                $user = auth()->user();
                if ($user && !$user->is_super_admin && $user->tenant_id) {
                    $tenantId = $user->tenant_id;
                }
            }

            if ($tenantId) {
                $model->setAttribute('tenant_id', $tenantId);
                return;
            }

            if (!app()->runningInConsole() && !$context->isSuperAdmin()) {
                throw new \RuntimeException('A tenant must be selected to create this record.');
            }
        });
    }
}
