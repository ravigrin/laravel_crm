<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Telegram integration
 */
class TelegramIntegration extends BaseIntegration
{
    public function getType(): string
    {
        return 'telegram';
    }

    protected function performSend(array $data, array $credentials): array
    {
        try {
            $token = $credentials['bot_token'] ?? '';
            $chatId = $credentials['chat_id'] ?? '';
            $message = $this->formatMessage($data);

            if (empty($token) || empty($chatId)) {
                return [
                    'success' => false,
                    'message' => 'Bot token or chat ID not provided'
                ];
            }

            $apiUrl = $this->baseUrl ?: 'https://api.telegram.org';
            $response = Http::post($apiUrl . '/bot' . $token . '/sendMessage', [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'external_id' => $responseData['result']['message_id'] ?? null,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send message: ' . $response->body(),
                    'http_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telegram integration error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Telegram integration error: ' . $e->getMessage()
            ];
        }
    }

    protected function performUpdate(array $data, array $credentials): array
    {
        // Telegram doesn't support updates, so we'll send a new message
        return $this->performSend($data, $credentials);
    }

    protected function performTestConnection(array $credentials): array
    {
        try {
            $token = $credentials['bot_token'] ?? '';
            
            if (empty($token)) {
                return [
                    'success' => false,
                    'message' => 'Bot token not provided'
                ];
            }

            $apiUrl = $this->baseUrl ?: 'https://api.telegram.org';
            $response = Http::get($apiUrl . '/bot' . $token . '/getMe');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection test successful',
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection test failed: ' . $response->body(),
                    'http_code' => $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Telegram connection test error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Connection test error: ' . $e->getMessage()
            ];
        }
    }

    public function getRequiredFields(): array
    {
        return ['bot_token', 'chat_id'];
    }

    /**
     * Format lead data as Telegram message
     */
    protected function formatMessage(array $data): string
    {
        $message = "<b>Новая заявка</b>\n\n";
        
        if (isset($data['name'])) {
            $message .= "<b>Имя:</b> " . htmlspecialchars($data['name']) . "\n";
        }
        
        if (isset($data['phone'])) {
            $message .= "<b>Телефон:</b> " . htmlspecialchars($data['phone']) . "\n";
        }
        
        if (isset($data['email'])) {
            $message .= "<b>Email:</b> " . htmlspecialchars($data['email']) . "\n";
        }
        
        if (!empty($data['data']) && is_array($data['data'])) {
            $message .= "\n<b>Дополнительные данные:</b>\n";
            foreach ($data['data'] as $key => $value) {
                $message .= htmlspecialchars($key) . ": " . htmlspecialchars($value) . "\n";
            }
        }
        
        return $message;
    }
}