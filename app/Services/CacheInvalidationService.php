<?php

namespace App\Services;

use App\Interfaces\QueryCacheServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class CacheInvalidationService
{
    protected QueryCacheServiceInterface $cacheService;

    public function __construct(QueryCacheServiceInterface $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->registerEventListeners();
    }

    /**
     * Register event listeners for cache invalidation
     */
    protected function registerEventListeners(): void
    {
        // Listen to model events
        Event::listen('eloquent.saved: *', function (Model $model) {
            $this->handleModelSaved($model);
        });

        Event::listen('eloquent.updated: *', function (Model $model) {
            $this->handleModelUpdated($model);
        });

        Event::listen('eloquent.deleted: *', function (Model $model) {
            $this->handleModelDeleted($model);
        });

        Event::listen('eloquent.created: *', function (Model $model) {
            $this->handleModelCreated($model);
        });
    }

    /**
     * Handle model saved event
     */
    protected function handleModelSaved(Model $model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Handle model updated event
     */
    protected function handleModelUpdated(Model $model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Handle model deleted event
     */
    protected function handleModelDeleted(Model $model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Handle model created event
     */
    protected function handleModelCreated(Model $model): void
    {
        $this->invalidateModelCache($model);
    }

    /**
     * Invalidate cache for specific model
     */
    public function invalidateModelCache(Model $model): bool
    {
        try {
            $modelClass = get_class($model);
            
            Log::debug('Invalidating cache for model', [
                'model' => $modelClass,
                'id' => $model->getKey()
            ]);

            return $this->cacheService->forgetModel($modelClass);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate model cache', [
                'model' => get_class($model),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache for specific model class
     */
    public function invalidateModelClassCache(string $modelClass): bool
    {
        try {
            Log::debug('Invalidating cache for model class', [
                'model' => $modelClass
            ]);

            return $this->cacheService->forgetModel($modelClass);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate model class cache', [
                'model' => $modelClass,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags): bool
    {
        try {
            Log::debug('Invalidating cache by tags', [
                'tags' => $tags
            ]);

            return $this->cacheService->forgetByTags($tags);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache by tags', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache for related models
     */
    public function invalidateRelatedCache(Model $model, array $relations = []): bool
    {
        try {
            $results = [];
            
            foreach ($relations as $relation) {
                if ($model->relationLoaded($relation)) {
                    $related = $model->getRelation($relation);
                    
                    if ($related instanceof Model) {
                        $results[] = $this->invalidateModelCache($related);
                    } elseif (is_iterable($related)) {
                        foreach ($related as $item) {
                            if ($item instanceof Model) {
                                $results[] = $this->invalidateModelCache($item);
                            }
                        }
                    }
                }
            }

            return !in_array(false, $results, true);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate related cache', [
                'model' => get_class($model),
                'relations' => $relations,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Warm up cache for specific model
     */
    public function warmUpModelCache(string $modelClass, array $conditions = []): bool
    {
        try {
            if (!class_exists($modelClass)) {
                return false;
            }

            Log::debug('Warming up cache for model', [
                'model' => $modelClass,
                'conditions' => $conditions
            ]);

            $query = $modelClass::query();
            
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }

            $cacheService = app(QueryCacheServiceInterface::class);
            $config = $cacheService->getModelConfig($modelClass);
            
            if ($config['cache_queries']) {
                $cacheService->rememberQuery($query, $config['ttl']);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to warm up model cache', [
                'model' => $modelClass,
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all application cache
     */
    public function clearAllCache(): bool
    {
        try {
            \Illuminate\Support\Facades\Cache::flush();
            
            Log::info('All cache cleared');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear all cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $driver = config('cache.default');
            $stats = [
                'driver' => $driver,
                'timestamp' => now()->toISOString(),
            ];

            // Add driver-specific stats if available
            switch ($driver) {
                case 'redis':
                    $stats = array_merge($stats, $this->getRedisStats());
                    break;
                case 'database':
                    $stats = array_merge($stats, $this->getDatabaseStats());
                    break;
                default:
                    $stats['message'] = 'Stats not available for this driver';
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get Redis cache statistics
     */
    protected function getRedisStats(): array
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection('cache');
            $info = $redis->info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return ['redis_error' => $e->getMessage()];
        }
    }

    /**
     * Get database cache statistics
     */
    protected function getDatabaseStats(): array
    {
        try {
            $table = config('cache.stores.database.table', 'cache');
            $count = \Illuminate\Support\Facades\DB::table($table)->count();
            
            return [
                'cache_entries_count' => $count,
            ];
        } catch (\Exception $e) {
            return ['database_error' => $e->getMessage()];
        }
    }
}

