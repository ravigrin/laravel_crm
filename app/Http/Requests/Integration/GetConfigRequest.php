<?php

namespace App\Http\Requests\Integration;

use App\Services\Integration\IntegrationManager;

class GetConfigRequest extends BaseIntegrationRequest
{
    protected ?IntegrationManager $integrationManager = null;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
    }

    /**
     * Get the integration manager instance
     */
    protected function getIntegrationManager(): IntegrationManager
    {
        if ($this->integrationManager === null) {
            $this->integrationManager = app(IntegrationManager::class);
        }
        return $this->integrationManager;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Type comes from route parameter, no need to validate here
        // The controller will handle validation
        return [];
    }

    /**
     * Get the integration type from route parameter
     */
    public function getIntegrationType(): string
    {
        $type = $this->route('type');
        if (empty($type)) {
            $type = $this->input('type', '');
        }
        return (string) $type;
    }

    /**
     * Validate integration-specific rules
     */
    protected function validateIntegrationSpecificRules($validator): void
    {
        // Skip validation here - let the controller handle it
        // This prevents exceptions during request validation
    }
}
