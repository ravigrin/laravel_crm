<?php

namespace App\JsonApi\V1\Leads;

use App\Enums\DefaultStatuses;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LeadRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        if ($this->isCreating()) {
            return $this->creationRules();
        }

        if ($this->isUpdating()) {
            return $this->updateRules();
        }

        return [];
    }

    /**
     * Get validation rules for resource creation.
     */
    protected function creationRules(): array
    {
        return [
            'externalId' => ['nullable', 'string', 'max:150'],
            'name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:150'],
            'messengers' => ['nullable', 'array'],
            'data' => ['nullable', 'array'],
            'contacts' => ['nullable', 'array'],
            'ipAddress' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'integer'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'integrationStatus' => ['nullable', 'string', 'max:150'],
            'integrationData' => ['nullable', 'array'],
            'isTest' => ['nullable', 'boolean'],
            'viewed' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
            'blocked' => ['nullable', 'boolean'],
            'fingerprint' => ['nullable', 'string', 'max:255'],
            'equalAnswerId' => ['nullable', 'integer'],
            'utmSource' => ['nullable', 'string'],
            'utmMedium' => ['nullable', 'string'],
            'utmCampaign' => ['nullable', 'string'],
            'utmContent' => ['nullable', 'string'],
            'utmTerm' => ['nullable', 'string'],
            'externalProjectId' => ['nullable', 'string', 'max:250'],
            'externalSystem' => ['nullable', 'string', 'max:250'], // Defaults to 'example_system' in controller
            'externalEntity' => ['nullable', 'string', 'max:250'], // Defaults to 'lead' in controller
            'externalEntityId' => ['nullable', 'string', 'max:250'], // Defaults to UUID in controller
            'userId' => ['nullable', 'integer'],
            'projectId' => ['nullable', 'integer'],
            'quizId' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get validation rules for resource updates.
     */
    protected function updateRules(): array
    {
        $statusValues = implode(',', array_column(DefaultStatuses::cases(), 'value'));

        return [
            'externalId' => ['sometimes', 'nullable', 'string', 'max:150'],
            'name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:150'],
            'messengers' => ['sometimes', 'nullable', 'array'],
            'data' => ['sometimes', 'nullable', 'array'],
            'contacts' => ['sometimes', 'nullable', 'array'],
            'ipAddress' => ['sometimes', 'nullable', 'string', 'max:150'],
            'status' => ['sometimes', 'nullable', 'integer', 'in:' . $statusValues],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'integrationStatus' => ['sometimes', 'nullable', 'string', 'max:150'],
            'integrationData' => ['sometimes', 'nullable', 'array'],
            'isTest' => ['sometimes', 'nullable', 'boolean'],
            'viewed' => ['sometimes', 'nullable', 'boolean'],
            'paid' => ['sometimes', 'nullable', 'boolean'],
            'blocked' => ['sometimes', 'nullable', 'boolean'],
            'fingerprint' => ['sometimes', 'nullable', 'string', 'max:255'],
            'equalAnswerId' => ['sometimes', 'nullable', 'integer'],
            'utmSource' => ['sometimes', 'nullable', 'string'],
            'utmMedium' => ['sometimes', 'nullable', 'string'],
            'utmCampaign' => ['sometimes', 'nullable', 'string'],
            'utmContent' => ['sometimes', 'nullable', 'string'],
            'utmTerm' => ['sometimes', 'nullable', 'string'],
            'externalProjectId' => ['sometimes', 'nullable', 'string', 'max:250'],
            'externalSystem' => ['sometimes', 'nullable', 'string', 'max:250'],
            'externalEntity' => ['sometimes', 'nullable', 'string', 'max:250'],
            'externalEntityId' => ['sometimes', 'nullable', 'string', 'max:250'],
            'userId' => ['sometimes', 'nullable', 'integer'],
            'projectId' => ['sometimes', 'nullable', 'integer'],
            'quizId' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}


