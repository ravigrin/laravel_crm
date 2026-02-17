<?php

namespace App\Http\Requests\Integration;

class TestConnectionRequest extends BaseIntegrationRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:email,amocrm,telegram,bitrix24,getresponse,sendpulse,unisender,uon_travel,lptracker,webhooks',
            'credentials' => 'required|array',
            'credentials.*' => 'required',
        ];
    }

    /**
     * Validate integration-specific rules
     */
    protected function validateIntegrationSpecificRules($validator): void
    {
        $type = $this->getIntegrationType();
        $credentials = $this->getCredentials();

        // Validate based on integration type
        switch ($type) {
            case 'email':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['emails']) || !is_array($credentials['emails'])) {
                        $validator->errors()->add('credentials.emails', 'Emails array is required for email integration');
                    } else {
                        foreach ($credentials['emails'] as $index => $email) {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $validator->errors()->add("credentials.emails.{$index}", 'Invalid email format');
                            }
                        }
                    }
                });
                break;

            case 'amocrm':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['access_token']) || empty($credentials['access_token'])) {
                        $validator->errors()->add('credentials.access_token', 'Access token is required for AmoCRM integration');
                    }
                    if (!isset($credentials['base_url']) || empty($credentials['base_url'])) {
                        $validator->errors()->add('credentials.base_url', 'Base URL is required for AmoCRM integration');
                    }
                    if (!isset($credentials['responsible_user_id']) || !is_numeric($credentials['responsible_user_id'])) {
                        $validator->errors()->add('credentials.responsible_user_id', 'Responsible user ID is required for AmoCRM integration');
                    }
                });
                break;

            case 'telegram':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['bot_token']) || empty($credentials['bot_token'])) {
                        $validator->errors()->add('credentials.bot_token', 'Bot token is required for Telegram integration');
                    }
                    if (!isset($credentials['chats']) || !is_array($credentials['chats'])) {
                        $validator->errors()->add('credentials.chats', 'Chats array is required for Telegram integration');
                    } else {
                        foreach ($credentials['chats'] as $index => $chat) {
                            if (!isset($chat['id']) || !is_numeric($chat['id'])) {
                                $validator->errors()->add("credentials.chats.{$index}.id", 'Chat ID must be numeric');
                            }
                        }
                    }
                });
                break;

            case 'bitrix24':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['webhook_url']) || empty($credentials['webhook_url'])) {
                        $validator->errors()->add('credentials.webhook_url', 'Webhook URL is required for Bitrix24 integration');
                    }
                    if (!isset($credentials['user_id']) || !is_numeric($credentials['user_id'])) {
                        $validator->errors()->add('credentials.user_id', 'User ID is required for Bitrix24 integration');
                    }
                });
                break;

            case 'getresponse':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['api_key']) || empty($credentials['api_key'])) {
                        $validator->errors()->add('credentials.api_key', 'API key is required for GetResponse integration');
                    }
                    if (!isset($credentials['campaign_id']) || empty($credentials['campaign_id'])) {
                        $validator->errors()->add('credentials.campaign_id', 'Campaign ID is required for GetResponse integration');
                    }
                });
                break;

            case 'sendpulse':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['client_id']) || empty($credentials['client_id'])) {
                        $validator->errors()->add('credentials.client_id', 'Client ID is required for SendPulse integration');
                    }
                    if (!isset($credentials['client_secret']) || empty($credentials['client_secret'])) {
                        $validator->errors()->add('credentials.client_secret', 'Client secret is required for SendPulse integration');
                    }
                    if (!isset($credentials['address_book_id']) || !is_numeric($credentials['address_book_id'])) {
                        $validator->errors()->add('credentials.address_book_id', 'Address book ID is required for SendPulse integration');
                    }
                });
                break;

            case 'unisender':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['api_key']) || empty($credentials['api_key'])) {
                        $validator->errors()->add('credentials.api_key', 'API key is required for UniSender integration');
                    }
                    if (!isset($credentials['list_id']) || !is_numeric($credentials['list_id'])) {
                        $validator->errors()->add('credentials.list_id', 'List ID is required for UniSender integration');
                    }
                });
                break;

            case 'uon_travel':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['api_key']) || empty($credentials['api_key'])) {
                        $validator->errors()->add('credentials.api_key', 'API key is required for UonTravel integration');
                    }
                    if (!isset($credentials['project_id']) || !is_numeric($credentials['project_id'])) {
                        $validator->errors()->add('credentials.project_id', 'Project ID is required for UonTravel integration');
                    }
                });
                break;

            case 'lptracker':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['api_key']) || empty($credentials['api_key'])) {
                        $validator->errors()->add('credentials.api_key', 'API key is required for LpTracker integration');
                    }
                    if (!isset($credentials['project_id']) || !is_numeric($credentials['project_id'])) {
                        $validator->errors()->add('credentials.project_id', 'Project ID is required for LpTracker integration');
                    }
                });
                break;

            case 'webhooks':
                $validator->after(function ($validator) use ($credentials) {
                    if (!isset($credentials['url']) || empty($credentials['url'])) {
                        $validator->errors()->add('credentials.url', 'URL is required for Webhooks integration');
                    }
                    if (!filter_var($credentials['url'], FILTER_VALIDATE_URL)) {
                        $validator->errors()->add('credentials.url', 'URL must be a valid URL');
                    }
                });
                break;
        }
    }
}
