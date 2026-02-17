<?php

namespace App\Http\Controllers;

use App\Http\Requests\Integration\GetConfigRequest;
use App\Http\Requests\Integration\SendLeadRequest;
use App\Http\Requests\Integration\TestConnectionRequest;
use App\Http\Requests\Integration\UpdateLeadRequest;
use App\Interfaces\IntegrationChannelInterface;
use App\Services\Integration\IntegrationFactory;
use App\Services\Integration\IntegrationManager;
use App\Models\Lead;
use App\Jobs\Integrations\SendLeadToMultipleIntegrationsJob;
use Illuminate\Http\JsonResponse;

class IntegrationController extends Controller
{
    protected IntegrationManager $integrationManager;

    public function __construct(IntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/types",
     *     summary="Получение списка доступных типов интеграций",
     *     tags={"Integrations"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'types' => $this->integrationManager->getAvailableTypes()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/integrations/test",
     *     summary="Тестирование подключения к интеграции",
     *     tags={"Integrations"},
     *     @OA\Response(response="200", description="Успешное тестирование подключения"),
     *     @OA\Response(response="500", description="Ошибка тестирования"),
     * )
     */
    public function testConnection(TestConnectionRequest $request): JsonResponse
    {
        try {
            $this->integrationManager->setIntegrationByType(
                $request->getIntegrationType()
            );
            
            $result = $this->integrationManager->testConnection(
                $request->getCredentials()
            );

            return response()->json([
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'data' => $result->getData()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/integrations/send",
     *     summary="Отправка лида в интеграцию",
     *     tags={"Integrations"},
     *     @OA\Response(response="200", description="Успешная отправка лида"),
     *     @OA\Response(response="404", description="Лид не найден"),
     *     @OA\Response(response="500", description="Ошибка отправки"),
     * )
     */
    public function sendLead(SendLeadRequest $request): JsonResponse
    {
        try {
            $lead = Lead::findOrFail($request->getLeadId());
            
            $this->integrationManager->setIntegrationByType(
                $request->getIntegrationType()
            );
            
            $result = $this->integrationManager->send(
                $lead, 
                $request->getCredentials()
            );

            return response()->json([
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'data' => [
                    'external_id' => $result->getExternalId(),
                    'integration_data' => $result->getData()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Send failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/integrations/update",
     *     summary="Обновление лида в интеграции",
     *     tags={"Integrations"},
     *     @OA\Response(response="200", description="Успешное обновление лида"),
     *     @OA\Response(response="404", description="Лид не найден"),
     *     @OA\Response(response="500", description="Ошибка обновления"),
     * )
     */
    public function updateLead(UpdateLeadRequest $request): JsonResponse
    {
        try {
            $lead = Lead::findOrFail($request->getLeadId());
            
            $this->integrationManager->setIntegrationByType(
                $request->getIntegrationType()
            );
            
            $result = $this->integrationManager->update(
                $lead, 
                $request->getCredentials()
            );

            return response()->json([
                'success' => $result->isSuccess(),
                'message' => $result->getMessage(),
                'data' => [
                    'external_id' => $result->getExternalId(),
                    'integration_data' => $result->getData()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/{type}/config",
     *     summary="Получение конфигурации интеграции",
     *     tags={"Integrations"},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="Тип интеграции",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Успешное получение конфигурации"),
     *     @OA\Response(response="500", description="Ошибка получения конфигурации"),
     * )
     */
    public function getConfig(GetConfigRequest $request): JsonResponse
    {
        $type = null;
        try {
            // Get type from route parameter directly
            $type = $request->route('type');
            
            if (empty($type)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Integration type is required'
                ], 422);
            }
            
            // Validate type is supported
            if (!$this->integrationManager->isTypeSupported($type)) {
                return response()->json([
                    'success' => false,
                    'message' => "Integration type '{$type}' is not supported"
                ], 404);
            }
            
            // Create integration instance directly via factory
            $factory = app(IntegrationFactory::class);
            $integration = $factory->create($type);

            // Get fields from config
            $fields = config("integrations.{$type}.fields", []);

            return response()->json([
                'success' => true,
                'data' => [
                    'type' => $integration->getType(),
                    'required_fields' => $integration->getRequiredFields(),
                    'fields' => $fields,
                    'supported_operations' => [
                        'send' => true,
                        'update' => !in_array($integration->getType(), ['email', 'webhooks']),
                        'test_connection' => true
                    ]
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to get integration config', [
                'type' => $type ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get config: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/integrations/send-batch",
     *     summary="Отправка лида в несколько интеграций используя batch processing",
     *     tags={"Integrations"},
     *     @OA\Response(response="200", description="Успешная отправка batch"),
     *     @OA\Response(response="422", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Ошибка отправки"),
     * )
     */
    public function sendLeadBatch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'lead_id' => 'required|integer|exists:leads,id',
                'integrations' => 'required|array|min:1',
                'integrations.*.type' => 'required|string|in:email,amocrm,telegram,bitrix24,webhooks',
                'integrations.*.settings' => 'sometimes|array',
                'credentials' => 'sometimes|array'
            ]);

            $leadId = $request->input('lead_id');
            $integrations = $request->input('integrations');
            $credentials = $request->input('credentials', []);

            // Dispatch batch job
            SendLeadToMultipleIntegrationsJob::dispatch($leadId, $integrations, $credentials);

            return response()->json([
                'success' => true,
                'message' => 'Lead batch processing started',
                'data' => [
                    'lead_id' => $leadId,
                    'integrations_count' => count($integrations),
                    'integrations' => array_column($integrations, 'type')
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch dispatch failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/{type}/form-request",
     *     summary="Получение класса FormRequest для интеграции",
     *     tags={"Integrations"},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         required=true,
     *         description="Тип интеграции",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Успешное получение FormRequest класса"),
     *     @OA\Response(response="400", description="Тип интеграции не найден"),
     * )
     */
    public function getFormRequestClass(string $type): JsonResponse
    {
        try {
            $formRequestClass = match ($type) {
                'email' => \App\Http\Requests\Integration\EmailIntegrationRequest::class,
                'amocrm' => \App\Http\Requests\Integration\AmoCrmIntegrationRequest::class,
                'telegram' => \App\Http\Requests\Integration\TelegramIntegrationRequest::class,
                'bitrix24' => \App\Http\Requests\Integration\Bitrix24IntegrationRequest::class,
                default => null
            };

            if (!$formRequestClass) {
                return response()->json([
                    'success' => false,
                    'message' => "No specific FormRequest found for type '{$type}'"
                ], 400);
            }

            $instance = new $formRequestClass();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'type' => $type,
                    'form_request_class' => $formRequestClass,
                    'validation_rules' => $instance->rules(),
                    'messages' => $instance->messages(),
                    'attributes' => $instance->attributes()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get FormRequest: ' . $e->getMessage()
            ], 500);
        }
    }
}
