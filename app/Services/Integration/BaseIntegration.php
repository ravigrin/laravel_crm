<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationChannelInterface;
use App\Interfaces\IntegrationResult;
use App\Interfaces\HttpServiceInterface;
use App\Services\FieldMapperService;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

abstract class BaseIntegration implements IntegrationChannelInterface
{
    protected HttpServiceInterface $httpService;
    protected FieldMapperService $fieldMapper;
    protected string $baseUrl;

    public function __construct(
        HttpServiceInterface $httpService,
        FieldMapperService $fieldMapper,
        string $baseUrl = ''
    ) {
        $this->httpService = $httpService;
        $this->fieldMapper = $fieldMapper;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Send lead to integration
     */
    public function send(Lead $lead, array $credentials): IntegrationResult
    {
        try {
            if (!$this->validateCredentials($credentials)) {
                return IntegrationResult::failure('Invalid credentials provided');
            }

            $mappedData = $this->mapLeadData($lead, $credentials);
            $result = $this->performSend($mappedData, $credentials);

            if ($result['success']) {
                Log::info('Integration send successful', [
                    'integration' => $this->getType(),
                    'lead_id' => $lead->id,
                    'external_id' => $result['external_id'] ?? null
                ]);

                return IntegrationResult::success(
                    'Lead sent successfully',
                    $result['external_id'] ?? null,
                    $result['data'] ?? []
                );
            } else {
                Log::error('Integration send failed', [
                    'integration' => $this->getType(),
                    'lead_id' => $lead->id,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);

                return IntegrationResult::failure(
                    $result['message'] ?? 'Failed to send lead',
                    $result['http_code'] ?? null,
                    $result['data'] ?? []
                );
            }

        } catch (\Exception $e) {
            Log::error('Integration send exception', [
                'integration' => $this->getType(),
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return IntegrationResult::failure('Integration error: ' . $e->getMessage());
        }
    }

    /**
     * Update lead in integration
     */
    public function update(Lead $lead, array $credentials): IntegrationResult
    {
        try {
            if (!$this->validateCredentials($credentials)) {
                return IntegrationResult::failure('Invalid credentials provided');
            }

            $mappedData = $this->mapLeadData($lead, $credentials);
            $result = $this->performUpdate($mappedData, $credentials);

            if ($result['success']) {
                Log::info('Integration update successful', [
                    'integration' => $this->getType(),
                    'lead_id' => $lead->id,
                    'external_id' => $result['external_id'] ?? null
                ]);

                return IntegrationResult::success(
                    'Lead updated successfully',
                    $result['external_id'] ?? null,
                    $result['data'] ?? []
                );
            } else {
                Log::error('Integration update failed', [
                    'integration' => $this->getType(),
                    'lead_id' => $lead->id,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);

                return IntegrationResult::failure(
                    $result['message'] ?? 'Failed to update lead',
                    $result['http_code'] ?? null,
                    $result['data'] ?? []
                );
            }

        } catch (\Exception $e) {
            Log::error('Integration update exception', [
                'integration' => $this->getType(),
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return IntegrationResult::failure('Integration error: ' . $e->getMessage());
        }
    }

    /**
     * Test connection with credentials
     */
    public function testConnection(array $credentials): IntegrationResult
    {
        try {
            if (!$this->validateCredentials($credentials)) {
                return IntegrationResult::failure('Invalid credentials provided');
            }

            $result = $this->performTestConnection($credentials);

            if ($result['success']) {
                return IntegrationResult::success(
                    $result['message'] ?? 'Connection test successful',
                    null,
                    $result['data'] ?? []
                );
            } else {
                return IntegrationResult::failure(
                    $result['message'] ?? 'Connection test failed',
                    $result['http_code'] ?? null,
                    $result['data'] ?? []
                );
            }

        } catch (\Exception $e) {
            return IntegrationResult::failure('Connection test error: ' . $e->getMessage());
        }
    }

    /**
     * Map lead data using field mapping
     */
    protected function mapLeadData(Lead $lead, array $credentials): array
    {
        return $this->fieldMapper->buildFromMapping(
            $lead,
            $credentials,
            $this->getType(),
            'fields'
        );
    }

    /**
     * Perform the actual send operation
     * Must be implemented by concrete classes
     */
    abstract protected function performSend(array $data, array $credentials): array;

    /**
     * Perform the actual update operation
     * Must be implemented by concrete classes
     */
    abstract protected function performUpdate(array $data, array $credentials): array;

    /**
     * Perform connection test
     * Must be implemented by concrete classes
     */
    abstract protected function performTestConnection(array $credentials): array;

    /**
     * Get required fields for credentials
     */
    public function getRequiredFields(): array
    {
        return config("integrations.{$this->getType()}.required_fields", []);
    }

    /**
     * Validate credentials structure
     */
    public function validateCredentials(array $credentials): bool
    {
        $requiredFields = $this->getRequiredFields();
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $credentials)) {
                return false;
            }
            
            // Additional validation for specific field types
            if (!$this->validateFieldValue($field, $credentials[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate specific field value
     */
    protected function validateFieldValue(string $field, $value): bool
    {
        // Override in child classes for specific validation
        return true;
    }
}
