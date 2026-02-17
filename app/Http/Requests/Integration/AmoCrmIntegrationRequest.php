<?php

namespace App\Http\Requests\Integration;

class AmoCrmIntegrationRequest extends BaseIntegrationRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:amocrm',
            'credentials' => 'required|array',
            'credentials.access_token' => 'required|string|min:10',
            'credentials.base_url' => 'required|url',
            'credentials.responsible_user_id' => 'required|integer|min:1',
            'credentials.pipeline_id' => 'nullable|integer|min:1',
            'credentials.status_id' => 'nullable|integer|min:1',
            'credentials.price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'credentials.access_token.required' => 'Access token is required',
            'credentials.access_token.min' => 'Access token must be at least 10 characters',
            'credentials.base_url.required' => 'Base URL is required',
            'credentials.base_url.url' => 'Base URL must be a valid URL',
            'credentials.responsible_user_id.required' => 'Responsible user ID is required',
            'credentials.responsible_user_id.integer' => 'Responsible user ID must be an integer',
            'credentials.responsible_user_id.min' => 'Responsible user ID must be greater than 0',
            'credentials.pipeline_id.integer' => 'Pipeline ID must be an integer',
            'credentials.pipeline_id.min' => 'Pipeline ID must be greater than 0',
            'credentials.status_id.integer' => 'Status ID must be an integer',
            'credentials.status_id.min' => 'Status ID must be greater than 0',
            'credentials.price.numeric' => 'Price must be a number',
            'credentials.price.min' => 'Price must be greater than or equal to 0',
        ];
    }
}
