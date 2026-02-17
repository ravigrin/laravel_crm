<?php

namespace App\Interfaces;

interface CrmServiceInterface
{
    /**
     * Get email by external entity ID
     *
     * @param string|null $entityId
     * @return string|null
     */
    public function getEmailByExternalId(?string $entityId = null): ?string;

    /**
     * Get email by project ID
     *
     * @param string|null $projectId
     * @return string|null
     */
    public function getEmailByProjectId(?string $projectId = null): ?string;

    /**
     * Get credentials for entity
     *
     * @param string $entityId
     * @param string $code
     * @return array|null
     */
    public function getEntityCredentials(string $entityId, string $code): ?array;

    /**
     * Get credentials for project
     *
     * @param string $projectId
     * @param string $code
     * @return array|null
     */
    public function getProjectCredentials(string $projectId, string $code): ?array;
}
