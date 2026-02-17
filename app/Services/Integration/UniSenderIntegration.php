<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationResult;

class UniSenderIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'unisender';
    }

    protected function performSend(array $data, array $credentials): array
    {
        // TODO: Implement UniSender integration
        return [
            'success' => false,
            'message' => 'UniSender integration not implemented yet'
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
            'message' => 'UniSender integration not implemented yet'
        ];
    }

    public function getRequiredFields(): array
    {
        return ['api_key', 'list_id'];
    }
}
