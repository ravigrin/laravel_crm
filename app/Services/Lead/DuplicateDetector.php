<?php

namespace App\Services\Lead;

use App\Models\Lead;

class DuplicateDetector
{
    public function linkDuplicate(Lead $lead): void
    {
        if (!$lead->fingerprint) {
            return;
        }

        $existing = Lead::query()
            ->where('fingerprint', $lead->fingerprint)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if ($existing && $existing->id !== $lead->id) {
            $lead->equal_answer_id = $existing->id;
        }
    }
}

