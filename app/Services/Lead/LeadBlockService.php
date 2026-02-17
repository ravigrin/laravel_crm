<?php

namespace App\Services\Lead;

use App\Models\Blocklist;
use App\Models\Lead;

class LeadBlockService
{
    public function shouldBlock(Lead $lead): bool
    {
        if (!$lead->phone && !$lead->email && !$lead->fingerprint && !$lead->ip_address
            && !$lead->quiz_id && !$lead->user_id) {
            return false;
        }

        return Blocklist::query()
            ->where('type', 'blacklist')
            ->where(function ($builder) use ($lead) {
            if ($lead->phone) {
                $builder->orWhere('phone', $lead->phone);
            }

            if ($lead->email) {
                $builder->orWhere('email', $lead->email);
            }

            if ($lead->fingerprint) {
                $builder->orWhere('fingerprint', $lead->fingerprint);
            }

            if ($lead->ip_address) {
                $builder->orWhere('ip_address', $lead->ip_address);
            }
                if ($lead->quiz_id) {
                    $builder->orWhere('quiz_id', $lead->quiz_id);
                }

                if ($lead->user_id) {
                    $builder->orWhere('user_id', $lead->user_id);
                }
            })
            ->exists();
    }
}

