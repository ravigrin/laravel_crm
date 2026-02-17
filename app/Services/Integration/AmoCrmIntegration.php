<?php

namespace App\Services\Integration;

use App\Interfaces\HttpServiceInterface;
use App\Interfaces\IntegrationResult;
use App\Models\Lead;
use App\Services\FieldMapperService;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class AmoCrmIntegration extends BaseIntegration
{
    protected LoggerInterface $logger;

    public function __construct(
        HttpServiceInterface $httpService,
        FieldMapperService $fieldMapper,
        LoggerInterface $logger,
        string $baseUrl = ''
    ) {
        parent::__construct($httpService, $fieldMapper, $baseUrl);
        $this->logger = $logger;
    }

    public function getType(): string
    {
        return 'amocrm';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $uri = $this->baseUrl . '/api/v4/leads';
            $headers = [
                'Authorization' => 'Bearer ' . ($credentials['access_token'] ?? ''),
                'Content-Type' => 'application/json',
            ];

            $response = $this->httpService->post($uri, $data, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'external_id' => $responseData['_embedded']['leads'][0]['id'] ?? null,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['errorMsg'] ?? 'Failed to send lead',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка отправки лида в AmoCRM: {$e->getMessage()}", [
                'lead_data' => $data,
                'credentials_keys' => array_keys($credentials)
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_code' => 500
            ];
        }
    }

    protected function performUpdate(array $data, array $credentials): array
    {
        $leadId = $credentials['lead_id'] ?? null;
        
        if (!$leadId) {
            return [
                'success' => false,
                'message' => 'Lead ID is required for update'
            ];
        }

        try {
            $uri = $this->baseUrl . "/api/v4/leads/{$leadId}";
            $headers = [
                'Authorization' => 'Bearer ' . ($credentials['access_token'] ?? ''),
                'Content-Type' => 'application/json',
            ];

            $response = $this->httpService->post($uri, $data, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'external_id' => $leadId,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['errorMsg'] ?? 'Failed to update lead',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка обновления лида в AmoCRM: {$e->getMessage()}", [
                'lead_id' => $leadId,
                'lead_data' => $data,
                'credentials_keys' => array_keys($credentials)
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_code' => 500
            ];
        }
    }

    protected function performTestConnection(array $credentials): array
    {
        try {
            $uri = $this->baseUrl . '/api/v4/account';
            $headers = [
                'Authorization' => 'Bearer ' . ($credentials['access_token'] ?? ''),
                'Content-Type' => 'application/json',
            ];

            $response = $this->httpService->get($uri, [], $headers, false);
            $responseData = $this->httpService->getResponseBody($response);
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['errorMsg'] ?? 'Connection test failed',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка тестирования соединения с AmoCRM: {$e->getMessage()}", [
                'credentials_keys' => array_keys($credentials)
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_code' => 500
            ];
        }
    }


    public function getRequiredFields(): array
    {
        return config('integrations.amocrm.required_fields', [
            'access_token',
            'base_url',
            'responsible_user_id'
        ]);
    }

    /**
     * Validate specific field value
     */
    protected function validateFieldValue(string $field, $value): bool
    {
        switch ($field) {
            case 'access_token':
                return is_string($value) && strlen($value) >= 10;
            case 'base_url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'responsible_user_id':
                return is_numeric($value) && $value > 0;
            case 'pipeline_id':
                return is_null($value) || (is_numeric($value) && $value > 0);
            case 'status_id':
                return is_null($value) || (is_numeric($value) && $value > 0);
            case 'price':
                return is_null($value) || (is_numeric($value) && $value >= 0);
            case 'lead_id':
                return is_null($value) || (is_numeric($value) && $value > 0);
            default:
                return true;
        }
    }
}
