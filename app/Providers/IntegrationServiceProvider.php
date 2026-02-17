<?php

namespace App\Providers;

use App\Interfaces\IntegrationChannelInterface;
use App\Services\Integration\IntegrationFactory;
use App\Services\Integration\IntegrationManager;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register IntegrationFactory as singleton
        $this->app->singleton(IntegrationFactory::class);

        // Register IntegrationManager as singleton
        $this->app->singleton(IntegrationManager::class);

        // Register IntegrationChannelInterface with factory
        $this->app->bind(IntegrationChannelInterface::class, function ($app, $parameters) {
            $type = $parameters['type'] ?? 'email';
            $config = $parameters['config'] ?? [];
            
            $factory = $app->make(IntegrationFactory::class);
            return $factory->create($type, $config);
        });
    }
}