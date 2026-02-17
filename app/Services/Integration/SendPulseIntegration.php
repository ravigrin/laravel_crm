<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationResult;

class SendPulseIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'sendpulse';
    }

    protected function performSend(array $data, array $credentials): array
    {
        // TODO: Implement SendPulse integration
        return [
            'success' => false,
            'message' => 'SendPulse integration not implemented yet'
        ];
    }

    protected function performUpdate(array $data, array $credentials): array
    {
        return $this->performSend($data, $credentials);
    }

    protected function performTestConnection(array $credentials): array
    {
        return [
            'success' => false,
            'message' => 'SendPulse integration not implemented yet'
        ];
    }

    public function getRequiredFields(): array
    {
        return ['client_id', 'client_secret', 'address_book_id'];
    }
}
