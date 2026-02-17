<?php

namespace App\Services;

use App\Exceptions\ClientException;
use App\Interfaces\MailServiceInterface;
use Illuminate\Support\Facades\Log;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;

class MailService implements MailServiceInterface
{
    protected ?PostmarkClient $client = null;
    protected ?string $emailFrom = null;
    protected bool $initialized = false;

    /**
     * Initialize Postmark client lazily
     */
    protected function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            $secret = config('services.postmark.secret');
            if (!$secret) {
                throw new ClientException('Postmark secret not specified');
            }

            $this->emailFrom = config('services.postmark.from', 'robot@marquiz.ru');
            $this->client = new PostmarkClient($secret);
            $this->initialized = true;
        } catch (ClientException $exception) {
            Log::critical('MailService initialization failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw $exception;
        }
    }

    /**
     * Send email via Postmark
     *
     * @param string $address
     * @param string $template
     * @param array $data
     * @return bool
     */
    public function send(string $address, string $template, array $data = []): bool
    {
        $this->ensureInitialized();
        
        try {
            $result = $this->client->sendEmailWithTemplate(
                $this->emailFrom,
                $address,
                $template,
                $data
            );

            Log::info('Email sent successfully', [
                'to' => $address,
                'template' => $template,
                'message_id' => $result->getMessageID()
            ]);

            return true;
        } catch (PostmarkException $exception) {
            Log::error('Failed to send email', [
                'to' => $address,
                'template' => $template,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send email with template
     *
     * @param string $address
     * @param string $templateId
     * @param array $templateData
     * @return bool
     */
    public function sendWithTemplate(string $address, string $templateId, array $templateData = []): bool
    {
        return $this->send($address, $templateId, $templateData);
    }
}
