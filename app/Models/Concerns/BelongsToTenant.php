<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
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

            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
                return;
            }

            if ($context->shouldBlockWithoutTenant()) {
                $builder->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('tenant_id')) {
                return;
            }

            $tenantId = app(TenantContext::class)->currentTenantId();
            if ($tenantId) {
                $model->setAttribute('tenant_id', $tenantId);
                return;
            }

            if (!app()->runningInConsole()) {
                throw new \RuntimeException('A tenant must be selected to create this record.');
            }
        });
    }
}
