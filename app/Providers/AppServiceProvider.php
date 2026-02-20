<?php

namespace App\Providers;

use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies - Laravel will auto-discover from App\Policies namespace
        // based on convention: ModelPolicy for Model
        Schema::defaultStringLength(191);
        
        // Register policies for Spatie models (not auto-discovered)
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        
        // Implicitly grant "admin" role all permissions
        // This works in the app by using gate interception
        Gate::before(function ($user, $ability) {
            return $user->is_super_admin ? true : null;
        });
    }
}
