<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

interface QueryCacheServiceInterface
{
    /**
     * Cache query results with automatic key generation
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Cache Eloquent query results
     */
    public function rememberQuery(Builder $query, int $ttl, ?string $key = null): mixed;

    /**
     * Cache model find operations
     */
    public function rememberFind(string $model, $id, int $ttl): ?Model;

    /**
     * Cache model collection operations
     */
    public function rememberWhere(string $model, array $conditions, int $ttl): mixed;

    /**
     * Invalidate cache by key
     */
    public function forget(string $key): bool;

    /**
     * Invalidate cache by tags
     */
    public function forgetByTags(array $tags): bool;

    /**
     * Invalidate all cache for a model
     */
    public function forgetModel(string $modelClass): bool;

    /**
     * Generate cache key for model operations
     */
    public function generateKey(string $model, string $operation, array $params = []): string;

    /**
     * Get cache tags for model
     */
    public function getModelTags(string $modelClass): array;
}

