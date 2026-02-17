<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationChannelInterface;
use App\Interfaces\IntegrationResult;
use App\Interfaces\HttpServiceInterface;
use App\Interfaces\LocaleServiceInterface;
use App\Interfaces\MailServiceInterface;
use App\Models\Lead;
use App\Services\FieldMapperService;

/**
 * Email integration service
 */
class EmailIntegration extends BaseIntegration
{
    protected MailServiceInterface $mailService;
    protected LocaleServiceInterface $localeService;

    public function __construct(
        HttpServiceInterface $httpService,
        FieldMapperService $fieldMapper,
        MailServiceInterface $mailService,
        LocaleServiceInterface $localeService,
        string $baseUrl = ''
    ) {
        parent::__construct($httpService, $fieldMapper, $baseUrl);
        $this->mailService = $mailService;
        $this->localeService = $localeService;
    }

    public function getType(): string
    {
        return 'email';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $template = $this->localeService->getEmailTemplate('new_lead');
            if (!$template) {
                return [
                    'success' => false,
                    'message' => 'Email template not found'
                ];
            }

            $templateData = $this->buildTemplateModel($data);
            $emails = is_array($credentials['emails']) ? $credentials['emails'] : [$credentials['emails']];

            $successCount = 0;
            $results = [];

            foreach ($emails as $email) {
                $result = $this->mailService->send($email, $template->template_id, $templateData);
                $results[] = [
                    'email' => $email,
                    'success' => $result
                ];
                
                if ($result) {
                    $successCount++;
                }
            }

            return [
                'success' => $successCount > 0,
                'message' => $successCount > 0 ? "Sent to {$successCount} emails" : "Failed to send to any email",
                'data' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function performUpdate(array $data, array $credentials): array
    {
        // Email doesn't support updates, so we'll send a new email
        return $this->performSend($data, $credentials);
    }

    protected function performTestConnection(array $credentials): array
    {
        try {
            $emails = $credentials['emails'] ?? [];
            if (empty($emails)) {
                return [
                    'success' => false,
                    'message' => 'No emails configured'
                ];
            }

            // Test with a simple validation
            $validEmails = array_filter($emails, function($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            });

            return [
                'success' => count($validEmails) > 0,
                'message' => count($validEmails) > 0 ? 'Valid emails found' : 'No valid emails found',
                'data' => [
                    'valid_emails' => count($validEmails),
                    'total_emails' => count($emails)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function buildTemplateModel(array $data): array
    {
        return [
            'lead_name' => $data['name'] ?? 'Unknown',
            'lead_email' => $data['email'] ?? '',
            'lead_phone' => $data['phone'] ?? '',
            'lead_data' => $data['data'] ?? [],
            'utm_source' => $data['utm_source'] ?? '',
            'utm_medium' => $data['utm_medium'] ?? '',
            'utm_campaign' => $data['utm_campaign'] ?? '',
            'created_at' => $data['created_at'] ?? now()->format('Y-m-d H:i:s'),
            'project_id' => $data['project_id'] ?? null,
            'quiz_id' => $data['quiz_id'] ?? null,
        ];
    }

    public function getRequiredFields(): array
    {
        return [
            'emails'
        ];
    }

    /**
     * Validate specific field value
     */
    protected function validateFieldValue(string $field, $value): bool
    {
        switch ($field) {
            case 'emails':
                if (!is_array($value) || empty($value)) {
                    return false;
                }
                foreach ($value as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return false;
                    }
                }
                return true;
            case 'template_id':
                return is_null($value) || is_string($value);
            case 'subject':
                return is_null($value) || is_string($value);
            default:
                return true;
        }
    }
}