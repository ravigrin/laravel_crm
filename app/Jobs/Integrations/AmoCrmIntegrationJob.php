<?php

namespace App\Jobs\Integrations;

use App\Interfaces\IntegrationChannelInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class AmoCrmIntegrationJob extends BaseIntegrationJob
{
    protected function getIntegrationType(): string
    {
        return 'amocrm';
    }

    protected function validateCredentials(): bool
    {
        return isset($this->credentials['subdomain']) && 
               isset($this->credentials['client_id']) && 
               isset($this->credentials['client_secret']) && 
               isset($this->credentials['access_token']);
    }

    protected function executeIntegration(IntegrationChannelInterface $integration, Lead $lead)
    {
        // AmoCrm specific logic
        Log::info('Executing AmoCrm integration', [
            'lead_id' => $this->leadId,
            'subdomain' => $this->credentials['subdomain'] ?? 'unknown'
        ]);

        return $integration->send($lead, $this->credentials);
    }

    protected function handleSuccess($result, Lead $lead): void
    {
        parent::handleSuccess($result, $lead);

        // AmoCrm specific success handling
        if ($result->getExternalId()) {
            Log::info('AmoCrm lead created successfully', [
                'lead_id' => $this->leadId,
                'amocrm_lead_id' => $result->getExternalId()
            ]);
        }
    }
}

















