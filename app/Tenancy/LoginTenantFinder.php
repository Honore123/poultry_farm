<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class LoginTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
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
}
