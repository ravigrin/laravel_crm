<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'secret' => env('POSTMARK_SECRET'),
        'from' => env('POSTMARK_FROM', 'robot@marquiz.ru'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'greensms' => [
        'base_url' => env('GREEN_SMS_BASE_URL', 'https://api3.greensms.ru'),
        'login' => env('GREEN_SMS_LOGIN'),
        'password' => env('GREEN_SMS_PASSWORD'),
        'verification_ttl' => env('GREEN_SMS_VERIFICATION_TTL', 10),
    ],

];
