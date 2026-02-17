<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\Lead\ClientIdRateLimiter;
use App\Services\Lead\DuplicateDetector;
use App\Services\Lead\GeoLocationService;
use App\Services\Lead\LeadBlockService;
use App\Services\Lead\LeadPaymentService;
use App\Services\Lead\LeadValidationService;
use App\Services\Lead\TestLeadLimiter;
use App\Services\Lead\UserRateLimiter;
use App\Services\PhoneVerification\PhoneVerificationService;

class LeadLifecycleObserver
{
    public function __construct(
        private readonly TestLeadLimiter $testLeadLimiter,
        private readonly DuplicateDetector $duplicateDetector,
        private readonly LeadBlockService $leadBlockService,
        private readonly PhoneVerificationService $phoneVerificationService,
        private readonly LeadPaymentService $leadPaymentService,
        private readonly LeadValidationService $leadValidationService,
        private readonly ClientIdRateLimiter $clientIdRateLimiter,
        private readonly UserRateLimiter $userRateLimiter,
        private readonly GeoLocationService $geoLocationService,
    ) {
    }

    public function creating(Lead $lead): void
    {
        if (!$lead->fingerprint) {
            $lead->fingerprint = request()->header('X-Client-Fingerprint', $lead->fingerprint);
        }

        // Validate userId and quizId existence, and check if quiz is blocked
        $this->leadValidationService->validateLead($lead);

        // Check phone verification - throws exception if not verified
        $this->phoneVerificationService->ensureVerified($lead->phone);

        // Rate limiting checks
        $this->userRateLimiter->ensureGlobalLimit($lead->user_id);
        $this->clientIdRateLimiter->ensureLeadsLimit($lead->fingerprint);
        $this->clientIdRateLimiter->ensureQuizzesLimit($lead->fingerprint, $lead->quiz_id);
        
        // Test lead limiting (per clientId/fingerprint)
        $this->testLeadLimiter->ensureWithinLimit($lead);
        
        // Duplicate detection
        $this->duplicateDetector->linkDuplicate($lead);

        // Payment detection
        if (!$lead->paid && $this->leadPaymentService->shouldMarkPaid($lead->data ?? [])) {
            $lead->paid = true;
        }

        // Blocklist check
        if ($this->leadBlockService->shouldBlock($lead)) {
            $lead->blocked = true;
        }

        // Geo-location by IP address
        if ($lead->ip_address && (!$lead->city || !$lead->country)) {
            $location = $this->geoLocationService->getLocationByIp($lead->ip_address);
            if ($location['city']) {
                $lead->city = $location['city'];
            }
            if ($location['country']) {
                $lead->country = $location['country'];
            }
        }
    }

    public function created(Lead $lead): void
    {
        $this->phoneVerificationService->attachLead($lead->id, $lead->phone);
    }
}

