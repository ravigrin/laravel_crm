<?php

namespace App\JsonApi\V1\Statuses;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class StatusRequest extends ResourceRequest
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
            'statusId' => ['required', 'integer'],
            'externalEntityId' => ['required', 'string', 'max:250'],
            'label' => ['required', 'string', 'max:250'],
            'color' => ['nullable', 'string', 'max:250'],
        ];
    }

    /**
     * Get validation rules for resource updates.
     */
    protected function updateRules(): array
    {
        return [
            'statusId' => ['sometimes', 'integer'],
            'externalEntityId' => ['sometimes', 'string', 'max:250'],
            'label' => ['sometimes', 'string', 'max:250'],
            'color' => ['sometimes', 'string', 'max:250'],
        ];
    }
}




