<?php

namespace App\Models;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $context = app(TenantContext::class);
            $tenantId = $context->currentTenantId();

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

            $context = app(TenantContext::class);
            $tenantId = $context->currentTenantId()
                ?? static::resolveTenantIdFromSubject($model)
                ?? static::resolveTenantIdFromCauser($model);

            if ($tenantId) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function resolveTenantIdFromSubject(Model $model): ?int
    {
        $subjectType = $model->getAttribute('subject_type');
        $subjectId = $model->getAttribute('subject_id');

        if (!$subjectType || !$subjectId || !class_exists($subjectType)) {
            return null;
        }

        try {
            $subject = $subjectType::withoutGlobalScopes()->find($subjectId);
        } catch (\Throwable $e) {
            return null;
        }

        $tenantId = $subject?->getAttribute('tenant_id');

        return $tenantId ? (int) $tenantId : null;
    }

    protected static function resolveTenantIdFromCauser(Model $model): ?int
    {
        $causerType = $model->getAttribute('causer_type');
        $causerId = $model->getAttribute('causer_id');

        if (!$causerType || !$causerId || !class_exists($causerType)) {
            return null;
        }

        try {
            $causer = $causerType::withoutGlobalScopes()->find($causerId);
        } catch (\Throwable $e) {
            return null;
        }

        $tenantId = $causer?->getAttribute('tenant_id');

        return $tenantId ? (int) $tenantId : null;
    }
}
