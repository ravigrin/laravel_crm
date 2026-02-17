<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootHorizon();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Boot Horizon with authentication
     */
    protected function bootHorizon(): void
    {
        // Horizon authentication
        Horizon::auth(function ($request) {
            // In production, you should implement proper authentication
            // For now, we'll allow access in local environment
            if (app()->environment('local')) {
                return true;
            }

            // In production, you might want to check for admin users
            // return $request->user() && $request->user()->isAdmin();
            Horizon::auth(function ($request) {
                return $request->user() && $request->user()->isAdmin();
            });
            // For development, allow all access
            return true;
        });
    }
}
