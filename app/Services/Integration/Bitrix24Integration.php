<?php

namespace App\Services\Integration;

use App\Interfaces\HttpServiceInterface;
use App\Services\FieldMapperService;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class Bitrix24Integration extends BaseIntegration
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
        return 'bitrix24';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $webhookUrl = $credentials['webhook_url'];
            $uri = $webhookUrl . 'crm.lead.add';
            $headers = [
                'Content-Type' => 'application/json',
            ];

            $requestData = ['fields' => $data];
            $response = $this->httpService->post($uri, $requestData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);
            
            if (isset($responseData['result'])) {
                return [
                    'success' => true,
                    'external_id' => $responseData['result'],
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['error_description'] ?? 'Failed to send lead',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка отправки лида в Bitrix24: {$e->getMessage()}", [
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
            $webhookUrl = $credentials['webhook_url'];
            $uri = $webhookUrl . 'crm.lead.update';
            $headers = [
                'Content-Type' => 'application/json',
            ];

            $requestData = [
                'id' => $leadId,
                'fields' => $data
            ];
            $response = $this->httpService->post($uri, $requestData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);
            
            if (isset($responseData['result'])) {
                return [
                    'success' => true,
                    'external_id' => $leadId,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['error_description'] ?? 'Failed to update lead',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка обновления лида в Bitrix24: {$e->getMessage()}", [
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
            $webhookUrl = $credentials['webhook_url'];
            $uri = $webhookUrl . 'crm.lead.fields';
            $headers = [
                'Content-Type' => 'application/json',
            ];

            $response = $this->httpService->get($uri, [], $headers, false);
            $responseData = $this->httpService->getResponseBody($response);
            
            if (isset($responseData['result'])) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['error_description'] ?? 'Connection test failed',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка тестирования соединения с Bitrix24: {$e->getMessage()}", [
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
        return config('integrations.bitrix24.required_fields', [
            'webhook_url',
            'user_id'
        ]);
    }

    /**
     * Validate specific field value
     */
    protected function validateFieldValue(string $field, $value): bool
    {
        switch ($field) {
            case 'webhook_url':
                return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'user_id':
                return is_numeric($value) && $value > 0;
            case 'lead_id':
                return is_null($value) || (is_numeric($value) && $value > 0);
            default:
                return true;
        }
    }
}