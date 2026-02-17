<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationResult;

class UonTravelIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'uon_travel';
    }

    protected function performSend(array $data, array $credentials): array
    {
        // TODO: Implement UonTravel integration
        return [
            'success' => false,
            'message' => 'UonTravel integration not implemented yet'
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
            'message' => 'UonTravel integration not implemented yet'
        ];
    }

    public function getRequiredFields(): array
    {
        return ['api_key', 'project_id'];
    }
}
