<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Define rate limiting rules for different API consumers and integrations.
    | This prevents abuse, protects external APIs, and ensures fair usage.
    |
    */

    /**
     * Client-level rate limiting (per IP/fingerprint)
     */
    'client' => [
        'enabled' => env('RATE_LIMIT_CLIENT_ENABLED', true),
        'limit' => env('RATE_LIMIT_CLIENT_LIMIT', 100),      // requests
        'window' => env('RATE_LIMIT_CLIENT_WINDOW', 60),     // seconds
        'key' => 'client_ip',                                  // fingerprint | ip_address
    ],

    /**
     * User-level rate limiting (per authenticated user)
     */
    'user' => [
        'enabled' => env('RATE_LIMIT_USER_ENABLED', true),
        'limit' => env('RATE_LIMIT_USER_LIMIT', 1000),
        'window' => env('RATE_LIMIT_USER_WINDOW', 3600),     // 1 hour
    ],

    /**
     * Endpoint-specific limits
     */
    'endpoints' => [
        'leads.create' => [
            'limit' => 50,
            'window' => 3600,
            'key' => 'user_id',
        ],
        'leads.update' => [
            'limit' => 100,
            'window' => 3600,
            'key' => 'user_id',
        ],
        'leads.resend' => [
            'limit' => 20,
            'window' => 300,          // 5 minutes
            'key' => 'user_id',
        ],
        'integrations.test' => [
            'limit' => 10,
            'window' => 300,          // 5 minutes
            'key' => 'user_id',
        ],
    ],

    /**
     * Integration-specific API rate limits
     * These are limits for external API calls (not user API calls)
     */
    'integrations' => [
        'amocrm' => [
            'requests_per_second' => 5,
            'requests_per_hour' => 5000,
            'batch_size' => 50,
            'timeout' => 30,
            'retry_on_rate_limit' => true,
        ],
        'retail_crm' => [
            'requests_per_second' => 2,
            'requests_per_hour' => 10000,
            'batch_size' => 100,
            'timeout' => 30,
            'retry_on_rate_limit' => true,
        ],
        'telegram' => [
            'requests_per_second' => 1,
            'requests_per_minute' => 30,
            'timeout' => 10,
            'retry_on_rate_limit' => true,
        ],
        'getresponse' => [
            'requests_per_second' => 2,
            'requests_per_hour' => 1000,
            'batch_size' => 50,
            'timeout' => 30,
            'retry_on_rate_limit' => true,
        ],
        'sendpulse' => [
            'requests_per_second' => 2,
            'requests_per_minute' => 60,
            'timeout' => 30,
            'retry_on_rate_limit' => true,
        ],
    ],

    /**
     * Throttle responses
     * How to respond when rate limit is exceeded
     */
    'response' => [
        'status' => 429,
        'headers' => [
            'X-RateLimit-Limit' => 'Limit',
            'X-RateLimit-Remaining' => 'Remaining',
            'X-RateLimit-Reset' => 'Reset',
            'Retry-After' => 'RetryAfter',
        ],
    ],

    /**
     * Whitelist IPs that bypass rate limiting
     */
    'whitelist' => [
        'ips' => array_filter(explode(',', env('RATE_LIMIT_WHITELIST_IPS', ''))),
        'user_ids' => array_filter(explode(',', env('RATE_LIMIT_WHITELIST_USERS', ''))),
    ],

    /**
     * Blacklist IPs that are always rate limited
     */
    'blacklist' => [
        'ips' => array_filter(explode(',', env('RATE_LIMIT_BLACKLIST_IPS', ''))),
    ],
];
