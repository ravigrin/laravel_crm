<?php

namespace App\Http\Requests\Integration;

use App\Models\Lead;

class SendLeadRequest extends TestConnectionRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'lead_id' => 'required|integer|exists:leads,id',
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'lead_id.required' => 'Lead ID is required',
            'lead_id.integer' => 'Lead ID must be an integer',
            'lead_id.exists' => 'Lead not found',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'lead_id' => 'lead ID',
        ]);
    }

    /**
     * Validate integration-specific rules
     */
    protected function validateIntegrationSpecificRules($validator): void
    {
        parent::validateIntegrationSpecificRules($validator);

        // Additional validation for lead-specific requirements
        $leadId = $this->getLeadId();
        if ($leadId) {
            $lead = Lead::find($leadId);
            
            if ($lead) {
                $validator->after(function ($validator) use ($lead) {
                    // Validate that lead has required data for the integration type
                    $type = $this->getIntegrationType();
                    
                    switch ($type) {
                        case 'email':
                            if (empty($lead->email)) {
                                $validator->errors()->add('lead_id', 'Lead must have an email address for email integration');
                            }
                            break;
                            
                        case 'amocrm':
                        case 'bitrix24':
                            if (empty($lead->name) && empty($lead->email) && empty($lead->phone)) {
                                $validator->errors()->add('lead_id', 'Lead must have at least name, email, or phone for CRM integration');
                            }
                            break;
                            
                        case 'telegram':
                            if (empty($lead->name)) {
                                $validator->errors()->add('lead_id', 'Lead must have a name for Telegram integration');
                            }
                            break;
                    }
                });
            }
        }
    }
}
