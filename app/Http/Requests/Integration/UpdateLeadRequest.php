<?php

namespace App\Http\Requests\Integration;

class UpdateLeadRequest extends SendLeadRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'external_id' => 'required|string',
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'external_id.required' => 'External ID is required for update operations',
            'external_id.string' => 'External ID must be a string',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'external_id' => 'external ID',
        ]);
    }

    /**
     * Get the external ID from request
     */
    public function getExternalId(): string
    {
        return $this->input('external_id');
    }

    /**
     * Validate integration-specific rules
     */
    protected function validateIntegrationSpecificRules($validator): void
    {
        parent::validateIntegrationSpecificRules($validator);

        // Additional validation for update operations
        $type = $this->getIntegrationType();
        
        $validator->after(function ($validator) use ($type) {
            // Some integrations don't support updates
            if (in_array($type, ['email', 'webhooks'])) {
                $validator->errors()->add('type', "Integration type '{$type}' does not support update operations");
            }
        });
    }
}
