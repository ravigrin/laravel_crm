<?php

namespace App\Http\Requests\Integration;

class Bitrix24IntegrationRequest extends BaseIntegrationRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:bitrix24',
            'credentials' => 'required|array',
            'credentials.webhook_url' => 'required|url',
            'credentials.user_id' => 'required|integer|min:1',
            'credentials.pipeline_id' => 'nullable|integer|min:1',
            'credentials.status_id' => 'nullable|integer|min:1',
            'credentials.stage_id' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'credentials.webhook_url.required' => 'Webhook URL is required',
            'credentials.webhook_url.url' => 'Webhook URL must be a valid URL',
            'credentials.user_id.required' => 'User ID is required',
            'credentials.user_id.integer' => 'User ID must be an integer',
            'credentials.user_id.min' => 'User ID must be greater than 0',
            'credentials.pipeline_id.integer' => 'Pipeline ID must be an integer',
            'credentials.pipeline_id.min' => 'Pipeline ID must be greater than 0',
            'credentials.status_id.integer' => 'Status ID must be an integer',
            'credentials.status_id.min' => 'Status ID must be greater than 0',
            'credentials.stage_id.integer' => 'Stage ID must be an integer',
            'credentials.stage_id.min' => 'Stage ID must be greater than 0',
        ];
    }
}
