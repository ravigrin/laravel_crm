<?php

namespace App\Jobs\Integrations;

use App\Jobs\NotifyByEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $type;
    public array $data;
    public array $recipients;
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(string $type, array $data, array $recipients = [])
    {
        $this->type = $type;
        $this->data = $data;
        $this->recipients = $recipients ?: $this->getDefaultRecipients();
    }

    public function handle(): void
    {
        try {
            Log::info('Processing batch notification', [
                'type' => $this->type,
                'data' => $this->data
            ]);

            switch ($this->type) {
                case 'integration_batch_success':
                    $this->handleSuccessNotification();
                    break;
                case 'integration_batch_failure':
                    $this->handleFailureNotification();
                    break;
                case 'integration_batch_critical_failure':
                    $this->handleCriticalFailureNotification();
                    break;
                default:
                    Log::warning('Unknown notification type', ['type' => $this->type]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process batch notification', [
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function handleSuccessNotification(): void
    {
        $leadId = $this->data['lead_id'] ?? 'unknown';
        $batchId = $this->data['batch_id'] ?? 'unknown';
        $successfulJobs = $this->data['successful_jobs'] ?? 0;
        $failedJobs = $this->data['failed_jobs'] ?? 0;
        $totalJobs = $this->data['total_jobs'] ?? 0;

        $subject = "Integration Batch Completed Successfully - Lead #{$leadId}";
        $message = $this->buildSuccessMessage($leadId, $batchId, $successfulJobs, $failedJobs, $totalJobs);

        $this->sendEmail($subject, $message);
    }

    protected function handleFailureNotification(): void
    {
        $leadId = $this->data['lead_id'] ?? 'unknown';
        $batchId = $this->data['batch_id'] ?? 'unknown';
        $error = $this->data['error'] ?? 'Unknown error';
        $failedJobs = $this->data['failed_jobs'] ?? 0;
        $totalJobs = $this->data['total_jobs'] ?? 0;

        $subject = "Integration Batch Failed - Lead #{$leadId}";
        $message = $this->buildFailureMessage($leadId, $batchId, $error, $failedJobs, $totalJobs);

        $this->sendEmail($subject, $message);
    }

    protected function handleCriticalFailureNotification(): void
    {
        $leadId = $this->data['lead_id'] ?? 'unknown';
        $error = $this->data['error'] ?? 'Unknown error';
        $attempts = $this->data['attempts'] ?? 0;

        $subject = "CRITICAL: Integration Batch Critical Failure - Lead #{$leadId}";
        $message = $this->buildCriticalFailureMessage($leadId, $error, $attempts);

        $this->sendEmail($subject, $message);
    }

    protected function buildSuccessMessage(int $leadId, string $batchId, int $successfulJobs, int $failedJobs, int $totalJobs): string
    {
        $status = $failedJobs > 0 ? 'Partial Success' : 'Complete Success';
        
        return "
        <h2>Integration Batch {$status}</h2>
        <p><strong>Lead ID:</strong> {$leadId}</p>
        <p><strong>Batch ID:</strong> {$batchId}</p>
        <p><strong>Total Jobs:</strong> {$totalJobs}</p>
        <p><strong>Successful Jobs:</strong> {$successfulJobs}</p>
        <p><strong>Failed Jobs:</strong> {$failedJobs}</p>
        <p><strong>Status:</strong> {$status}</p>
        <p><strong>Timestamp:</strong> " . now()->format('Y-m-d H:i:s') . "</p>
        ";
    }

    protected function buildFailureMessage(int $leadId, string $batchId, string $error, int $failedJobs, int $totalJobs): string
    {
        return "
        <h2>Integration Batch Failed</h2>
        <p><strong>Lead ID:</strong> {$leadId}</p>
        <p><strong>Batch ID:</strong> {$batchId}</p>
        <p><strong>Total Jobs:</strong> {$totalJobs}</p>
        <p><strong>Failed Jobs:</strong> {$failedJobs}</p>
        <p><strong>Error:</strong> {$error}</p>
        <p><strong>Timestamp:</strong> " . now()->format('Y-m-d H:i:s') . "</p>
        ";
    }

    protected function buildCriticalFailureMessage(int $leadId, string $error, int $attempts): string
    {
        return "
        <h2>CRITICAL: Integration Batch Critical Failure</h2>
        <p><strong>Lead ID:</strong> {$leadId}</p>
        <p><strong>Error:</strong> {$error}</p>
        <p><strong>Attempts:</strong> {$attempts}</p>
        <p><strong>Status:</strong> Job permanently failed</p>
        <p><strong>Timestamp:</strong> " . now()->format('Y-m-d H:i:s') . "</p>
        <p><strong>Action Required:</strong> Manual intervention needed</p>
        ";
    }

    protected function sendEmail(string $subject, string $message): void
    {
        NotifyByEmail::dispatch([
            'subject' => $subject,
            'message' => $message,
            'recipients' => $this->recipients,
            'is_html' => true
        ])->onQueue('notifications');
    }

    protected function getDefaultRecipients(): array
    {
        return [
            'admin@example.com',
            'notifications@example.com',
            'devops@example.com'
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('BatchNotificationJob permanently failed', [
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}

















