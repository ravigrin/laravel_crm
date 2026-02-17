<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationResult;

class LpTrackerIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'lptracker';
    }

    protected function performSend(array $data, array $credentials): array
    {
        // TODO: Implement LpTracker integration
        return [
            'success' => false,
            'message' => 'LpTracker integration not implemented yet'
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
            'message' => 'LpTracker integration not implemented yet'
        ];
    }

    public function getRequiredFields(): array
    {
        return ['api_key', 'project_id'];
    }
}
