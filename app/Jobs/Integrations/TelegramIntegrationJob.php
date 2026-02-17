<?php

namespace App\Jobs\Integrations;

use App\Interfaces\IntegrationChannelInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class TelegramIntegrationJob extends BaseIntegrationJob
{
    protected function getIntegrationType(): string
    {
        return 'telegram';
    }

    protected function validateCredentials(): bool
    {
        return isset($this->credentials['bot_token']) && 
               isset($this->credentials['chat_id']);
    }

    protected function executeIntegration(IntegrationChannelInterface $integration, Lead $lead)
    {
        // Telegram specific logic
        Log::info('Executing Telegram integration', [
            'lead_id' => $this->leadId,
            'chat_id' => $this->credentials['chat_id'] ?? 'unknown'
        ]);

        return $integration->send($lead, $this->credentials);
    }

    protected function handleSuccess($result, Lead $lead): void
    {
        parent::handleSuccess($result, $lead);

        // Telegram specific success handling
        Log::info('Telegram message sent successfully', [
            'lead_id' => $this->leadId,
            'message_id' => $result->getExternalId()
        ]);
    }
}

















