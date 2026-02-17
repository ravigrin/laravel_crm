<?php

namespace App\Services;

use App\Interfaces\CrmServiceInterface;
use App\Models\Integration\EntityCredentials;
use App\Models\Integration\ProjectCredentials;
use Illuminate\Support\Facades\Cache;

class CrmService implements CrmServiceInterface
{
    protected int $cacheTtl = 3600; // 1 hour

    /**
     * Get email by external entity ID
     *
     * @param string|null $entityId
     * @return string|null
     */
    public function getEmailByExternalId(?string $entityId = null): ?string
    {
        if (!$entityId) {
            return null;
        }

        $cacheKey = "crm_email_entity_{$entityId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($entityId) {
            $credentials = EntityCredentials::with('credentials')
                ->where('external_entity_id', $entityId)
                ->get()
                ->pluck('credentials')
                ->flatten();

            foreach ($credentials as $cred) {
                if ($cred->code === 'email' && isset($cred->credentials['addresses'][0])) {
                    return $cred->credentials['addresses'][0];
                }
            }

            return null;
        });
    }

    /**
     * Get email by project ID
     *
     * @param string|null $projectId
     * @return string|null
     */
    public function getEmailByProjectId(?string $projectId = null): ?string
    {
        if (!$projectId) {
            return null;
        }

        $cacheKey = "crm_email_project_{$projectId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($projectId) {
            $credentials = ProjectCredentials::with('credentials')
                ->where('external_project_id', $projectId)
                ->get()
                ->pluck('credentials')
                ->flatten();

            foreach ($credentials as $cred) {
                if ($cred->code === 'email' && isset($cred->credentials['addresses'][0])) {
                    return $cred->credentials['addresses'][0];
                }
            }

            return null;
        });
    }

    /**
     * Get credentials for entity
     *
     * @param string $entityId
     * @param string $code
     * @return array|null
     */
    public function getEntityCredentials(string $entityId, string $code): ?array
    {
        $cacheKey = "crm_credentials_entity_{$entityId}_{$code}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($entityId, $code) {
            $credential = EntityCredentials::with('credentials')
                ->where('external_entity_id', $entityId)
                ->get()
                ->pluck('credentials')
                ->flatten()
                ->firstWhere('code', $code);

            return $credential ? $credential->credentials : null;
        });
    }

    /**
     * Get credentials for project
     *
     * @param string $projectId
     * @param string $code
     * @return array|null
     */
    public function getProjectCredentials(string $projectId, string $code): ?array
    {
        $cacheKey = "crm_credentials_project_{$projectId}_{$code}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($projectId, $code) {
            $credential = ProjectCredentials::with('credentials')
                ->where('external_project_id', $projectId)
                ->get()
                ->pluck('credentials')
                ->flatten()
                ->firstWhere('code', $code);

            return $credential ? $credential->credentials : null;
        });
    }

    /**
     * Clear cache for entity
     *
     * @param string $entityId
     * @return void
     */
    public function clearEntityCache(string $entityId): void
    {
        Cache::forget("crm_email_entity_{$entityId}");
        Cache::forget("crm_credentials_entity_{$entityId}_email");
    }

    /**
     * Clear cache for project
     *
     * @param string $projectId
     * @return void
     */
    public function clearProjectCache(string $projectId): void
    {
        Cache::forget("crm_email_project_{$projectId}");
        Cache::forget("crm_credentials_project_{$projectId}_email");
    }
}
