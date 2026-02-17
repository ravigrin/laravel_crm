<?php

namespace App\Jobs\Integrations;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Batchable as BusBatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class DispatchLeadBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $leadId;
    public int $quizId;
    public array $integrationJobClasses;

    public function __construct(int $leadId, int $quizId, array $integrationJobClasses)
    {
        $this->leadId = $leadId;
        $this->quizId = $quizId;
        $this->integrationJobClasses = $integrationJobClasses;
    }

    public function handle(): void
    {
        $jobs = [];
        foreach ($this->integrationJobClasses as $jobClass) {
            $jobs[] = new $jobClass($this->leadId);
        }

        Bus::batch($jobs)
            ->name('lead:'.$this->leadId.' quiz:'.$this->quizId)
            ->allowFailures()
            ->onQueue('integrations')
            ->dispatch();
    }
}




