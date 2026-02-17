<?php

namespace App\Services\PhoneVerification;

use App\Models\PhoneVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PhoneVerificationService
{
    public function __construct(
        private readonly GreensmsClient $client
    ) {
    }

    public function ensureVerified(?string $phone): void
    {
        if (!$phone || !$this->isEnabled()) {
            return;
        }

        $verification = $this->latestVerification($phone);

        if (!$verification || $this->isExpired($verification)) {
            try {
                $verification = $this->performLookup($phone);
            } catch (RequestException $exception) {
                throw ValidationException::withMessages([
                    'phone' => 'Failed to verify phone number',
                ]);
            }
        }

        if (!$verification || $verification->status !== PhoneVerification::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'phone' => 'Phone was not verified via GreenSMS',
            ]);
        }
    }

    public function attachLead(int $leadId, ?string $phone): void
    {
        if (!$phone) {
            return;
        }

        PhoneVerification::query()
            ->where('phone', $phone)
            ->whereNull('lead_id')
            ->latest('id')
            ->first()?->update(['lead_id' => $leadId]);
    }

    protected function performLookup(string $phone): ?PhoneVerification
    {
        $response = $this->client->lookup($phone);

        $status = Arr::get($response, 'status') === 'completed'
            ? PhoneVerification::STATUS_VERIFIED
            : PhoneVerification::STATUS_FAILED;

        return PhoneVerification::query()->create([
            'phone' => $phone,
            'status' => $status,
            'verified_at' => $status === PhoneVerification::STATUS_VERIFIED ? now() : null,
            'expires_at' => $status === PhoneVerification::STATUS_VERIFIED
                ? now()->addMinutes(config('leads.phone_verification.ttl_minutes', 10))
                : null,
            'greensms_response' => $response,
        ]);
    }

    protected function latestVerification(string $phone): ?PhoneVerification
    {
        return PhoneVerification::query()
            ->where(fn (Builder $builder) => $builder->where('phone', $phone))
            ->latest('verified_at')
            ->first();
    }

    protected function isExpired(PhoneVerification $verification): bool
    {
        if (!$verification->expires_at) {
            return false;
        }

        return now()->greaterThan($verification->expires_at);
    }

    protected function isEnabled(): bool
    {
        return (bool) config('services.greensms.login') && (bool) config('services.greensms.password');
    }
}

