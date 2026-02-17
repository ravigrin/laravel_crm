<?php

namespace App\Http\Requests\Integration;

class EmailIntegrationRequest extends BaseIntegrationRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:email',
            'credentials' => 'required|array',
            'credentials.emails' => 'required|array|min:1',
            'credentials.emails.*' => 'required|email|max:255',
            'credentials.template_id' => 'nullable|string|max:255',
            'credentials.subject' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'credentials.emails.required' => 'Email addresses are required',
            'credentials.emails.array' => 'Email addresses must be an array',
            'credentials.emails.min' => 'At least one email address is required',
            'credentials.emails.*.required' => 'Email address is required',
            'credentials.emails.*.email' => 'Invalid email format',
            'credentials.emails.*.max' => 'Email address cannot exceed 255 characters',
        ];
    }
}
