<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monitoring & Observability Configuration
    |--------------------------------------------------------------------------
    |
    | Configure metrics, alerts, and observability for production monitoring.
    | Integrates with Sentry, Datadog, Prometheus, New Relic, etc.
    |
    */

    /**
     * Enable/disable monitoring
     */
    'enabled' => env('MONITORING_ENABLED', true),

    /**
     * Application metrics to track
     */
    'metrics' => [
        'enabled' => true,
        'track' => [
            'leads_created' => true,
            'leads_updated' => true,
            'leads_deleted' => true,
            'integrations_success' => true,
            'integrations_failure' => true,
            'queue_depth' => true,
            'api_response_time' => true,
            'database_query_time' => true,
            'cache_hit_ratio' => true,
            'authentication_failures' => true,
        ],
    ],

    /**
     * Alert thresholds for automatic notifications
     */
    'alerts' => [
        'enabled' => true,
        'channels' => ['email', 'slack', 'sentry'],

        'thresholds' => [
            // Integration performance
            'integration_failure_rate' => [
                'threshold' => 5,        // % of failures
                'window' => 300,         // seconds
                'severity' => 'critical',
            ],

            // Queue health
            'queue_depth_high' => [
                'threshold' => 10000,
                'window' => 60,
                'severity' => 'warning',
            ],
            'queue_stuck' => [
                'threshold' => 3600,     // Job stuck for 1 hour
                'window' => 60,
                'severity' => 'critical',
            ],

            // API performance
            'api_latency_p99' => [
                'threshold' => 2000,     // milliseconds
                'window' => 300,
                'severity' => 'warning',
            ],
            'api_error_rate' => [
                'threshold' => 5,        // % of requests
                'window' => 300,
                'severity' => 'critical',
            ],

            // Database performance
            'database_query_slow' => [
                'threshold' => 1000,     // milliseconds
                'window' => 300,
                'severity' => 'warning',
            ],
            'database_connection_error' => [
                'threshold' => 5,        // absolute count
                'window' => 60,
                'severity' => 'critical',
            ],

            // Cache performance
            'cache_miss_ratio_high' => [
                'threshold' => 50,       // % of cache hits
                'window' => 300,
                'severity' => 'warning',
            ],

            // Security
            'brute_force_attempt' => [
                'threshold' => 10,       // failed attempts
                'window' => 300,
                'severity' => 'critical',
            ],
            'rate_limit_exceeded' => [
                'threshold' => 100,      // count
                'window' => 300,
                'severity' => 'warning',
            ],

            // Infrastructure
            'memory_usage_percent' => [
                'threshold' => 85,       // % of available memory
                'window' => 60,
                'severity' => 'warning',
            ],
            'disk_usage_percent' => [
                'threshold' => 90,       // % of available disk
                'window' => 3600,
                'severity' => 'critical',
            ],
        ],
    ],

    /**
     * Sentry configuration
     */
    'sentry' => [
        'enabled' => env('SENTRY_LARAVEL_DSN') !== null,
        'sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'environment' => env('SENTRY_ENVIRONMENT', 'production'),
        'release' => env('SENTRY_RELEASE', null),
        'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
    ],

    /**
     * Custom metrics endpoints
     */
    'endpoints' => [
        'prometheus' => [
            'enabled' => env('PROMETHEUS_ENABLED', false),
            'url' => env('PROMETHEUS_URL', 'http://localhost:9090'),
            'namespace' => 'laravel_crm',
        ],
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY'),
            'site' => env('DATADOG_SITE', 'datadoghq.com'),
            'tags' => [
                'service' => 'laravel-crm',
                'env' => env('APP_ENV', 'production'),
            ],
        ],
        'new_relic' => [
            'enabled' => env('NEW_RELIC_ENABLED', false),
            'license_key' => env('NEW_RELIC_LICENSE_KEY'),
            'app_name' => env('NEW_RELIC_APP_NAME', 'Laravel CRM'),
        ],
    ],

    /**
     * Log levels and configuration
     */
    'logging' => [
        'log_slow_queries' => env('LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds

        'log_http_requests' => env('LOG_HTTP_REQUESTS', true),
        'http_log_channels' => ['stack'],

        'log_queue_jobs' => env('LOG_QUEUE_JOBS', true),
        'queue_log_channels' => ['stack'],

        'log_integrations' => env('LOG_INTEGRATIONS', true),
        'integration_log_channels' => ['stack'],

        'log_database_errors' => env('LOG_DATABASE_ERRORS', true),
        'log_redis_errors' => env('LOG_REDIS_ERRORS', true),
        'log_authentication_events' => env('LOG_AUTHENTICATION_EVENTS', true),
    ],

    /**
     * Health check configuration
     */
    'health_checks' => [
        'enabled' => true,
        'endpoint' => '/health',
        'checks' => [
            'database' => true,
            'redis' => true,
            'disk_space' => true,
            'queue' => true,
            'memory' => true,
        ],
        'cache_result' => 60, // seconds
    ],

    /**
     * Distributed tracing
     */
    'tracing' => [
        'enabled' => env('TRACING_ENABLED', false),
        'sample_rate' => 0.1,
        'exporters' => [
            'jaeger' => env('JAEGER_ENABLED', false),
            'zipkin' => env('ZIPKIN_ENABLED', false),
        ],
    ],

    /**
     * Custom dashboards
     */
    'dashboards' => [
        'grafana' => [
            'url' => env('GRAFANA_URL', 'http://localhost:3000'),
            'api_key' => env('GRAFANA_API_KEY'),
        ],
        'datadog' => [
            'url' => env('DATADOG_DASHBOARD_URL'),
        ],
    ],

    /**
     * Incident response
     */
    'incident_response' => [
        'pagerduty' => [
            'enabled' => env('PAGERDUTY_ENABLED', false),
            'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
        ],
        'slack' => [
            'enabled' => env('SLACK_ALERTS_ENABLED', true),
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => env('SLACK_ALERT_CHANNEL', '#alerts'),
            'mention_on_critical' => ['@ops-team'],
        ],
        'email' => [
            'enabled' => env('EMAIL_ALERTS_ENABLED', true),
            'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', 'ops@example.com')),
        ],
    ],
];
