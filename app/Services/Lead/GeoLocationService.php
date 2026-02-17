<?php

namespace App\Services\Lead;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    /**
     * Get city and country from IP address.
     * 
     * @param string|null $ipAddress
     * @return array{city: string|null, country: string|null}
     */
    public function getLocationByIp(?string $ipAddress): array
    {
        if (empty($ipAddress)) {
            return ['city' => null, 'country' => null];
        }

        // Skip private/local IPs
        if ($this->isPrivateIp($ipAddress)) {
            return ['city' => null, 'country' => null];
        }

        // Try to get from cache first
        $cacheKey = "geolocation:{$ipAddress}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Use ip-api.com (free, no API key required, 45 requests/minute)
            $response = Http::timeout(5)
                ->get("http://ip-api.com/json/{$ipAddress}", [
                    'fields' => 'status,message,country,city'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['status']) && $data['status'] === 'success') {
                    $result = [
                        'city' => $data['city'] ?? null,
                        'country' => $data['country'] ?? null,
                    ];

                    // Cache for 24 hours
                    Cache::put($cacheKey, $result, now()->addHours(24));

                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get geolocation from ip-api.com', [
                'ip' => $ipAddress,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: try alternative service (ipgeolocation.io requires API key, but we'll try without)
        try {
            $response = Http::timeout(5)
                ->get("https://ipapi.co/{$ipAddress}/json/");

            if ($response->successful()) {
                $data = $response->json();
                
                if (!isset($data['error'])) {
                    $result = [
                        'city' => $data['city'] ?? null,
                        'country' => $data['country_name'] ?? null,
                    ];

                    // Cache for 24 hours
                    Cache::put($cacheKey, $result, now()->addHours(24));

                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get geolocation from ipapi.co', [
                'ip' => $ipAddress,
                'error' => $e->getMessage()
            ]);
        }

        // Return null if all services failed
        return ['city' => null, 'country' => null];
    }

    /**
     * Check if IP is private/local.
     * 
     * @param string $ipAddress
     * @return bool
     */
    private function isPrivateIp(string $ipAddress): bool
    {
        // Check for IPv4 private ranges
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // Check for IPv6 private ranges (localhost, link-local, etc.)
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6 localhost
            if ($ipAddress === '::1' || $ipAddress === '0:0:0:0:0:0:0:1') {
                return true;
            }
            
            // IPv6 link-local (fe80::/10)
            if (str_starts_with($ipAddress, 'fe80:')) {
                return true;
            }
        }

        return false;
    }
}



