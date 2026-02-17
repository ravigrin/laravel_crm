<?php

namespace App\Services\Integration;

use App\Interfaces\IntegrationChannelInterface;
use App\Interfaces\IntegrationResult;
use App\Models\Lead;
use InvalidArgumentException;

/**
 * Integration manager
 */
class IntegrationManager
{
    protected ?IntegrationChannelInterface $integration = null;
    protected IntegrationFactory $factory;
    
    public function __construct(IntegrationFactory $factory)
    {
        $this->factory = $factory;
    }
    
    /**
     * Set the integration by type
     *
     * @param string $type
     * @param array $config
     */
    public function setIntegrationByType(string $type, array $config = []): void
    {
        $this->integration = $this->factory->create($type, $config);
    }

    /**
     * Set the integration directly
     *
     * @param IntegrationChannelInterface $integration
     */
    public function setIntegration(IntegrationChannelInterface $integration): void
    {
        $this->integration = $integration;
    }
    
    /**
     * Send lead to integration
     *
     * @param Lead $lead
     * @param array $credentials
     * @return IntegrationResult
     */
    public function send(Lead $lead, array $credentials): IntegrationResult
    {
        if (!$this->integration) {
            throw new InvalidArgumentException('No integration set');
        }
        
        return $this->integration->send($lead, $credentials);
    }

    /**
     * Update lead in integration
     *
     * @param Lead $lead
     * @param array $credentials
     * @return IntegrationResult
     */
    public function update(Lead $lead, array $credentials): IntegrationResult
    {
        if (!$this->integration) {
            throw new InvalidArgumentException('No integration set');
        }
        
        return $this->integration->update($lead, $credentials);
    }

    /**
     * Test integration connection
     *
     * @param array $credentials
     * @return IntegrationResult
     */
    public function testConnection(array $credentials): IntegrationResult
    {
        if (!$this->integration) {
            throw new InvalidArgumentException('No integration set');
        }
        
        return $this->integration->testConnection($credentials);
    }
    
    /**
     * Get the integration type
     *
     * @return string
     */
    public function getIntegrationType(): string
    {
        if (!$this->integration) {
            throw new InvalidArgumentException('No integration set');
        }

        return $this->integration->getType();
    }

    /**
     * Check if integration is set
     *
     * @return bool
     */
    public function hasIntegration(): bool
    {
        return $this->integration !== null;
    }

    /**
     * Get available integration types
     *
     * @return array
     */
    public function getAvailableTypes(): array
    {
        return $this->factory->getAvailableTypes();
    }

    /**
     * Check if integration type is supported
     *
     * @param string $type
     * @return bool
     */
    public function isTypeSupported(string $type): bool
    {
        return $this->factory->isSupported($type);
    }
}