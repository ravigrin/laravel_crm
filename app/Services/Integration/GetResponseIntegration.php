<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationResult;
use Illuminate\Support\Facades\Http;

class GetResponseIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'getresponse';
    }

    protected function performSend(array $data, array $credentials): array
    {
        // TODO: Implement GetResponse integration
        return [
            'success' => false,
            'message' => 'GetResponse integration not implemented yet'
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
            'message' => 'GetResponse integration not implemented yet'
        ];
    }

    public function getRequiredFields(): array
    {
        return ['api_key', 'campaign_id'];
    }
}
