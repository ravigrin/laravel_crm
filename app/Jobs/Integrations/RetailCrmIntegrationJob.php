<?php

namespace App\Jobs\Integrations;

use App\Services\Integration\IntegrationManager;
use Illuminate\Support\Facades\Log;

class RetailCrmIntegrationJob extends BaseIntegrationJob
{
    /**
     * Get the integration type for this job
     */
    protected function getIntegrationType(): string
    {
        return 'retailcrm';
    }

    /**
     * Get the queue name for this job
     */
    protected function getQueueName(): string
    {
        return 'retailcrm';
    }

    /**
     * Execute the job.
     */
    public function handle(IntegrationManager $integrationManager): void
    {
        try {
            $lead = \App\Models\Lead::findOrFail($this->leadId);
            
            Log::info('Starting RetailCRM integration job', [
                'lead_id' => $this->leadId,
                'integration_type' => $this->getIntegrationType(),
                'credentials_keys' => array_keys($this->credentials)
            ]);

            // Устанавливаем тип интеграции
            $integrationManager->setIntegrationByType($this->getIntegrationType());
            
            // Выполняем отправку
            $result = $integrationManager->send($lead, $this->credentials);

            if ($result->isSuccess()) {
                Log::info('RetailCRM integration job completed successfully', [
                    'lead_id' => $this->leadId,
                    'external_id' => $result->getExternalId(),
                    'message' => $result->getMessage()
                ]);

                // Обновляем статус лида, если нужно
                $this->updateLeadIntegrationStatus($lead, 'success', $result->getExternalId());
            } else {
                Log::error('RetailCRM integration job failed', [
                    'lead_id' => $this->leadId,
                    'error' => $result->getMessage(),
                    'http_code' => $result->getHttpCode(),
                    'data' => $result->getData()
                ]);

                // Обновляем статус лида на ошибку
                $this->updateLeadIntegrationStatus($lead, 'error', null, $result->getMessage());
                
                // Если это не последняя попытка, выбрасываем исключение для повтора
                if ($this->attempts() < $this->tries) {
                    throw new \Exception('RetailCRM integration failed: ' . $result->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error('RetailCRM integration job exception', [
                'lead_id' => $this->leadId,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Если это последняя попытка, обновляем статус на ошибку
            if ($this->attempts() >= $this->tries) {
                $lead = \App\Models\Lead::find($this->leadId);
                if ($lead) {
                    $this->updateLeadIntegrationStatus($lead, 'error', null, $e->getMessage());
                }
            }

            // Выбрасываем исключение для повтора
            throw $e;
        }
    }

    /**
     * Update lead integration status
     */
    protected function updateLeadIntegrationStatus(\App\Models\Lead $lead, string $status, ?string $externalId = null, ?string $errorMessage = null): void
    {
        try {
            $lead->update([
                'integration_status' => $status,
                'external_id' => $externalId,
                'integration_error' => $errorMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update lead integration status', [
                'lead_id' => $lead->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RetailCRM integration job permanently failed', [
            'lead_id' => $this->leadId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Обновляем статус лида на ошибку
        $lead = \App\Models\Lead::find($this->leadId);
        if ($lead) {
            $this->updateLeadIntegrationStatus($lead, 'error', null, $exception->getMessage());
        }
    }
}
















