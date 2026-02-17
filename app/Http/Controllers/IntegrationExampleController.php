<?php

namespace App\Http\Controllers;

use App\Http\Requests\Integration\AmoCrmIntegrationRequest;
use App\Http\Requests\Integration\EmailIntegrationRequest;
use App\Http\Requests\Integration\SendLeadRequest;
use App\Http\Requests\Integration\TelegramIntegrationRequest;
use App\Http\Requests\Integration\TestConnectionRequest;
use App\Http\Requests\Integration\UpdateLeadRequest;
use App\Services\Integration\IntegrationManager;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

/**
 * Example controller showing how to use FormRequest validation
 * for integrations
 */
class IntegrationExampleController extends Controller
{
    protected IntegrationManager $integrationManager;

    public function __construct(IntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }

    /**
     * Example: Test AmoCRM connection with specific FormRequest
     */
    public function testAmoCrmConnection(AmoCrmIntegrationRequest $request): JsonResponse
    {
        try {
            $this->integrationManager->setIntegrationByType('amocrm');
            $result = $this->integrationManager->testConnection($request->getCredentials());

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
     * Example: Test Email connection with specific FormRequest
     */
    public function testEmailConnection(EmailIntegrationRequest $request): JsonResponse
    {
        try {
            $this->integrationManager->setIntegrationByType('email');
            $result = $this->integrationManager->testConnection($request->getCredentials());

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
     * Example: Test Telegram connection with specific FormRequest
     */
    public function testTelegramConnection(TelegramIntegrationRequest $request): JsonResponse
    {
        try {
            $this->integrationManager->setIntegrationByType('telegram');
            $result = $this->integrationManager->testConnection($request->getCredentials());

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
     * Example: Send lead using generic TestConnectionRequest
     * (works with any integration type)
     */
    public function sendLeadGeneric(TestConnectionRequest $request): JsonResponse
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
     * Example: Send lead with lead validation
     */
    public function sendLeadWithValidation(SendLeadRequest $request): JsonResponse
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
     * Example: Update lead with external ID validation
     */
    public function updateLeadWithValidation(UpdateLeadRequest $request): JsonResponse
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
     * Example: Bulk operations with validation
     */
    public function bulkSendLeads(SendLeadRequest $request): JsonResponse
    {
        try {
            $leadIds = $request->input('lead_ids', []);
            $type = $request->getIntegrationType();
            $credentials = $request->getCredentials();

            $this->integrationManager->setIntegrationByType($type);

            $results = [];
            $successCount = 0;

            foreach ($leadIds as $leadId) {
                $lead = Lead::find($leadId);
                if (!$lead) {
                    $results[] = [
                        'lead_id' => $leadId,
                        'success' => false,
                        'message' => 'Lead not found'
                    ];
                    continue;
                }

                $result = $this->integrationManager->send($lead, $credentials);
                $results[] = [
                    'lead_id' => $leadId,
                    'success' => $result->isSuccess(),
                    'message' => $result->getMessage(),
                    'external_id' => $result->getExternalId()
                ];

                if ($result->isSuccess()) {
                    $successCount++;
                }
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Sent {$successCount} out of " . count($leadIds) . " leads",
                'data' => [
                    'total' => count($leadIds),
                    'successful' => $successCount,
                    'failed' => count($leadIds) - $successCount,
                    'results' => $results
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk send failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example: Get validation errors for debugging
     */
    public function validateCredentials(TestConnectionRequest $request): JsonResponse
    {
        // If we reach this point, validation passed
        return response()->json([
            'success' => true,
            'message' => 'Validation passed',
            'data' => [
                'type' => $request->getIntegrationType(),
                'credentials_keys' => array_keys($request->getCredentials()),
                'has_lead_id' => $request->getLeadId() !== null
            ]
        ]);
    }
}
