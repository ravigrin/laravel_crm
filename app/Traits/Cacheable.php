<?php

namespace App\Traits;

use App\Services\QueryCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait Cacheable
{
    /**
     * Boot the trait
     */
    protected static function bootCacheable(): void
    {
        // Register model events for cache invalidation
        static::saved(function (Model $model) {
            static::invalidateCache($model);
        });

        static::deleted(function (Model $model) {
            static::invalidateCache($model);
        });

        static::updated(function (Model $model) {
            static::invalidateCache($model);
        });
    }

    /**
     * Get cached query results
     */
    public function scopeCached(Builder $query, ?int $ttl = null): mixed
    {
        $cacheService = app(QueryCacheService::class);
        $config = $cacheService->getModelConfig(static::class);
        
        if (!$config['cache_queries']) {
            return $query->get();
        }

        $ttl = $ttl ?? $config['ttl'];
        return $cacheService->rememberQuery($query, $ttl);
    }

    /**
     * Find model with caching
     */
    public static function findCached($id, ?int $ttl = null): ?Model
    {
        $cacheService = app(QueryCacheService::class);
        $config = $cacheService->getModelConfig(static::class);
        
        if (!$config['cache_find']) {
            return static::find($id);
        }

        $ttl = $ttl ?? $config['ttl'];
        return $cacheService->rememberFind(static::class, $id, $ttl);
    }

    /**
     * Find many models with caching
     */
    public static function findManyCached(array $ids, ?int $ttl = null): mixed
    {
        $cacheService = app(QueryCacheService::class);
        $config = $cacheService->getModelConfig(static::class);
        
        if (!$config['cache_find']) {
            return static::findMany($ids);
        }

        $ttl = $ttl ?? $config['ttl'];
        $key = $cacheService->generateKey(static::class, 'find_many', ['ids' => $ids]);
        
        return $cacheService->remember($key, $ttl, function () use ($ids) {
            return static::findMany($ids);
        });
    }

    /**
     * Where query with caching
     */
    public static function whereCached(array $conditions, ?int $ttl = null): mixed
    {
        $cacheService = app(QueryCacheService::class);
        $config = $cacheService->getModelConfig(static::class);
        
        if (!$config['cache_where']) {
            $query = static::query();
            foreach ($conditions as $field => $value) {
                $query->where($field, $value);
            }
            return $query->get();
        }

        $ttl = $ttl ?? $config['ttl'];
        return $cacheService->rememberWhere(static::class, $conditions, $ttl);
    }

    /**
     * Get all models with caching
     */
    public static function allCached(?int $ttl = null): mixed
    {
        $cacheService = app(QueryCacheService::class);
        $config = $cacheService->getModelConfig(static::class);
        
        $ttl = $ttl ?? $config['ttl'];
        $key = $cacheService->generateKey(static::class, 'all');
        
        return $cacheService->remember($key, $ttl, function () {
            return static::all();
        });
    }

    /**
     * Get model configuration
     */
    public function getCacheConfig(): array
    {
        $cacheService = app(QueryCacheService::class);
        return $cacheService->getModelConfig(static::class);
    }

    /**
     * Get cache TTL for this model
     */
    public function getCacheTtl(): int
    {
        $config = $this->getCacheConfig();
        return $config['ttl'];
    }

    /**
     * Get cache tags for this model
     */
    public function getCacheTags(): array
    {
        $config = $this->getCacheConfig();
        return $config['tags'];
    }

    /**
     * Invalidate cache for this model
     */
    public function invalidateModelCache(): bool
    {
        $cacheService = app(QueryCacheService::class);
        return $cacheService->forgetModel(static::class);
    }

    /**
     * Invalidate cache when model is updated
     */
    protected static function invalidateCache(Model $model): void
    {
        $config = $model->getCacheConfig();
        
        // Only invalidate if flushCacheOnUpdate is true (default behavior)
        if ($config['flush_on_update'] ?? true) {
            $model->invalidateModelCache();
        }
    }

    /**
     * Check if model supports caching
     */
    public function supportsCaching(): bool
    {
        $config = $this->getCacheConfig();
        return !empty($config['ttl']) && $config['ttl'] > 0;
    }

    /**
     * Get cache key for this model instance
     */
    public function getCacheKey(): string
    {
        $cacheService = app(QueryCacheService::class);
        return $cacheService->generateKey(static::class, 'instance', [
            'id' => $this->getKey(),
            'updated_at' => $this->updated_at?->timestamp ?? 0
        ]);
    }

    /**
     * Cache this model instance
     */
    public function cache(): void
    {
        if (!$this->supportsCaching()) {
            return;
        }

        $cacheService = app(QueryCacheService::class);
        $key = $this->getCacheKey();
        $ttl = $this->getCacheTtl();
        $tags = $this->getCacheTags();

        try {
            if ($tags) {
                \Illuminate\Support\Facades\Cache::tags($tags)->put($key, $this, $ttl);
            } else {
                \Illuminate\Support\Facades\Cache::put($key, $this, $ttl);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to cache model instance', [
                'model' => static::class,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }
}

