<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies - Laravel will auto-discover from App\Policies namespace
        // based on convention: ModelPolicy for Model
        Schema::defaultStringLength(191);
        // Implicitly grant "admin" role all permissions
        // This works in the app by using gate interception
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });
    }
}
