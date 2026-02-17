<?php

namespace App\Services\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use JsonException;

class LeadContactEncryptionService
{
    private const CURRENT_VERSION = 1;

    /**
     * Encrypts the contacts payload and wraps it into a versioned structure
     */
    public function encrypt(?array $contacts): ?array
    {
        if (empty($contacts)) {
            return null;
        }

        try {
            $serialized = json_encode($contacts, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('Failed to serialize contacts payload', [
                'error' => $exception->getMessage(),
            ]);
            return null;
        }

        return [
            'version' => self::CURRENT_VERSION,
            'payload' => Crypt::encryptString($serialized),
        ];
    }

    /**
     * Decrypts payloads stored by {@see encrypt}. If payload is not encrypted
     * yet (legacy data), it is returned as-is.
     */
    public function decrypt(array|string|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = is_array($value) ? $value : json_decode($value, true);

        // Legacy JSON value without encryption wrapper
        if (!is_array($decoded) || !array_key_exists('payload', $decoded)) {
            return $decoded;
        }

        try {
            $decrypted = Crypt::decryptString($decoded['payload']);
            return json_decode($decrypted, true);
        } catch (DecryptException $exception) {
            Log::warning('Failed to decrypt lead contacts', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}

