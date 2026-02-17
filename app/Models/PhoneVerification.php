<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    protected $table = 'phone_verifications';

    protected $fillable = [
        'lead_id',
        'phone',
        'code',
        'status',
        'verified_at',
        'expires_at',
        'attempts',
        'greensms_response',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'greensms_response' => 'array',
    ];

    public function markVerified(array $response = []): void
    {
        $this->forceFill([
            'status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'expires_at' => now()->addMinutes(config('services.greensms.verification_ttl', 10)),
            'greensms_response' => $response ?: $this->greensms_response,
        ])->save();
    }

    public function markFailed(array $response = []): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'greensms_response' => $response ?: $this->greensms_response,
        ])->save();
    }
}

