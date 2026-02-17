<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the query cache system that replaces the old
    | QueryCacheable trait functionality.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Cache TTL
    |--------------------------------------------------------------------------
    |
    | Default time-to-live for cached queries in seconds
    |
    */
    'default_ttl' => env('QUERY_CACHE_DEFAULT_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | Cache driver to use for query caching. If null, uses the default
    | cache driver from config/cache.php
    |
    */
    'driver' => env('QUERY_CACHE_DRIVER', null),

    /*
    |--------------------------------------------------------------------------
    | Model Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration for specific models including TTL, tags, and caching options
    |
    */
    'models' => [
        \App\Models\Lead::class => [
            'ttl' => env('LEAD_CACHE_TTL', 3600), // 1 hour
            'tags' => ['leads', 'models'],
            'cache_find' => true,
            'cache_where' => true,
            'cache_queries' => true,
            'cache_collections' => true,
            'flush_on_update' => true,
        ],

        \App\Models\Status::class => [
            'ttl' => env('STATUS_CACHE_TTL', 3600), // 1 hour
            'tags' => ['statuses', 'models'],
            'cache_find' => true,
            'cache_where' => true,
            'cache_queries' => true,
            'cache_collections' => true,
            'flush_on_update' => true,
        ],

        \App\Models\Email::class => [
            'ttl' => env('EMAIL_CACHE_TTL', 7200), // 2 hours
            'tags' => ['emails', 'templates', 'models'],
            'cache_find' => true,
            'cache_where' => true,
            'cache_queries' => false, // Templates change less frequently
            'cache_collections' => true,
            'flush_on_update' => true,
        ],

        \App\Models\User::class => [
            'ttl' => env('USER_CACHE_TTL', 1800), // 30 minutes
            'tags' => ['users', 'models'],
            'cache_find' => true,
            'cache_where' => true,
            'cache_queries' => true,
            'cache_collections' => false, // Users change frequently
            'flush_on_update' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all query cache keys
    |
    */
    'key_prefix' => env('QUERY_CACHE_PREFIX', 'query_cache'),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Whether to log cache operations for debugging
    |
    */
    'enable_logging' => env('QUERY_CACHE_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Statistics
    |--------------------------------------------------------------------------
    |
    | Whether to collect cache statistics
    |
    */
    'collect_stats' => env('QUERY_CACHE_STATS', true),

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | What to do when cache operations fail:
    | - 'execute' - Execute the query anyway (recommended)
    | - 'throw' - Throw an exception
    | - 'return_null' - Return null
    |
    */
    'fallback_behavior' => env('QUERY_CACHE_FALLBACK', 'execute'),

    /*
    |--------------------------------------------------------------------------
    | Cache Warming
    |--------------------------------------------------------------------------
    |
    | Configuration for cache warming strategies
    |
    */
    'warming' => [
        'enabled' => env('QUERY_CACHE_WARMING', false),
        'strategies' => [
            'on_boot' => false, // Warm cache on application boot
            'on_schedule' => false, // Warm cache via scheduled command
            'on_demand' => true, // Warm cache on demand
        ],
        'models' => [
            // Models to warm up on boot
            \App\Models\Status::class,
            \App\Models\Email::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring cache performance
    |
    */
    'monitoring' => [
        'enabled' => env('QUERY_CACHE_MONITORING', false),
        'slow_query_threshold' => env('QUERY_CACHE_SLOW_THRESHOLD', 100), // milliseconds
        'metrics' => [
            'hit_rate' => true,
            'miss_rate' => true,
            'avg_response_time' => true,
            'cache_size' => true,
        ],
    ],
];

