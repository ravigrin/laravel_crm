<?php

namespace App\Console\Commands\Lead;

use App\Models\Lead;
use Illuminate\Console\Command;

class Restore extends Command
{
    protected $signature = 'leads:restore
                            {ids* : Leads ids (use whitespace as separator)}';

    protected $description = 'Restore soft deleted leads by ids (use whitespace as separator)';

    public function handle(): int
    {
        if (count($this->argument('ids'))) {
            try {
                Lead::onlyTrashed()
                    ->whereIn('id', $this->argument('ids'))
                    ->restore();
                
                $this->info('Leads restored successfully');
                return 0;
            } catch (\Exception $exception) {
                \Log::critical($exception->getMessage(), $exception->getTrace());
                $this->error('Failed to restore leads: ' . $exception->getMessage());
                return 1;
            }
        }
        
        $this->error('No lead IDs provided');
        return 1;
    }
}
