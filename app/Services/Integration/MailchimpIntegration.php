<?php

namespace App\Services\Integration;

use App\Interfaces\HttpServiceInterface;
use App\Services\FieldMapperService;
use Psr\Log\LoggerInterface;

class MailchimpIntegration extends BaseIntegration
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
        return 'mailchimp';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $apiKey = $credentials['api_key'];
            $serverPrefix = $credentials['server_prefix'];
            $listId = $credentials['list_id'];
            
            $uri = "https://{$serverPrefix}.api.mailchimp.com/3.0/lists/{$listId}/members";
            $headers = [
                'Authorization' => 'apikey ' . $apiKey,
                'Content-Type' => 'application/json',
            ];

            // Подготавливаем данные для Mailchimp
            $memberData = $this->prepareMemberData($data, $credentials);

            $response = $this->httpService->post($uri, $memberData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'external_id' => $responseData['id'] ?? null,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['detail'] ?? 'Failed to add member',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка добавления контакта в Mailchimp: {$e->getMessage()}", [
                'member_data' => $data,
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
            $serverPrefix = $credentials['server_prefix'];
            $listId = $credentials['list_id'];
            $memberId = $credentials['member_id'] ?? $data['member_id'] ?? null;

            if (!$memberId) {
                return [
                    'success' => false,
                    'message' => 'Member ID is required for update operation'
                ];
            }

            $uri = "https://{$serverPrefix}.api.mailchimp.com/3.0/lists/{$listId}/members/{$memberId}";
            $headers = [
                'Authorization' => 'apikey ' . $apiKey,
                'Content-Type' => 'application/json',
            ];

            // Подготавливаем данные для обновления
            $memberData = $this->prepareMemberData($data, $credentials);

            $response = $this->httpService->post($uri, $memberData, $headers, false);
            $responseData = $this->httpService->getResponseBody($response);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'external_id' => $memberId,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['detail'] ?? 'Failed to update member',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка обновления контакта в Mailchimp: {$e->getMessage()}", [
                'member_id' => $memberId ?? 'unknown',
                'member_data' => $data,
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
            $serverPrefix = $credentials['server_prefix'];
            $uri = "https://{$serverPrefix}.api.mailchimp.com/3.0/ping";
            $headers = [
                'Authorization' => 'apikey ' . $apiKey,
                'Content-Type' => 'application/json',
            ];

            $response = $this->httpService->get($uri, [], $headers, false);
            $responseData = $this->httpService->getResponseBody($response);

            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['detail'] ?? 'Connection test failed',
                    'http_code' => $response->getStatusCode(),
                    'data' => $responseData
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error("Ошибка тестирования соединения с Mailchimp: {$e->getMessage()}", [
                'credentials_keys' => array_keys($credentials)
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'http_code' => 500
            ];
        }
    }

    /**
     * Подготовка данных контакта для Mailchimp API
     */
    protected function prepareMemberData(array $data, array $credentials): array
    {
        $memberData = [
            'email_address' => $data['email'] ?? '',
            'status' => $data['status'] ?? 'subscribed',
            'merge_fields' => [
                'FNAME' => $data['first_name'] ?? $data['name'] ?? '',
                'LNAME' => $data['last_name'] ?? '',
                'PHONE' => $data['phone'] ?? '',
            ],
        ];

        // Добавляем теги если есть
        if (!empty($data['tags']) && is_array($data['tags'])) {
            $memberData['tags'] = $data['tags'];
        }

        // Добавляем язык если есть
        if (!empty($data['language'])) {
            $memberData['language'] = $data['language'];
        }

        // Добавляем дополнительные merge fields
        $mergeFields = $data['merge_fields'] ?? [];
        if (!empty($mergeFields)) {
            $memberData['merge_fields'] = array_merge($memberData['merge_fields'], $mergeFields);
        }

        return $memberData;
    }

    public function getRequiredFields(): array
    {
        return config('integrations.mailchimp.required_fields', [
            'api_key',
            'server_prefix',
            'list_id'
        ]);
    }

    /**
     * Валидация специфичных полей Mailchimp
     */
    protected function validateFieldValue(string $field, $value): bool
    {
        switch ($field) {
            case 'api_key':
                return !empty($value) && is_string($value);
            case 'server_prefix':
                return !empty($value) && is_string($value) && preg_match('/^[a-z0-9]+$/', $value);
            case 'list_id':
                return !empty($value) && is_string($value);
            case 'member_id':
                return is_null($value) || (is_string($value) && !empty($value));
            case 'status':
                return empty($value) || in_array($value, ['subscribed', 'unsubscribed', 'cleaned', 'pending']);
            case 'language':
                return empty($value) || (is_string($value) && strlen($value) === 2);
            default:
                return true;
        }
    }
}
















