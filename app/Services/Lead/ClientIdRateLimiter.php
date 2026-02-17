<?php

namespace App\Services\Lead;

use App\Models\Lead;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ClientIdRateLimiter
{
    /**
     * Limit: 5 leads per 20 minutes per clientId (fingerprint).
     */
    public function ensureLeadsLimit(?string $fingerprint): void
    {
        if (!$fingerprint) {
            return;
        }

        $limit = 5;
        $windowMinutes = 20;

        $count = Lead::query()
            ->where('fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($count >= $limit) {
            throw new TooManyRequestsHttpException(
                headers: [],
                message: sprintf(
                    'Leads limit reached for client (%d in %d minutes)',
                    $limit,
                    $windowMinutes
                )
            );
        }
    }

    /**
     * Limit: 5 different quizzes per 20 minutes per clientId (fingerprint).
     */
    public function ensureQuizzesLimit(?string $fingerprint, ?int $quizId): void
    {
        if (!$fingerprint || !$quizId) {
            return;
        }

        $limit = 5;
        $windowMinutes = 20;

        // Get distinct quiz_ids created by this fingerprint in the last 20 minutes
        $existingQuizIds = Lead::query()
            ->where('fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->whereNotNull('quiz_id')
            ->select('quiz_id')
            ->distinct()
            ->pluck('quiz_id')
            ->toArray();

        // Count unique quizzes
        $uniqueQuizzesCount = count(array_unique($existingQuizIds));

        // Check if current quiz is already in the list
        $currentQuizExists = in_array($quizId, $existingQuizIds);

        // If current quiz is new and we've reached the limit
        if (!$currentQuizExists && $uniqueQuizzesCount >= $limit) {
            throw new TooManyRequestsHttpException(
                headers: [],
                message: sprintf(
                    'Quizzes limit reached for client (%d different quizzes in %d minutes)',
                    $limit,
                    $windowMinutes
                )
            );
        }
    }
}

