<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\CurrentTenantContainer;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $tenant = $context->resolve($request);

        $context->setTenant($tenant);

        if (class_exists(CurrentTenantContainer::class)) {
            CurrentTenantContainer::set($tenant);
        }

        $context->applyPermissionContext($tenant);

        return $next($request);
    }
}
