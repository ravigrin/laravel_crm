<?php

namespace App\Jobs\Integrations;

use App\Interfaces\IntegrationChannelInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class EmailIntegrationJob extends BaseIntegrationJob
{
    protected function getIntegrationType(): string
    {
        return 'email';
    }

    protected function validateCredentials(): bool
    {
        return isset($this->credentials['emails']) && 
               is_array($this->credentials['emails']) && 
               !empty($this->credentials['emails']);
    }

    protected function executeIntegration(IntegrationChannelInterface $integration, Lead $lead)
    {
        // Email specific logic
        Log::info('Executing Email integration', [
            'lead_id' => $this->leadId,
            'emails' => $this->credentials['emails'] ?? []
        ]);

        return $integration->send($lead, $this->credentials);
    }

    protected function handleSuccess($result, Lead $lead): void
    {
        parent::handleSuccess($result, $lead);

        // Email specific success handling
        Log::info('Email sent successfully', [
            'lead_id' => $this->leadId,
            'recipients' => $this->credentials['emails'] ?? []
        ]);
    }

}
