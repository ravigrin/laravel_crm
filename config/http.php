<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HTTP client used by services
    |
    */

    'timeout' => env('HTTP_TIMEOUT', 30),
    'connect_timeout' => env('HTTP_CONNECT_TIMEOUT', 10),
    
    'retry' => [
        'times' => env('HTTP_RETRY_TIMES', 3),
        'sleep_ms' => env('HTTP_RETRY_SLEEP_MS', 1000),
    ],
    
    'log_channel' => env('HTTP_LOG_CHANNEL', 'http'),
    
    'default_headers' => [
        'User-Agent' => 'MarQuiz-Integration/1.0',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
];