<?php

namespace App\Console\Commands\Cache;

use App\Services\CacheInvalidationService;
use Illuminate\Console\Command;

class QueryCacheStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:query-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display query cache statistics';

    /**
     * Execute the console command.
     */
    public function handle(CacheInvalidationService $cacheService): int
    {
        $this->info('📊 Query Cache Statistics');
        $this->newLine();

        $stats = $cacheService->getCacheStats();

        if (isset($stats['error'])) {
            $this->error('❌ Failed to get cache statistics: ' . $stats['error']);
            return 1;
        }

        // Display basic info
        $this->line("Driver: <info>{$stats['driver']}</info>");
        $this->line("Timestamp: <info>{$stats['timestamp']}</info>");
        $this->newLine();

        // Display driver-specific stats
        if (isset($stats['redis_version'])) {
            $this->displayRedisStats($stats);
        } elseif (isset($stats['cache_entries_count'])) {
            $this->displayDatabaseStats($stats);
        } elseif (isset($stats['message'])) {
            $this->line("<comment>{$stats['message']}</comment>");
        }

        // Display model configurations
        $this->displayModelConfigs();

        return 0;
    }

    /**
     * Display Redis-specific statistics
     */
    protected function displayRedisStats(array $stats): void
    {
        $this->info('🔴 Redis Cache Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Redis Version', $stats['redis_version']],
                ['Used Memory', $stats['used_memory_human']],
                ['Connected Clients', $stats['connected_clients']],
                ['Total Commands Processed', $stats['total_commands_processed']],
            ]
        );
    }

    /**
     * Display database-specific statistics
     */
    protected function displayDatabaseStats(array $stats): void
    {
        $this->info('🗄️ Database Cache Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Cache Entries Count', $stats['cache_entries_count']],
            ]
        );
    }

    /**
     * Display model configurations
     */
    protected function displayModelConfigs(): void
    {
        $this->newLine();
        $this->info('⚙️ Model Cache Configurations:');

        $configs = config('query_cache.models', []);
        $rows = [];

        foreach ($configs as $model => $config) {
            $modelName = class_basename($model);
            $ttl = $config['ttl'] ?? 'default';
            $tags = implode(', ', $config['tags'] ?? []);
            
            $rows[] = [
                $modelName,
                $ttl . 's',
                $tags,
                $this->formatBoolean($config['cache_find'] ?? false),
                $this->formatBoolean($config['cache_queries'] ?? false),
            ];
        }

        $this->table(
            ['Model', 'TTL', 'Tags', 'Cache Find', 'Cache Queries'],
            $rows
        );
    }

    /**
     * Format boolean value for display
     */
    protected function formatBoolean(bool $value): string
    {
        return $value ? '✅' : '❌';
    }
}

