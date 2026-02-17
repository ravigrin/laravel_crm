<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationResult;
use Illuminate\Support\Facades\Http;

class WebhooksIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'webhooks';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $response = Http::post($credentials['url'], $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response->body(),
                    'http_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function performUpdate(array $data, array $credentials): array
    {
        return $this->performSend($data, $credentials);
    }

    protected function performTestConnection(array $credentials): array
    {
        try {
            $response = Http::post($credentials['url'], [
                'test' => true,
                'timestamp' => now()->toISOString()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response->body(),
                    'http_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getRequiredFields(): array
    {
        return ['url'];
    }
}
