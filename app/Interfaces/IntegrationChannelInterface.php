<?php

namespace App\Interfaces;

use App\Models\Lead;

interface IntegrationChannelInterface
{
    /**
     * Send lead to integration
     *
     * @param Lead $lead
     * @param array $credentials
     * @return IntegrationResult
     */
    public function send(Lead $lead, array $credentials): IntegrationResult;

    /**
     * Update lead in integration
     *
     * @param Lead $lead
     * @param array $credentials
     * @return IntegrationResult
     */
    public function update(Lead $lead, array $credentials): IntegrationResult;

    /**
     * Validate credentials
     *
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(array $credentials): bool;

    /**
     * Get integration type
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get required fields for credentials
     *
     * @return array
     */
    public function getRequiredFields(): array;

    /**
     * Test connection with credentials
     *
     * @param array $credentials
     * @return IntegrationResult
     */
    public function testConnection(array $credentials): IntegrationResult;
}
