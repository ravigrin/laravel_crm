<?php

namespace App\Services\Lead;

use App\Models\Lead;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class TestLeadLimiter
{
    private int $limit;
    private int $windowMinutes;

    public function __construct()
    {
        $this->limit = (int) config('leads.test_lead_limit.total', 20);
        $this->windowMinutes = (int) config('leads.test_lead_limit.window_minutes', 10);
    }

    /**
     * Limit: 20 test leads per 10 minutes per clientId (fingerprint).
     */
    public function ensureWithinLimit(Lead $lead): void
    {
        if (!$lead->is_test || !$lead->fingerprint) {
            return;
        }

        $count = Lead::query()
            ->where('fingerprint', $lead->fingerprint)
            ->where('is_test', true)
            ->where('created_at', '>=', now()->subMinutes($this->windowMinutes))
            ->count();

        if ($count >= $this->limit) {
            throw new TooManyRequestsHttpException(
                headers: [],
                message: sprintf(
                    'Test leads limit reached for client (%d in %d minutes)',
                    $this->limit,
                    $this->windowMinutes
                )
            );
        }
    }
}

