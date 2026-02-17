<?php

namespace App\Services\Lead;

use App\Jobs\Integrations\AutoIntegrationJob;
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

/**
 * LeadResendService - сервис для централизации логики повторной отправки лидов в интеграции
 * 
 * Предоставляет единообразный API для отправки лидов в интеграции:
 * - Автоматическое определение всех доступных интеграций
 * - Отправка в указанные интеграции
 * - Batch обработка для множественных лидов
 */
class LeadResendService
{
    /**
     * Валидные типы интеграций
     */
    private const VALID_INTEGRATION_TYPES = [
        'email',
        'amocrm',
        'telegram',
        'bitrix24',
        'webhooks',
        'retailcrm',
        'getresponse',
        'sendpulse',
        'unisender',
        'lptracker',
        'uontravel',
    ];

    /**
     * Отправить лид в интеграции
     * 
     * @param int $leadId ID лида
     * @param array $integrationTypes Массив типов интеграций (пустой массив = все доступные)
     * @param array $credentials Дополнительные credentials для интеграций
     * @return array Результат операции
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resendLead(int $leadId, array $integrationTypes = [], array $credentials = []): array
    {
        // Проверяем существование лида
        $lead = Lead::findOrFail($leadId);

        // Валидируем типы интеграций
        $this->validateIntegrationTypes($integrationTypes);

        // Если типы интеграций не указаны, используем автоматическое определение
        if (empty($integrationTypes)) {
            return $this->resendToAllIntegrations($leadId);
        }

        // Отправляем в указанные интеграции
        return $this->resendToSpecificIntegrations($leadId, $integrationTypes, $credentials);
    }

    /**
     * Отправить несколько лидов в интеграции (batch операция)
     * 
     * @param array $leadIds Массив ID лидов
     * @param array $integrationTypes Массив типов интеграций (пустой массив = все доступные)
     * @param array $credentials Дополнительные credentials для интеграций
     * @return array Результат операции с деталями по каждому лиду
     */
    public function bulkResendLeads(array $leadIds, array $integrationTypes = [], array $credentials = []): array
    {
        $dispatchedCount = 0;
        $errors = [];

        foreach ($leadIds as $leadId) {
            try {
                $this->resendLead($leadId, $integrationTypes, $credentials);
                $dispatchedCount++;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                $errors[] = [
                    'lead_id' => $leadId,
                    'error' => 'Lead not found'
                ];
                Log::warning('Lead not found in bulk resend operation', [
                    'lead_id' => $leadId
                ]);
            } catch (\Exception $e) {
                $errors[] = [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage()
                ];
                Log::warning('Failed to resend lead in bulk operation', [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk resend completed', [
            'total_leads' => count($leadIds),
            'dispatched_count' => $dispatchedCount,
            'errors_count' => count($errors),
            'integration_types' => $integrationTypes
        ]);

        return [
            'dispatched_count' => $dispatchedCount,
            'total_count' => count($leadIds),
            'errors_count' => count($errors),
            'errors' => $errors
        ];
    }

    /**
     * Отправить лид во все доступные интеграции (автоматическое определение)
     * 
     * @param int $leadId ID лида
     * @return array Результат операции
     */
    private function resendToAllIntegrations(int $leadId): array
    {
        AutoIntegrationJob::dispatch($leadId);

        Log::info('Lead queued for resend to all integrations', [
            'lead_id' => $leadId
        ]);

        return [
            'success' => true,
            'lead_id' => $leadId,
            'method' => 'all_integrations',
            'message' => 'Lead resend queued to all configured integrations'
        ];
    }

    /**
     * Отправить лид в указанные интеграции
     * 
     * @param int $leadId ID лида
     * @param array $integrationTypes Массив типов интеграций
     * @param array $credentials Дополнительные credentials
     * @return array Результат операции
     */
    private function resendToSpecificIntegrations(int $leadId, array $integrationTypes, array $credentials): array
    {
        // Строим массив конфигураций интеграций
        $integrations = array_map(function ($type) {
            return [
                'type' => $type,
                'settings' => []
            ];
        }, $integrationTypes);

        // Отправляем batch job
        SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $credentials);

        Log::info('Lead queued for resend to specific integrations', [
            'lead_id' => $leadId,
            'integration_types' => $integrationTypes,
            'integrations_count' => count($integrationTypes)
        ]);

        return [
            'success' => true,
            'lead_id' => $leadId,
            'integration_types' => $integrationTypes,
            'integrations_count' => count($integrationTypes),
            'message' => 'Lead resend queued to specified integrations'
        ];
    }

    /**
     * Валидировать типы интеграций
     * 
     * @param array $integrationTypes Массив типов интеграций
     * @throws \InvalidArgumentException
     */
    private function validateIntegrationTypes(array $integrationTypes): void
    {
        if (empty($integrationTypes)) {
            return; // Пустой массив допустим (означает все интеграции)
        }

        $invalidTypes = array_diff($integrationTypes, self::VALID_INTEGRATION_TYPES);
        
        if (!empty($invalidTypes)) {
            throw new \InvalidArgumentException(
                'Invalid integration types: ' . implode(', ', $invalidTypes)
            );
        }
    }

    /**
     * Получить список валидных типов интеграций
     * 
     * @return array
     */
    public static function getValidIntegrationTypes(): array
    {
        return self::VALID_INTEGRATION_TYPES;
    }
}



