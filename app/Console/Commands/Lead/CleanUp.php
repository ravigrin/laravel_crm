<?php

namespace App\Console\Commands\Lead;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanUp extends Command
{
    protected $signature = 'leads:clean_up
                            {--force : Delete soft deleted items}
                            {--abandoned : Soft delete abandoned items}';

    protected $description = 'Remove deleted and abandoned leads from database';

    protected $storeTimeYears = 2;

    /**
     * Handle cleaning
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->option('abandoned')) {
            $this->info('Please, specify at least one of this options: [--force] or [--abandoned]');
            return 1;
        }

        if ($this->option('force')) {
            $this->forceDeleteRows();
        }

        if ($this->option('abandoned')) {
            $this->deleteAbandoned();
        }

        return 0;
    }

    /**
     * Calculate searchable date (today - X years)
     * @param null $storeTimeYears
     * @return Carbon
     */
    private function getSearchDate($storeTimeYears = null)
    {
        if (!$storeTimeYears) {
            $storeTimeYears = $this->storeTimeYears;
        }

        $time = Carbon::now();
        return $time->subYears($storeTimeYears);
    }

    /**
     * Force delete soft deleted rows
     */
    private function forceDeleteRows()
    {
        $searchDate = $this->getSearchDate();

        try {
            Lead::onlyTrashed()
                ->where('deleted_at', '<', $searchDate)
                ->forceDelete();
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage());
        }
    }

    /**
     * Delete leads, which was abandoned (Soft deleting)
     */
    private function deleteAbandoned()
    {
        try {
            $searchDate = $this->getSearchDate();
            DB::table(with(new Lead())->getTable())
                ->where('updated_at', '<', $searchDate)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => Carbon::now()->format('Y-m-d H:i:s')
                ]);
        } catch (\Exception $exception) {
            \Log::critical($exception->getMessage());
        }
    }
}
