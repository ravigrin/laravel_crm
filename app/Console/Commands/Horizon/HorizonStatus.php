<?php

namespace App\Console\Commands\Horizon;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;

class HorizonStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:status-check
                            {--json : Output status as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of Laravel Horizon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $status = $this->getHorizonStatus();

        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayStatus($status);
        return 0;
    }

    /**
     * Get comprehensive Horizon status
     */
    protected function getHorizonStatus(): array
    {
        $status = [
            'timestamp' => now()->toISOString(),
            'process_running' => $this->isProcessRunning(),
            'redis_connected' => $this->isRedisConnected(),
            'supervisors' => $this->getSupervisors(),
            'queues' => $this->getQueueStatus(),
            'jobs' => $this->getJobStats(),
            'dashboard_url' => config('app.url') . '/horizon',
        ];

        return $status;
    }

    /**
     * Check if Horizon process is running
     */
    protected function isProcessRunning(): bool
    {
        try {
            $result = Process::run('pgrep -f "artisan horizon"');
            return $result->successful() && !empty(trim($result->output()));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if Redis is connected
     */
    protected function isRedisConnected(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get supervisors information
     */
    protected function getSupervisors(): array
    {
        try {
            $supervisors = Redis::hgetall('horizon:supervisors');
            return $supervisors;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get queue status
     */
    protected function getQueueStatus(): array
    {
        try {
            $queues = ['default', 'high', 'low'];
            $status = [];

            foreach ($queues as $queue) {
                $length = Redis::llen("queues:{$queue}");
                $status[$queue] = [
                    'length' => $length,
                    'pending' => $length > 0,
                ];
            }

            return $status;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get job statistics
     */
    protected function getJobStats(): array
    {
        try {
            $stats = [
                'completed' => Redis::get('horizon:completed_jobs') ?? 0,
                'failed' => Redis::get('horizon:failed_jobs') ?? 0,
                'recent' => count(Redis::lrange('horizon:recent_jobs', 0, -1)),
            ];

            return $stats;
        } catch (\Exception $e) {
            return [
                'completed' => 0,
                'failed' => 0,
                'recent' => 0,
            ];
        }
    }

    /**
     * Display status in a readable format
     */
    protected function displayStatus(array $status): void
    {
        $this->info('📊 Laravel Horizon Status');
        $this->newLine();

        // Process status
        $processStatus = $status['process_running'] ? '🟢 Running' : '🔴 Stopped';
        $this->line("Process: {$processStatus}");

        // Redis status
        $redisStatus = $status['redis_connected'] ? '🟢 Connected' : '🔴 Disconnected';
        $this->line("Redis: {$redisStatus}");

        // Dashboard URL
        $this->line("Dashboard: <info>{$status['dashboard_url']}</info>");
        $this->newLine();

        // Supervisors
        $this->info('👥 Supervisors:');
        if (empty($status['supervisors'])) {
            $this->line('  No supervisors running');
        } else {
            foreach ($status['supervisors'] as $name => $data) {
                $this->line("  • {$name}");
            }
        }
        $this->newLine();

        // Queue status
        $this->info('📋 Queue Status:');
        foreach ($status['queues'] as $queue => $info) {
            $statusIcon = $info['pending'] ? '🟡' : '🟢';
            $this->line("  {$statusIcon} {$queue}: {$info['length']} jobs");
        }
        $this->newLine();

        // Job statistics
        $this->info('📈 Job Statistics:');
        $this->line("  ✅ Completed: {$status['jobs']['completed']}");
        $this->line("  ❌ Failed: {$status['jobs']['failed']}");
        $this->line("  📝 Recent: {$status['jobs']['recent']}");
        $this->newLine();

        // Commands
        $this->info('🛠️  Available Commands:');
        $this->line('  • php artisan horizon:start-daemon - Start Horizon');
        $this->line('  • php artisan horizon:terminate - Stop Horizon');
        $this->line('  • php artisan horizon:status-check - Check status');
        $this->line('  • php artisan horizon:clear - Clear failed jobs');
    }
}

