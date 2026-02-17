<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => env('HORIZON_USE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event will
    | be fired. Every connection / queue combination may have its own unique
    | threshold; however, this is the threshold used by default.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, you want at least few
    | hours of these jobs but the values you choose are dependent on your
    | needs. You are free to change these values based on your requirements.
    |
    */

    'trim' => [
        'recent' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait for all of the current workers to finish the current job before
    | terminating. This may provide faster termination if you have large
    | numbers of workers and long-running jobs.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum memory limit that will be consumed by
    | a single worker. When a worker is approaching this limit, it will be
    | restarted to prevent memory leaks from consuming your application.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be processed by Horizon.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['integration-email', 'integration-amocrm', 'notifications'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'maxTime' => 0,
                'maxJobs' => 0,
                'memory' => 128,
                'tries' => 3,
                'nice' => 0,
                'timeout' => 300,
                'sleep' => 3,
                'rest' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['integration-email', 'integration-amocrm', 'notifications'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 3,
                'timeout' => 300,
                'sleep' => 3,
                'rest' => 3,
            ],
        ],

        'testing' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['integration-email', 'integration-amocrm', 'notifications'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 1,
                'timeout' => 300,
                'sleep' => 3,
                'rest' => 3,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dark Mode
    |--------------------------------------------------------------------------
    |
    | By enabling this option, Horizon will use a dark theme by default.
    | You may disable this option if you prefer the light theme.
    |
    */

    'dark_mode' => env('HORIZON_DARK_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | The following array of job classes will not be displayed in Horizon's
    | dashboard. This is useful for jobs that run very frequently or are
    | not important to monitor during development.
    |
    */

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

];

