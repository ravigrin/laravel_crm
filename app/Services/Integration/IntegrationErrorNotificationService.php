<?php

namespace App\Services\Integration;

use App\Jobs\NotifyByEmail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class IntegrationErrorNotificationService
{
    /**
     * Send email notification to quiz owner about integration error.
     * 
     * @param Lead $lead
     * @param string $integrationType
     * @param string $errorMessage
     * @param array $errorData
     * @return void
     */
    public function notifyQuizOwnerAboutError(
        Lead $lead,
        string $integrationType,
        string $errorMessage,
        array $errorData = []
    ): void {
        try {
            // Get quiz owner email from lead's user_id
            if (!$lead->user_id) {
                Log::warning('Cannot send integration error notification: lead has no user_id', [
                    'lead_id' => $lead->id,
                    'integration_type' => $integrationType
                ]);
                return;
            }

            $user = User::find($lead->user_id);
            if (!$user || !$user->email) {
                Log::warning('Cannot send integration error notification: user not found or has no email', [
                    'lead_id' => $lead->id,
                    'user_id' => $lead->user_id,
                    'integration_type' => $integrationType
                ]);
                return;
            }

            // Build email body
            $body = $this->buildEmailBody($lead, $integrationType, $errorMessage, $errorData);

            // Dispatch email notification job
            NotifyByEmail::dispatch(
                $user->email,
                $body,
                'integration_error' // Template code - should be defined in email templates
            );

            Log::info('Integration error notification sent to quiz owner', [
                'lead_id' => $lead->id,
                'quiz_id' => $lead->quiz_id,
                'user_id' => $lead->user_id,
                'user_email' => $user->email,
                'integration_type' => $integrationType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send integration error notification', [
                'lead_id' => $lead->id,
                'integration_type' => $integrationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Build email body for integration error notification.
     * 
     * @param Lead $lead
     * @param string $integrationType
     * @param string $errorMessage
     * @param array $errorData
     * @return array
     */
    private function buildEmailBody(
        Lead $lead,
        string $integrationType,
        string $errorMessage,
        array $errorData = []
    ): array {
        return [
            'lead_id' => $lead->id,
            'lead_name' => $lead->name ?? 'Не указано',
            'lead_email' => $lead->email ?? 'Не указано',
            'lead_phone' => $lead->phone ?? 'Не указано',
            'quiz_id' => $lead->quiz_id,
            'integration_type' => $integrationType,
            'error_message' => $errorMessage,
            'error_data' => $errorData,
            'created_at' => $lead->created_at?->format('Y-m-d H:i:s'),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
    }
}



