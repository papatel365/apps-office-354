<?php

namespace App\Providers;

use App\Services\Permission\UserPermissionService;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register as singleton - same instance per request
        $this->app->singleton('permission', function ($app) {
            return new UserPermissionService();
        });

        // Also bind to interface if needed
        $this->app->bind(UserPermissionService::class, function ($app) {
            return $app->make('permission');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
