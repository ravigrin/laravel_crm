<?php

namespace App\Jobs\Integrations;

use App\Models\Lead;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SendLeadToMultipleIntegrationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $leadId;
    public array $integrations;
    public array $credentials;
    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param int $leadId
     * @param array $integrations Array of integration configurations
     * @param array $credentials Array of credentials for each integration
     */
    public function __construct(int $leadId, array $integrations, array $credentials = [])
    {
        $this->leadId = $leadId;
        $this->integrations = $integrations;
        $this->credentials = $credentials;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $lead = Lead::findOrFail($this->leadId);
            
            Log::info('Starting batch dispatch for lead', [
                'lead_id' => $this->leadId,
                'integrations_count' => count($this->integrations)
            ]);

            $jobs = [];
            
            foreach ($this->integrations as $integration) {
                $integrationType = $integration['type'];
                $integrationCredentials = $this->credentials[$integrationType] ?? [];
                
                // Create job for specific integration
                $jobClass = $this->getJobClassForIntegration($integrationType);
                
                if ($jobClass) {
                    $jobs[] = new $jobClass(
                        $this->leadId,
                        $integrationCredentials,
                        $integration['settings'] ?? []
                    );
                } else {
                    Log::warning('No job class found for integration', [
                        'integration_type' => $integrationType,
                        'lead_id' => $this->leadId
                    ]);
                }
            }

            if (empty($jobs)) {
                Log::warning('No valid jobs to dispatch for lead', [
                    'lead_id' => $this->leadId
                ]);
                return;
            }

            // Dispatch batch with callbacks
            $batch = Bus::batch($jobs)
                ->name("Lead {$this->leadId} - Multiple Integrations")
                ->allowFailures() // Allow partial failures
                ->then(function (Batch $batch) {
                    $this->handleBatchSuccess($batch);
                })
                ->catch(function (Batch $batch, \Throwable $e) {
                    $this->handleBatchFailure($batch, $e);
                })
                ->finally(function (Batch $batch) {
                    $this->handleBatchFinally($batch);
                })
                ->dispatch();

            Log::info('Batch dispatched successfully', [
                'lead_id' => $this->leadId,
                'batch_id' => $batch->id,
                'jobs_count' => count($jobs)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch batch for lead', [
                'lead_id' => $this->leadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get job class for integration type
     */
    protected function getJobClassForIntegration(string $integrationType): ?string
    {
        return match ($integrationType) {
            'email' => EmailIntegrationJob::class,
            'amocrm' => AmoCrmIntegrationJob::class,
            'telegram' => TelegramIntegrationJob::class,
            'bitrix24' => Bitrix24IntegrationJob::class,
            'webhooks' => WebhookIntegrationJob::class,
            default => null
        };
    }

    /**
     * Handle successful batch completion
     */
    protected function handleBatchSuccess(Batch $batch): void
    {
        Log::info('Batch completed successfully', [
            'batch_id' => $batch->id,
            'lead_id' => $this->leadId,
            'total_jobs' => $batch->totalJobs,
            'processed_jobs' => $batch->processedJobs(),
            'failed_jobs' => $batch->failedJobs
        ]);

        // Send success notification
        $this->sendNotification('success', [
            'lead_id' => $this->leadId,
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'successful_jobs' => $batch->processedJobs() - $batch->failedJobs,
            'failed_jobs' => $batch->failedJobs
        ]);
    }

    /**
     * Handle batch failure
     */
    protected function handleBatchFailure(Batch $batch, \Throwable $e): void
    {
        Log::error('Batch failed', [
            'batch_id' => $batch->id,
            'lead_id' => $this->leadId,
            'error' => $e->getMessage(),
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs
        ]);

        // Send failure notification
        $this->sendNotification('failure', [
            'lead_id' => $this->leadId,
            'batch_id' => $batch->id,
            'error' => $e->getMessage(),
            'total_jobs' => $batch->totalJobs,
            'failed_jobs' => $batch->failedJobs
        ]);
    }

    /**
     * Handle batch completion (always called)
     */
    protected function handleBatchFinally(Batch $batch): void
    {
        Log::info('Batch processing finished', [
            'batch_id' => $batch->id,
            'lead_id' => $this->leadId,
            'total_jobs' => $batch->totalJobs,
            'processed_jobs' => $batch->processedJobs(),
            'failed_jobs' => $batch->failedJobs,
            'pending_jobs' => $batch->pendingJobs
        ]);

        // Update lead status or send final notification
        $this->updateLeadStatus($batch);
    }

    /**
     * Send notification about batch status
     */
    protected function sendNotification(string $type, array $data): void
    {
        // Dispatch notification job
        \App\Jobs\Integrations\BatchNotificationJob::dispatch(
            "integration_batch_{$type}",
            $data,
            $this->getNotificationRecipients()
        )->onQueue('notifications');
    }

    /**
     * Update lead status based on batch results
     */
    protected function updateLeadStatus(Batch $batch): void
    {
        try {
            $lead = Lead::findOrFail($this->leadId);
            
            $successfulJobs = $batch->processedJobs() - $batch->failedJobs;
            $totalJobs = $batch->totalJobs;
            
            if ($successfulJobs === $totalJobs) {
                // All integrations successful
                $lead->update(['integration_status' => 'completed']);
            } elseif ($successfulJobs > 0) {
                // Partial success
                $lead->update(['integration_status' => 'partial']);
            } else {
                // All failed
                $lead->update(['integration_status' => 'failed']);
            }

        } catch (\Exception $e) {
            Log::error('Failed to update lead status', [
                'lead_id' => $this->leadId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get notification recipients
     */
    protected function getNotificationRecipients(): array
    {
        // This should be configured based on your requirements
        return ['admin@example.com', 'notifications@example.com'];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('SendLeadToMultipleIntegrationsJob permanently failed', [
            'lead_id' => $this->leadId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Send critical failure notification
        $this->sendNotification('critical_failure', [
            'lead_id' => $this->leadId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
