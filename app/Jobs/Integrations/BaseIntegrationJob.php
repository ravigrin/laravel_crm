<?php

namespace App\Jobs\Integrations;

use App\Interfaces\IntegrationChannelInterface;
use App\Models\Lead;
use App\Services\Integration\IntegrationErrorNotificationService;
use App\Services\Integration\IntegrationManager;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $leadId;
    public array $credentials;
    public array $settings;
    /**
     * Increase retry attempts to 5 for reliability
     */
    public int $tries = 5;
    public int $timeout = 120;
    /**
     * Exponential backoff strategy: 10s, 30s, 1m, 5m, 1h
     * This prevents overwhelming external APIs on repeated failures
     */
    public array $backoff = [10, 30, 60, 300, 3600];

    /**
     * Create a new job instance.
     */
    public function __construct(int $leadId, array $credentials = [], array $settings = [])
    {
        $this->leadId = $leadId;
        $this->credentials = $credentials;
        $this->settings = $settings;
        
        // Set queue based on integration type
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job.
     */
    public function handle(IntegrationManager $integrationManager): void
    {
        try {
            $lead = Lead::findOrFail($this->leadId);
            
            Log::info('Starting integration job', [
                'lead_id' => $this->leadId,
                'integration_type' => $this->getIntegrationType(),
                'queue' => $this->getQueueName()
            ]);

            // Set integration by type
            $integrationManager->setIntegrationByType($this->getIntegrationType());
            
            // Get integration instance
            $integration = $integrationManager->getIntegration();
            
            if (!$integration) {
                throw new \Exception("Integration not found for type: {$this->getIntegrationType()}");
            }

            // Validate credentials
            if (!$this->validateCredentials()) {
                throw new \Exception("Invalid credentials for integration: {$this->getIntegrationType()}");
            }

            // Execute integration
            $result = $this->executeIntegration($integration, $lead);

            if ($result->isSuccess()) {
                Log::info('Integration job completed successfully', [
                    'lead_id' => $this->leadId,
                    'integration_type' => $this->getIntegrationType(),
                    'external_id' => $result->getExternalId()
                ]);

                $this->handleSuccess($result, $lead);
            } else {
                Log::error('Integration job failed', [
                    'lead_id' => $this->leadId,
                    'integration_type' => $this->getIntegrationType(),
                    'error' => $result->getMessage()
                ]);

                $this->handleFailure($result, $lead);
            }

        } catch (\Exception $e) {
            Log::error('Integration job exception', [
                'lead_id' => $this->leadId,
                'integration_type' => $this->getIntegrationType(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->handleException($e);
            throw $e;
        }
    }

    /**
     * Execute the integration logic
     */
    protected function executeIntegration(IntegrationChannelInterface $integration, Lead $lead)
    {
        // Default implementation - can be overridden in specific jobs
        return $integration->send($lead, $this->credentials);
    }

    /**
     * Validate credentials for the integration
     */
    protected function validateCredentials(): bool
    {
        // Default validation - can be overridden in specific jobs
        return !empty($this->credentials);
    }

    /**
     * Handle successful integration
     */
    protected function handleSuccess($result, Lead $lead): void
    {
        // Update lead with external ID if available
        if ($result->getExternalId()) {
            $lead->update([
                'external_id' => $result->getExternalId(),
                'integration_data' => $result->getData()
            ]);
        }

        // Log integration success
        $this->logIntegrationResult('success', $result, $lead);
    }

    /**
     * Handle integration failure
     */
    protected function handleFailure($result, Lead $lead): void
    {
        // Log integration failure
        $this->logIntegrationResult('failure', $result, $lead);

        // Send notification to quiz owner
        $this->notifyQuizOwnerAboutError(
            $lead,
            $result->getMessage() ?? 'Integration failed',
            $result->getData() ?? []
        );
    }

    /**
     * Handle job exception
     */
    protected function handleException(\Exception $e): void
    {
        // Log exception
        Log::error('Integration job exception', [
            'lead_id' => $this->leadId,
            'integration_type' => $this->getIntegrationType(),
            'error' => $e->getMessage()
        ]);

        // Send notification to quiz owner
        try {
            $lead = Lead::find($this->leadId);
            if ($lead) {
                $this->notifyQuizOwnerAboutError($lead, $e->getMessage());
            }
        } catch (\Exception $notificationException) {
            Log::error('Failed to send error notification after exception', [
                'lead_id' => $this->leadId,
                'error' => $notificationException->getMessage()
            ]);
        }
    }

    /**
     * Notify quiz owner about integration error.
     * 
     * @param Lead $lead
     * @param string $errorMessage
     * @param array $errorData
     * @return void
     */
    protected function notifyQuizOwnerAboutError(Lead $lead, string $errorMessage, array $errorData = []): void
    {
        try {
            $notificationService = app(IntegrationErrorNotificationService::class);
            $notificationService->notifyQuizOwnerAboutError(
                $lead,
                $this->getIntegrationType(),
                $errorMessage,
                $errorData
            );
        } catch (\Exception $e) {
            Log::error('Failed to notify quiz owner about integration error', [
                'lead_id' => $lead->id,
                'integration_type' => $this->getIntegrationType(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log integration result
     */
    protected function logIntegrationResult(string $status, $result, Lead $lead): void
    {
        Log::info("Integration {$status}", [
            'lead_id' => $this->leadId,
            'integration_type' => $this->getIntegrationType(),
            'status' => $status,
            'external_id' => $result->getExternalId(),
            'message' => $result->getMessage(),
            'data' => $result->getData()
        ]);
    }

    /**
     * Get integration type - must be implemented by child classes
     */
    abstract protected function getIntegrationType(): string;

    /**
     * Get queue name for this integration
     */
    protected function getQueueName(): string
    {
        return "integration-{$this->getIntegrationType()}";
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Integration job permanently failed', [
            'lead_id' => $this->leadId,
            'integration_type' => $this->getIntegrationType(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update lead status if needed and notify quiz owner
        try {
            $lead = Lead::findOrFail($this->leadId);
            $lead->update(['integration_status' => 'failed']);

            // Send notification to quiz owner about permanent failure
            $this->notifyQuizOwnerAboutError(
                $lead,
                "Integration permanently failed after {$this->attempts()} attempts: " . $exception->getMessage(),
                ['attempts' => $this->attempts(), 'exception' => get_class($exception)]
            );
        } catch (\Exception $e) {
            Log::error('Failed to update lead status after job failure', [
                'lead_id' => $this->leadId,
                'error' => $e->getMessage()
            ]);
        }
    }
}











