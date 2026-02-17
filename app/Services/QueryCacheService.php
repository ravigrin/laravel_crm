<?php

namespace App\Services;

use App\Interfaces\QueryCacheServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QueryCacheService implements QueryCacheServiceInterface
{
    protected array $modelConfigs = [];
    protected int $defaultTtl = 3600; // 1 hour

    public function __construct()
    {
        $this->loadModelConfigs();
    }

    /**
     * Cache query results with automatic key generation
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to callback execution
            return $callback();
        }
    }

    /**
     * Cache Eloquent query results
     */
    public function rememberQuery(Builder $query, int $ttl, ?string $key = null): mixed
    {
        if (!$key) {
            $key = $this->generateQueryKey($query);
        }

        $modelClass = get_class($query->getModel());
        $tags = $this->getModelTags($modelClass);

        try {
            if ($tags) {
                return Cache::tags($tags)->remember($key, $ttl, function () use ($query) {
                    return $query->get();
                });
            } else {
                return $this->remember($key, $ttl, function () use ($query) {
                    return $query->get();
                });
            }
        } catch (\Exception $e) {
            Log::error('Cache query failed', [
                'key' => $key,
                'model' => $modelClass,
                'error' => $e->getMessage()
            ]);
            
            return $query->get();
        }
    }

    /**
     * Cache model find operations
     */
    public function rememberFind(string $model, $id, int $ttl): ?Model
    {
        $key = $this->generateKey($model, 'find', ['id' => $id]);
        $tags = $this->getModelTags($model);

        try {
            if ($tags) {
                return Cache::tags($tags)->remember($key, $ttl, function () use ($model, $id) {
                    return $model::find($id);
                });
            } else {
                return $this->remember($key, $ttl, function () use ($model, $id) {
                    return $model::find($id);
                });
            }
        } catch (\Exception $e) {
            Log::error('Cache find failed', [
                'key' => $key,
                'model' => $model,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return $model::find($id);
        }
    }

    /**
     * Cache model collection operations
     */
    public function rememberWhere(string $model, array $conditions, int $ttl): mixed
    {
        $key = $this->generateKey($model, 'where', $conditions);
        $tags = $this->getModelTags($model);

        try {
            if ($tags) {
                return Cache::tags($tags)->remember($key, $ttl, function () use ($model, $conditions) {
                    $query = $model::query();
                    foreach ($conditions as $field => $value) {
                        $query->where($field, $value);
                    }
                    return $query->get();
                });
            } else {
                return $this->remember($key, $ttl, function () use ($model, $conditions) {
                    $query = $model::query();
                    foreach ($conditions as $field => $value) {
                        $query->where($field, $value);
                    }
                    return $query->get();
                });
            }
        } catch (\Exception $e) {
            Log::error('Cache where failed', [
                'key' => $key,
                'model' => $model,
                'conditions' => $conditions,
                'error' => $e->getMessage()
            ]);
            
            $query = $model::query();
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
            return $query->get();
        }
    }

    /**
     * Invalidate cache by key
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error('Cache forget failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache by tags
     */
    public function forgetByTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error('Cache forget by tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalidate all cache for a model
     */
    public function forgetModel(string $modelClass): bool
    {
        $tags = $this->getModelTags($modelClass);
        if ($tags) {
            return $this->forgetByTags($tags);
        }
        
        // Fallback: clear cache with model-specific keys
        return $this->clearModelCache($modelClass);
    }

    /**
     * Generate cache key for model operations
     */
    public function generateKey(string $model, string $operation, array $params = []): string
    {
        $modelName = Str::snake(class_basename($model));
        $paramsHash = md5(serialize($params));
        
        return "model:{$modelName}:{$operation}:{$paramsHash}";
    }

    /**
     * Get cache tags for model
     */
    public function getModelTags(string $modelClass): array
    {
        $modelName = Str::snake(class_basename($modelClass));
        return ["model:{$modelName}", 'models'];
    }

    /**
     * Generate cache key for query
     */
    protected function generateQueryKey(Builder $query): string
    {
        $model = $query->getModel();
        $modelName = Str::snake(class_basename($model));
        
        // Get query SQL and bindings
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // Create hash from SQL and bindings
        $queryHash = md5($sql . serialize($bindings));
        
        return "query:{$modelName}:{$queryHash}";
    }

    /**
     * Load model configurations
     */
    protected function loadModelConfigs(): void
    {
        $this->modelConfigs = [
            \App\Models\Lead::class => [
                'ttl' => 3600, // 1 hour
                'tags' => ['leads', 'models'],
                'cache_find' => true,
                'cache_where' => true,
                'cache_queries' => true,
            ],
            \App\Models\Status::class => [
                'ttl' => 3600, // 1 hour
                'tags' => ['statuses', 'models'],
                'cache_find' => true,
                'cache_where' => true,
                'cache_queries' => true,
            ],
            \App\Models\Email::class => [
                'ttl' => 7200, // 2 hours
                'tags' => ['emails', 'templates', 'models'],
                'cache_find' => true,
                'cache_where' => true,
                'cache_queries' => false,
            ],
        ];
    }

    /**
     * Get model configuration
     */
    public function getModelConfig(string $modelClass): array
    {
        return $this->modelConfigs[$modelClass] ?? [
            'ttl' => $this->defaultTtl,
            'tags' => $this->getModelTags($modelClass),
            'cache_find' => true,
            'cache_where' => true,
            'cache_queries' => true,
        ];
    }

    /**
     * Clear all cache entries for a model (fallback method)
     */
    protected function clearModelCache(string $modelClass): bool
    {
        try {
            $modelName = Str::snake(class_basename($modelClass));
            
            // This is a simplified approach - in production you might want
            // to maintain a list of cache keys or use a more sophisticated approach
            Cache::flush();
            
            Log::info('Model cache cleared', ['model' => $modelClass]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear model cache', [
                'model' => $modelClass,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

