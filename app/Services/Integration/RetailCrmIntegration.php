<?php

namespace App\Services\Integration;

use App\Interfaces\HttpServiceInterface;
use App\Services\FieldMapperService;
use Psr\Log\LoggerInterface;

class RetailCrmIntegration extends BaseIntegration
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
        return 'retailcrm';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $apiKey = $credentials['api_key'];
            $site = $credentials['site'] ?? 'default';
            $uri = $this->baseUrl . '/api/v5/orders/create';
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];

            $requestData = [
                'apiKey' => $apiKey,
                'site' => $site,
                'order' => json_encode($data)
            ];

            $response = $this->httpService->post($uri, $requestData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);

            if ($response->getStatusCode() === 200 && isset($responseData['id'])) {
                return [
                    'success' => true,
                    'external_id' => (string) $responseData['id'],
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['errorMsg'] ?? 'Failed to create order',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка отправки заказа в RetailCRM: {$e->getMessage()}", [
                'order_data' => $data,
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
        try {
            $apiKey = $credentials['api_key'];
            $site = $credentials['site'] ?? 'default';
            $externalId = $credentials['external_id'] ?? $data['externalId'] ?? null;

            if (!$externalId) {
                return [
                    'success' => false,
                    'message' => 'External ID is required for update operation'
                ];
            }

            $uri = $this->baseUrl . '/api/v5/orders/edit';
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];

            $requestData = [
                'apiKey' => $apiKey,
                'site' => $site,
                'by' => 'externalId',
                'order' => json_encode(array_merge($data, ['externalId' => $externalId]))
            ];

            $response = $this->httpService->post($uri, $requestData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);

            if ($response->getStatusCode() === 200 && isset($responseData['id'])) {
                return [
                    'success' => true,
                    'external_id' => (string) $responseData['id'],
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['errorMsg'] ?? 'Failed to update order',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка обновления заказа в RetailCRM: {$e->getMessage()}", [
                'external_id' => $externalId ?? 'unknown',
                'order_data' => $data,
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
            $apiKey = $credentials['api_key'];
            $site = $credentials['site'] ?? 'default';
            $uri = $this->baseUrl . '/api/v5/users';
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];

            $requestData = [
                'apiKey' => $apiKey,
                'site' => $site
            ];

            $response = $this->httpService->get($uri, $requestData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);

            if ($response->getStatusCode() === 200 && !isset($responseData['errorMsg'])) {
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
            $this->logger->error("Ошибка тестирования соединения с RetailCRM: {$e->getMessage()}", [
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
        return config('integrations.retailcrm.required_fields', [
            'api_key'
        ]);
    }

    /**
     * Валидация специфичных полей RetailCRM
     */
    protected function validateFieldValue(string $field, $value): bool
    {
        switch ($field) {
            case 'api_key':
                return !empty($value) && is_string($value);
            case 'site':
                return empty($value) || is_string($value);
            case 'external_id':
                return is_null($value) || (is_string($value) && !empty($value));
            case 'orderType':
                return empty($value) || in_array($value, ['fizik', 'urik']);
            case 'status':
                return empty($value) || is_string($value);
            default:
                return true;
        }
    }
}
