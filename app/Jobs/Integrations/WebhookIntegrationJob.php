<?php

namespace App\Jobs\Integrations;

use App\Interfaces\IntegrationChannelInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class WebhookIntegrationJob extends BaseIntegrationJob
{
    protected function getIntegrationType(): string
    {
        return 'webhooks';
    }

    protected function validateCredentials(): bool
    {
        return isset($this->credentials['url']) && 
               filter_var($this->credentials['url'], FILTER_VALIDATE_URL);
    }

    protected function executeIntegration(IntegrationChannelInterface $integration, Lead $lead)
    {
        // Webhook specific logic
        Log::info('Executing Webhook integration', [
            'lead_id' => $this->leadId,
            'url' => $this->credentials['url'] ?? 'unknown'
        ]);

        return $integration->send($lead, $this->credentials);
    }

    protected function handleSuccess($result, Lead $lead): void
    {
        parent::handleSuccess($result, $lead);

        // Webhook specific success handling
        Log::info('Webhook sent successfully', [
            'lead_id' => $this->leadId,
            'response_code' => $result->getData()['response_code'] ?? 'unknown'
        ]);
    }
}

















