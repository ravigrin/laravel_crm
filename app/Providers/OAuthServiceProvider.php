<?php

namespace App\Providers;

use AmoCRM\Client\AmoCRMApiClient;
use App\Enums\AvailableIntegrations;
use App\Models\OAuth\Service;
use Illuminate\Support\ServiceProvider;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('amocrm_client', function ($app) {
            $service = Service::where('service', AvailableIntegrations::amocrm)->first();
            return new AmoCRMApiClient($service->client_id, $service->client_secret, $service->redirect_url);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
