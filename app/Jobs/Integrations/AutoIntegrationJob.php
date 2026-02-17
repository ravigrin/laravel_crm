<?php

namespace App\Jobs\Integrations;

use App\Models\Integration\EntityCredentials;
use App\Models\Integration\ProjectCredentials;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AutoIntegrationJob - автоматически определяет все доступные интеграции для лида
 * и отправляет их через SendLeadToMultipleIntegrationsJob
 * 
 * Заменяет старый Notification job, используя новую архитектуру интеграций
 */
class AutoIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $leadId;
    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param int $leadId
     */
    public function __construct(int $leadId)
    {
        $this->leadId = $leadId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $lead = Lead::findOrFail($this->leadId);

            Log::info('AutoIntegrationJob: Starting automatic integration detection', [
                'lead_id' => $this->leadId
            ]);

            // Get all credentials for the lead
            $credentials = $this->getCredentials($lead);

            if (empty($credentials)) {
                Log::warning('AutoIntegrationJob: No credentials found for lead', [
                    'lead_id' => $this->leadId,
                    'external_entity_id' => $lead->external_entity_id,
                    'external_project_id' => $lead->external_project_id
                ]);
                return;
            }

            // Build integrations array and credentials map
            $integrations = [];
            $credentialsMap = [];

            foreach ($credentials as $credential) {
                $code = $credential['code'];
                
                // Skip if integration type is not supported by SendLeadToMultipleIntegrationsJob
                if (!$this->isSupportedIntegration($code)) {
                    Log::debug('AutoIntegrationJob: Skipping unsupported integration', [
                        'lead_id' => $this->leadId,
                        'code' => $code
                    ]);
                    continue;
                }

                // Add to integrations array if not already added
                if (!isset($credentialsMap[$code])) {
                    $integrations[] = [
                        'type' => $code,
                        'settings' => []
                    ];
                    $credentialsMap[$code] = $credential['credentials'];
                } else {
                    // If multiple credentials for same integration type, merge or use the first one
                    // For now, we'll use the first one found
                    Log::debug('AutoIntegrationJob: Multiple credentials for same integration type, using first', [
                        'lead_id' => $this->leadId,
                        'code' => $code
                    ]);
                }
            }

            if (empty($integrations)) {
                Log::warning('AutoIntegrationJob: No supported integrations found for lead', [
                    'lead_id' => $this->leadId
                ]);
                return;
            }

            Log::info('AutoIntegrationJob: Dispatching SendLeadToMultipleIntegrationsJob', [
                'lead_id' => $this->leadId,
                'integrations_count' => count($integrations),
                'integration_types' => array_column($integrations, 'type')
            ]);

            // Dispatch SendLeadToMultipleIntegrationsJob with detected integrations
            SendLeadToMultipleIntegrationsJob::dispatch($this->leadId, $integrations, $credentialsMap);

        } catch (\Exception $e) {
            Log::error('AutoIntegrationJob: Failed to process lead', [
                'lead_id' => $this->leadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get all credentials for the lead (from entity and project)
     *
     * @param Lead $lead
     * @return array
     */
    private function getCredentials(Lead $lead): array
    {
        $externalEntityId = $lead->external_entity_id;
        $externalProjectId = $lead->external_project_id;

        $allCredentials = [];

        // Get entity credentials
        if ($externalEntityId) {
            $entityCredentials = EntityCredentials::where('external_entity_id', $externalEntityId)
                ->with(['credentials' => function ($query) {
                    $query->where('enabled', true);
                }])
                ->get();

            foreach ($entityCredentials as $entityCred) {
                foreach ($entityCred->credentials as $cred) {
                    if ($cred->enabled) {
                        $allCredentials[] = [
                            'code' => $cred->code,
                            'credentials' => $cred->credentials
                        ];
                    }
                }
            }
        }

        // Get project credentials
        if ($externalProjectId) {
            $projectCredentials = ProjectCredentials::where('external_project_id', $externalProjectId)
                ->with(['credentials' => function ($query) {
                    $query->where('enabled', true);
                }])
                ->get();

            foreach ($projectCredentials as $projectCred) {
                foreach ($projectCred->credentials as $cred) {
                    if ($cred->enabled) {
                        $allCredentials[] = [
                            'code' => $cred->code,
                            'credentials' => $cred->credentials
                        ];
                    }
                }
            }
        }

        return $allCredentials;
    }

    /**
     * Check if integration type is supported by SendLeadToMultipleIntegrationsJob
     *
     * @param string $code
     * @return bool
     */
    private function isSupportedIntegration(string $code): bool
    {
        // Check if integration type is supported by SendLeadToMultipleIntegrationsJob
        $supportedTypes = ['email', 'amocrm', 'telegram', 'bitrix24', 'webhooks'];
        return in_array($code, $supportedTypes);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('AutoIntegrationJob permanently failed', [
            'lead_id' => $this->leadId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}

