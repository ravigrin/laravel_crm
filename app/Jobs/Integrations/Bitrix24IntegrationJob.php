<?php

namespace App\Jobs\Integrations;

use App\Interfaces\IntegrationChannelInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class Bitrix24IntegrationJob extends BaseIntegrationJob
{
    protected function getIntegrationType(): string
    {
        return 'bitrix24';
    }

    protected function validateCredentials(): bool
    {
        return isset($this->credentials['domain']) && 
               isset($this->credentials['access_token']);
    }

    protected function executeIntegration(IntegrationChannelInterface $integration, Lead $lead)
    {
        // Bitrix24 specific logic
        Log::info('Executing Bitrix24 integration', [
            'lead_id' => $this->leadId,
            'domain' => $this->credentials['domain'] ?? 'unknown'
        ]);

        return $integration->send($lead, $this->credentials);
    }

    protected function handleSuccess($result, Lead $lead): void
    {
        parent::handleSuccess($result, $lead);

        // Bitrix24 specific success handling
        if ($result->getExternalId()) {
            Log::info('Bitrix24 lead created successfully', [
                'lead_id' => $this->leadId,
                'bitrix24_lead_id' => $result->getExternalId()
            ]);
        }
    }
}

















