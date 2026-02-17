<?php

namespace App\Console\Commands\Horizon;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class StartHorizon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:start-daemon
                            {--timeout=60 : The number of seconds a child process can run}
                            {--max-jobs=1000 : The number of jobs to process before stopping}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no jobs are available}
                            {--tries=3 : Number of times to attempt a job before logging it failed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Horizon queue daemon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Laravel Horizon...');

        // Check if Horizon is already running
        if ($this->isHorizonRunning()) {
            $this->warn('⚠️  Horizon is already running!');
            $this->info('Use "php artisan horizon:status" to check status');
            $this->info('Use "php artisan horizon:terminate" to stop');
            return 1;
        }

        // Start Horizon in background
        $command = $this->buildHorizonCommand();
        
        try {
            $process = Process::start($command);
            
            $this->info('✅ Horizon started successfully!');
            $this->info("📊 Dashboard: " . config('app.url') . '/horizon');
            $this->info('🔍 Status: php artisan horizon:status');
            $this->info('⏹️  Stop: php artisan horizon:terminate');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to start Horizon: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check if Horizon is already running
     */
    protected function isHorizonRunning(): bool
    {
        try {
            $result = Process::run('pgrep -f "artisan horizon"');
            return $result->successful() && !empty(trim($result->output()));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build the Horizon command with options
     */
    protected function buildHorizonCommand(): string
    {
        $command = 'php artisan horizon';
        
        $options = [
            'timeout' => $this->option('timeout'),
            'max-jobs' => $this->option('max-jobs'),
            'memory' => $this->option('memory'),
            'sleep' => $this->option('sleep'),
            'tries' => $this->option('tries'),
        ];

        foreach ($options as $key => $value) {
            if ($value !== null) {
                $command .= " --{$key}={$value}";
            }
        }

        // Add nohup and redirect output for background process
        $command = "nohup {$command} > storage/logs/horizon.log 2>&1 &";

        return $command;
    }
}

