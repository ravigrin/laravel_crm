<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // For testing purposes, allow all actions
        // In production, implement proper authorization policies
        Gate::define('viewAny', function ($user = null) {
            return true;
        });
        
        Gate::define('view', function ($user = null, $model = null) {
            return true;
        });
        
        Gate::define('create', function ($user = null) {
            return true;
        });
        
        Gate::define('update', function ($user = null, $model = null) {
            return true;
        });
        
        Gate::define('delete', function ($user = null, $model = null) {
            return true;
        });
    }
}
