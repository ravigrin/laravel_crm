<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CreateLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Check against IP filter middleware instead
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Contact Information
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Zа-яА-ЯёЁ\s\-\'\.]+$/',
            ],
            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:254',
                'lowercase',
            ],
            'phone' => [
                'nullable',
                'regex:/^\+?[1-9]\d{1,14}$/', // E.164 format
            ],
            'messengers' => [
                'nullable',
                'array',
                'max:10',
            ],
            'messengers.*' => [
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9@._\-:\/]+$/',
            ],

            // Personal Data (will be encrypted)
            'contacts' => [
                'nullable',
                'array',
                'max:100',
            ],
            'contacts.*' => [
                'string',
                'max:500',
            ],

            // Location
            'ip_address' => [
                'nullable',
                'ip',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Zа-яА-ЯёЁ\s\-\'\.]+$/',
            ],
            'country' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/',
            ],

            // UTM Tags
            'utm_source' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'utm_medium' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'utm_campaign' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'utm_content' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'utm_term' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_\-\s]+$/',
            ],

            // Lead Data
            'data' => [
                'nullable',
                'array',
                'max:50', // Prevent JSON bomb attacks
            ],
            'data.*' => [
                'string|array|numeric|boolean',
                'max:10000',
            ],

            // Status and Flags
            'status' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'is_test' => [
                'nullable',
                'boolean',
            ],
            'viewed' => [
                'nullable',
                'boolean',
            ],
            'paid' => [
                'nullable',
                'boolean',
            ],
            'blocked' => [
                'nullable',
                'boolean',
            ],

            // Relationships
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
            ],
            'quiz_id' => [
                'nullable',
                'integer',
            ],

            // External Integration
            'external_id' => [
                'nullable',
                'string',
                'max:150',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'external_system' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9_\-]+$/',
            ],
            'external_entity' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.',
            'email.email' => 'Email address is invalid.',
            'phone.regex' => 'Phone must be in valid E.164 format (e.g., +1234567890).',
            'messengers.max' => 'Maximum 10 messenger contacts allowed.',
            'contacts.max' => 'Too many contact fields (max 100).',
            'data.max' => 'Lead data exceeds maximum size.',
            'utm_source.regex' => 'UTM source contains invalid characters.',
            'utm_medium.regex' => 'UTM medium contains invalid characters.',
            'utm_campaign.regex' => 'UTM campaign contains invalid characters.',
            'city.regex' => 'City name contains invalid characters.',
            'country.regex' => 'Country name contains invalid characters.',
            'external_id.regex' => 'External ID contains invalid characters.',
            'external_system.regex' => 'External system must be lowercase with underscores.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize and normalize inputs
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim($this->email)),
            ]);
        }

        if ($this->has('name')) {
            // Remove extra whitespace, trim
            $this->merge([
                'name' => Str::squish($this->name),
            ]);
        }

        if ($this->has('phone')) {
            // Remove spaces and common separators
            $this->merge([
                'phone' => preg_replace('/[\s\-\(\)]+/', '', $this->phone),
            ]);
        }

        // Remove any HTML/script tags from string fields
        $stringFields = ['name', 'city', 'country', 'external_id', 'external_system', 'external_entity'];
        foreach ($stringFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => htmlspecialchars(strip_tags($this->get($field)), ENT_QUOTES, 'UTF-8'),
                ]);
            }
        }

        // Recursively sanitize array fields
        if ($this->has('data')) {
            $this->merge([
                'data' => $this->sanitizeArray($this->data),
            ]);
        }

        if ($this->has('contacts')) {
            $this->merge([
                'contacts' => $this->sanitizeArray($this->contacts),
            ]);
        }
    }

    /**
     * Recursively sanitize array values
     */
    private function sanitizeArray(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitizeArray($value);
            }

            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                // Remove control characters
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
                // Limit length
                return Str::limit($value, 10000);
            }

            return $value;
        }, $data);
    }
}
