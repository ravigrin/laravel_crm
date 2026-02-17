<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'credentials.required' => 'Integration credentials are required',
            'credentials.array' => 'Credentials must be an array',
            'type.required' => 'Integration type is required',
            'type.string' => 'Integration type must be a string',
            'type.in' => 'Integration type is not supported',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'credentials' => 'integration credentials',
            'type' => 'integration type',
        ];
    }

    /**
     * Get the integration type from request
     */
    public function getIntegrationType(): string
    {
        return $this->input('type');
    }

    /**
     * Get the credentials from request
     */
    public function getCredentials(): array
    {
        return $this->input('credentials', []);
    }

    /**
     * Get the lead ID from request (if present)
     */
    public function getLeadId(): ?int
    {
        return $this->input('lead_id');
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic can be added here
            $this->validateIntegrationSpecificRules($validator);
        });
    }

    /**
     * Validate integration-specific rules
     * Override in child classes for specific validation
     */
    protected function validateIntegrationSpecificRules($validator): void
    {
        // Override in child classes
    }
}
