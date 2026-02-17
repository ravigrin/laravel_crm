<?php

namespace App\Console\Commands\Cache;

use App\Services\CacheInvalidationService;
use Illuminate\Console\Command;

class ClearQueryCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-query 
                            {--model= : Clear cache for specific model}
                            {--tags= : Clear cache for specific tags (comma-separated)}
                            {--all : Clear all query cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear query cache for models';

    /**
     * Execute the console command.
     */
    public function handle(CacheInvalidationService $cacheService): int
    {
        $model = $this->option('model');
        $tags = $this->option('tags');
        $all = $this->option('all');

        if ($all) {
            return $this->clearAllCache($cacheService);
        }

        if ($model) {
            return $this->clearModelCache($cacheService, $model);
        }

        if ($tags) {
            return $this->clearTagsCache($cacheService, $tags);
        }

        $this->error('Please specify --model, --tags, or --all option');
        return 1;
    }

    /**
     * Clear all cache
     */
    protected function clearAllCache(CacheInvalidationService $cacheService): int
    {
        $this->info('Clearing all query cache...');
        
        if ($cacheService->clearAllCache()) {
            $this->info('✅ All query cache cleared successfully');
            return 0;
        } else {
            $this->error('❌ Failed to clear all cache');
            return 1;
        }
    }

    /**
     * Clear cache for specific model
     */
    protected function clearModelCache(CacheInvalidationService $cacheService, string $model): int
    {
        $this->info("Clearing cache for model: {$model}");

        if (!class_exists($model)) {
            $this->error("❌ Model class '{$model}' does not exist");
            return 1;
        }

        if ($cacheService->invalidateModelClassCache($model)) {
            $this->info("✅ Cache cleared for model: {$model}");
            return 0;
        } else {
            $this->error("❌ Failed to clear cache for model: {$model}");
            return 1;
        }
    }

    /**
     * Clear cache for specific tags
     */
    protected function clearTagsCache(CacheInvalidationService $cacheService, string $tags): int
    {
        $tagArray = array_map('trim', explode(',', $tags));
        
        $this->info('Clearing cache for tags: ' . implode(', ', $tagArray));

        if ($cacheService->invalidateByTags($tagArray)) {
            $this->info('✅ Cache cleared for tags: ' . implode(', ', $tagArray));
            return 0;
        } else {
            $this->error('❌ Failed to clear cache for tags: ' . implode(', ', $tagArray));
            return 1;
        }
    }
}

