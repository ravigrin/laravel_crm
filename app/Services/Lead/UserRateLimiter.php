<?php

namespace App\Services\Lead;

use App\Models\Lead;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class UserRateLimiter
{
    /**
     * Global limit: 20,000 leads per hour per userId.
     */
    public function ensureGlobalLimit(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        $limit = 20000;
        $windowMinutes = 60;

        $count = Lead::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($count >= $limit) {
            throw new TooManyRequestsHttpException(
                headers: [],
                message: sprintf(
                    'Global leads limit reached for user (%d in %d minutes)',
                    $limit,
                    $windowMinutes
                )
            );
        }
    }
}



