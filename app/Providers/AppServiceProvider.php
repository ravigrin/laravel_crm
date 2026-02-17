<?php

namespace App\Providers;

use App\Interfaces\CrmServiceInterface;
use App\Interfaces\HttpServiceInterface;
use App\Interfaces\LocaleServiceInterface;
use App\Interfaces\MailServiceInterface;
use App\Interfaces\QueryCacheServiceInterface;
use App\Models\Lead;
use App\Observers\LeadLifecycleObserver;
use App\Observers\LogLeadObserver;
use App\Services\CrmService;
use App\Services\FieldMapperService;
use App\Services\HttpService;
use App\Services\LocaleService;
use App\Services\MailService;
use App\Services\QueryCacheService;
use App\Services\CacheInvalidationService;
use App\Services\Integration\IntegrationFactory;
use App\Services\Integration\IntegrationManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Mail Service
        $this->app->singleton(MailServiceInterface::class, MailService::class);

        // Locale Service
        $this->app->singleton(LocaleServiceInterface::class, LocaleService::class);

        // CRM Service
        $this->app->singleton(CrmServiceInterface::class, CrmService::class);

        // Field Mapper Service
        $this->app->singleton(FieldMapperService::class);

        // Query Cache Service
        $this->app->singleton(QueryCacheServiceInterface::class, QueryCacheService::class);

        // Cache Invalidation Service
        $this->app->singleton(CacheInvalidationService::class);

        // HTTP Service - register as singleton with default base URL
        $this->app->singleton(HttpServiceInterface::class, function ($app) {
            return new HttpService(config('app.url'));
        });

        // Integration Factory
        $this->app->singleton(IntegrationFactory::class);

        // Integration Manager
        $this->app->singleton(IntegrationManager::class);
    }

    public function boot()
    {
        Lead::observe(LogLeadObserver::class);
        Lead::observe(LeadLifecycleObserver::class);
        
        // Register L5-Swagger docs file route AFTER L5-Swagger has registered its routes
        // This ensures our route takes precedence for /docs/{file} requests
        $this->app->booted(function () {
            // Register our custom route that handles /docs/{file} for JSON/YAML files
            // Use Route::group to ensure it's in the web middleware group like L5-Swagger routes
            \Illuminate\Support\Facades\Route::middleware('web')->group(function () {
                \Illuminate\Support\Facades\Route::get('docs/{file}', function ($file) {
                // Only handle JSON and YAML files to avoid conflicts with asset routes
                if (!preg_match('/\.(json|yaml)$/i', $file)) {
                    abort(404);
                }
                
                $documentation = 'default';
                $configFactory = resolve(\L5Swagger\ConfigFactory::class);
                $config = $configFactory->documentationConfig($documentation);
                
                $fileSystem = new \Illuminate\Filesystem\Filesystem();
                $formatToUseForDocs = $config['paths']['format_to_use_for_docs'] ?? 'json';
                $yamlFormat = ($formatToUseForDocs === 'yaml');
                
                // Determine the actual file path based on config
                $actualFile = $yamlFormat ? $config['paths']['docs_yaml'] : $config['paths']['docs_json'];
                
                // Verify the requested file matches the configured file
                if ($file !== $actualFile) {
                    abort(404, 'Documentation file not found');
                }
                
                $filePath = sprintf(
                    '%s/%s',
                    $config['paths']['docs'],
                    $actualFile
                );
                
                if ($config['generate_always']) {
                    $generator = resolve(\L5Swagger\GeneratorFactory::class)->make($documentation);
                    try {
                        $generator->generateDocs();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error($e);
                        abort(404, sprintf('Unable to generate documentation file: %s', $e->getMessage()));
                    }
                }
                
                if (! $fileSystem->exists($filePath)) {
                    abort(404, sprintf('Unable to locate documentation file at: "%s"', $filePath));
                }
                
                $content = $fileSystem->get($filePath);
                
                if ($yamlFormat) {
                    return response($content, 200, [
                        'Content-Type' => 'application/yaml',
                        'Content-Disposition' => 'inline',
                    ]);
                }
                
                return response($content, 200, [
                    'Content-Type' => 'application/json',
                ]);
                })->where('file', '[^/]+\.(json|yaml)$')->name('l5-swagger.default.docs.file');
            });
        });
    }
}
