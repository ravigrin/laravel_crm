<?php

namespace App\Console\Commands\Horizon;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class HorizonClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear-jobs
                            {--failed : Clear only failed jobs}
                            {--completed : Clear only completed jobs}
                            {--recent : Clear only recent jobs}
                            {--all : Clear all job history}
                            {--force : Force clear without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Horizon job history';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $failed = $this->option('failed');
        $completed = $this->option('completed');
        $recent = $this->option('recent');
        $all = $this->option('all');
        $force = $this->option('force');

        // If no specific option is provided, show help
        if (!$failed && !$completed && !$recent && !$all) {
            $this->showHelp();
            return 0;
        }

        // Confirmation
        if (!$force && !$this->confirm('Are you sure you want to clear the selected job history?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $cleared = [];

        try {
            if ($all || $failed) {
                $cleared['failed'] = $this->clearFailedJobs();
            }

            if ($all || $completed) {
                $cleared['completed'] = $this->clearCompletedJobs();
            }

            if ($all || $recent) {
                $cleared['recent'] = $this->clearRecentJobs();
            }

            $this->displayResults($cleared);
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Failed to clear jobs: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show help information
     */
    protected function showHelp(): void
    {
        $this->info('🧹 Horizon Job History Clearer');
        $this->newLine();
        $this->info('Available options:');
        $this->line('  --failed     Clear failed jobs');
        $this->line('  --completed  Clear completed jobs');
        $this->line('  --recent     Clear recent jobs');
        $this->line('  --all        Clear all job history');
        $this->line('  --force      Skip confirmation prompt');
        $this->newLine();
        $this->info('Examples:');
        $this->line('  php artisan horizon:clear-jobs --failed');
        $this->line('  php artisan horizon:clear-jobs --all --force');
    }

    /**
     * Clear failed jobs
     */
    protected function clearFailedJobs(): int
    {
        $count = 0;
        
        // Clear failed jobs list
        $failedJobs = Redis::lrange('horizon:failed_jobs', 0, -1);
        $count += count($failedJobs);
        Redis::del('horizon:failed_jobs');

        // Clear recent failed jobs
        $recentFailed = Redis::lrange('horizon:recent_failed_jobs', 0, -1);
        $count += count($recentFailed);
        Redis::del('horizon:recent_failed_jobs');

        return $count;
    }

    /**
     * Clear completed jobs
     */
    protected function clearCompletedJobs(): int
    {
        $count = 0;

        // Clear completed jobs list
        $completedJobs = Redis::lrange('horizon:completed_jobs', 0, -1);
        $count += count($completedJobs);
        Redis::del('horizon:completed_jobs');

        // Clear recent completed jobs
        $recentCompleted = Redis::lrange('horizon:recent_jobs', 0, -1);
        $count += count($recentCompleted);
        Redis::del('horizon:recent_jobs');

        return $count;
    }

    /**
     * Clear recent jobs
     */
    protected function clearRecentJobs(): int
    {
        $count = 0;

        // Clear recent jobs list
        $recentJobs = Redis::lrange('horizon:recent_jobs', 0, -1);
        $count += count($recentJobs);
        Redis::del('horizon:recent_jobs');

        return $count;
    }

    /**
     * Display clearing results
     */
    protected function displayResults(array $cleared): void
    {
        $this->info('✅ Job history cleared successfully!');
        $this->newLine();

        $total = 0;
        foreach ($cleared as $type => $count) {
            $this->line("🗑️  {$type}: {$count} jobs cleared");
            $total += $count;
        }

        $this->newLine();
        $this->info("📊 Total jobs cleared: {$total}");
    }
}

