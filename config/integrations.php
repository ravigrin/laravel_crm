<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Integration Configurations
    |--------------------------------------------------------------------------
    |
    | Configuration for various integrations
    |
    */

    'email' => [
        'required_fields' => ['emails'],
        'fields' => [
            'name' => ['type' => 'attr', 'key' => 'name'],
            'email' => ['type' => 'attr', 'key' => 'email'],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
            'data' => ['type' => 'attr', 'key' => 'data'],
            'utm_source' => ['type' => 'attr', 'key' => 'utm_source'],
            'utm_medium' => ['type' => 'attr', 'key' => 'utm_medium'],
            'utm_campaign' => ['type' => 'attr', 'key' => 'utm_campaign'],
            'created_at' => ['type' => 'date', 'key' => 'created_at'],
            'project_id' => ['type' => 'attr', 'key' => 'project_id'],
            'quiz_id' => ['type' => 'attr', 'key' => 'quiz_id'],
        ]
    ],

    'amocrm' => [
        'base_url' => env('AMOCRM_BASE_URL', 'https://example.amocrm.ru'),
        'required_fields' => ['access_token', 'base_url', 'responsible_user_id'],
        'fields' => [
            'name' => ['type' => 'attr', 'key' => 'name'],
            'price' => ['type' => 'const', 'value' => 0],
            'phone' => ['type' => 'complex', 'key' => 'phone'],
            'email' => ['type' => 'complex', 'key' => 'email'],
            'utm_source' => ['type' => 'attr', 'key' => 'utm_source'],
            'utm_medium' => ['type' => 'attr', 'key' => 'utm_medium'],
            'utm_campaign' => ['type' => 'attr', 'key' => 'utm_campaign'],
            'utm_content' => ['type' => 'attr', 'key' => 'utm_content'],
            'utm_term' => ['type' => 'attr', 'key' => 'utm_term'],
            'source' => ['type' => 'attr', 'key' => 'source'],
            'medium' => ['type' => 'attr', 'key' => 'medium'],
            'campaign' => ['type' => 'attr', 'key' => 'campaign'],
            'referrer' => ['type' => 'attr', 'key' => 'referrer'],
            'landing_page' => ['type' => 'attr', 'key' => 'landing_page'],
        ]
    ],

    'telegram' => [
        'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org/bot{token}'),
        'required_fields' => ['bot_token', 'chats'],
        'fields' => [
            'title' => ['type' => 'trans', 'key' => 'lead.title', 'locale' => 'RU'],
            'contacts' => [
                'email' => ['type' => 'attr', 'key' => 'email'],
                'phone' => ['type' => 'attr', 'key' => 'phone'],
            ],
            'answers' => ['type' => 'answers_html'],
            'link' => ['type' => 'const', 'value' => 'View Lead'],
            'link_url' => ['type' => 'dynamic', 'key' => 'id', 'template' => '/leads/{value}'],
        ]
    ],

    'bitrix24' => [
        'base_url' => env('BITRIX24_BASE_URL'),
        'required_fields' => ['webhook_url', 'user_id'],
        'fields' => [
            'TITLE' => ['type' => 'attr', 'key' => 'name'],
            'NAME' => ['type' => 'attr', 'key' => 'name'],
            'EMAIL' => ['type' => 'complex', 'key' => 'email'],
            'PHONE' => ['type' => 'complex', 'key' => 'phone'],
            'COMMENTS' => ['type' => 'answers_text'],
            'ASSIGNED_BY_ID' => ['type' => 'credentials', 'key' => 'user_id'],
            'UTM_SOURCE' => ['type' => 'attr', 'key' => 'utm_source'],
            'UTM_MEDIUM' => ['type' => 'attr', 'key' => 'utm_medium'],
            'UTM_CAMPAIGN' => ['type' => 'attr', 'key' => 'utm_campaign'],
            'UTM_CONTENT' => ['type' => 'attr', 'key' => 'utm_content'],
            'UTM_TERM' => ['type' => 'attr', 'key' => 'utm_term'],
            'SOURCE_ID' => ['type' => 'attr', 'key' => 'source'],
            'MEDIUM' => ['type' => 'attr', 'key' => 'medium'],
            'CAMPAIGN' => ['type' => 'attr', 'key' => 'campaign'],
            'REFERRER' => ['type' => 'attr', 'key' => 'referrer'],
            'LANDING_PAGE' => ['type' => 'attr', 'key' => 'landing_page'],
        ]
    ],

    'getresponse' => [
        'base_url' => env('GETRESPONSE_BASE_URL', 'https://api.getresponse.com/v3'),
        'required_fields' => ['api_key', 'campaign_id'],
        'fields' => [
            'name' => ['type' => 'attr', 'key' => 'name'],
            'email' => ['type' => 'attr', 'key' => 'email'],
            'dayOfCycle' => ['type' => 'const', 'value' => 0],
        ]
    ],

    'sendpulse' => [
        'base_url' => env('SENDPULSE_BASE_URL', 'https://api.sendpulse.com'),
        'required_fields' => ['client_id', 'client_secret', 'address_book_id'],
        'fields' => [
            'email' => ['type' => 'attr', 'key' => 'email'],
            'name' => ['type' => 'attr', 'key' => 'name'],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
        ]
    ],

    'unisender' => [
        'base_url' => env('UNISENDER_BASE_URL', 'https://api.unisender.com/ru/api'),
        'required_fields' => ['api_key', 'list_id'],
        'fields' => [
            'email' => ['type' => 'attr', 'key' => 'email'],
            'name' => ['type' => 'attr', 'key' => 'name'],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
        ]
    ],

    'uon_travel' => [
        'base_url' => env('UON_TRAVEL_BASE_URL', 'https://api.u-on.ru'),
        'required_fields' => ['api_key', 'project_id'],
        'fields' => [
            'name' => ['type' => 'attr', 'key' => 'name'],
            'email' => ['type' => 'attr', 'key' => 'email'],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
        ]
    ],

    'lptracker' => [
        'base_url' => env('LPTRACKER_BASE_URL', 'https://api.lptracker.ru'),
        'required_fields' => ['api_key', 'project_id'],
        'fields' => [
            'name' => ['type' => 'attr', 'key' => 'name'],
            'email' => ['type' => 'attr', 'key' => 'email'],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
        ]
    ],

    'webhooks' => [
        'required_fields' => ['url'],
        'fields' => [
            'name' => ['type' => 'attr', 'key' => 'name'],
            'email' => ['type' => 'attr', 'key' => 'email'],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
            'data' => ['type' => 'attr', 'key' => 'data'],
        ]
    ],

    'retailcrm' => [
        'base_url' => env('RETAILCRM_BASE_URL', 'https://example.retailcrm.ru'),
        'required_fields' => ['api_key'],
        'fields' => [
            'firstName' => ['type' => 'attr', 'key' => 'name'],
            'lastName' => ['type' => 'const', 'value' => ''],
            'email' => ['type' => 'complex', 'key' => 'email'],
            'phone' => ['type' => 'complex', 'key' => 'phone'],
            'status' => ['type' => 'const', 'value' => 'new'],
            'orderType' => ['type' => 'const', 'value' => 'fizik'],
            'orderMethod' => ['type' => 'const', 'value' => 'api'],
            'customerComment' => ['type' => 'answers_text'],
            'managerComment' => ['type' => 'const', 'value' => ''],
            'totalSumm' => ['type' => 'const', 'value' => 0],
            'deliveryCode' => ['type' => 'const', 'value' => 'delivery'],
            'deliveryCost' => ['type' => 'const', 'value' => 0],
            'deliveryAddress' => ['type' => 'const', 'value' => ''],
            'utm_source' => ['type' => 'attr', 'key' => 'utm_source'],
            'utm_medium' => ['type' => 'attr', 'key' => 'utm_medium'],
            'utm_campaign' => ['type' => 'attr', 'key' => 'utm_campaign'],
            'utm_content' => ['type' => 'attr', 'key' => 'utm_content'],
            'utm_term' => ['type' => 'attr', 'key' => 'utm_term'],
            'source' => ['type' => 'attr', 'key' => 'source'],
            'medium' => ['type' => 'attr', 'key' => 'medium'],
            'campaign' => ['type' => 'attr', 'key' => 'campaign'],
            'referrer' => ['type' => 'attr', 'key' => 'referrer'],
            'landing_page' => ['type' => 'attr', 'key' => 'landing_page'],
            'project_id' => ['type' => 'attr', 'key' => 'project_id'],
            'quiz_id' => ['type' => 'attr', 'key' => 'quiz_id'],
            'created_at' => ['type' => 'date', 'key' => 'created_at'],
        ]
    ],

    'mailchimp' => [
        'base_url' => env('MAILCHIMP_BASE_URL', 'https://us1.api.mailchimp.com'),
        'required_fields' => ['api_key', 'server_prefix', 'list_id'],
        'fields' => [
            'email' => ['type' => 'attr', 'key' => 'email'],
            'first_name' => ['type' => 'attr', 'key' => 'name'],
            'last_name' => ['type' => 'const', 'value' => ''],
            'phone' => ['type' => 'attr', 'key' => 'phone'],
            'status' => ['type' => 'const', 'value' => 'subscribed'],
            'language' => ['type' => 'const', 'value' => 'ru'],
            'tags' => ['type' => 'const', 'value' => []],
            'merge_fields' => ['type' => 'const', 'value' => []],
            'utm_source' => ['type' => 'attr', 'key' => 'utm_source'],
            'utm_medium' => ['type' => 'attr', 'key' => 'utm_medium'],
            'utm_campaign' => ['type' => 'attr', 'key' => 'utm_campaign'],
            'utm_content' => ['type' => 'attr', 'key' => 'utm_content'],
            'utm_term' => ['type' => 'attr', 'key' => 'utm_term'],
            'source' => ['type' => 'attr', 'key' => 'source'],
            'medium' => ['type' => 'attr', 'key' => 'medium'],
            'campaign' => ['type' => 'attr', 'key' => 'campaign'],
            'referrer' => ['type' => 'attr', 'key' => 'referrer'],
            'landing_page' => ['type' => 'attr', 'key' => 'landing_page'],
            'project_id' => ['type' => 'attr', 'key' => 'project_id'],
            'quiz_id' => ['type' => 'attr', 'key' => 'quiz_id'],
            'created_at' => ['type' => 'date', 'key' => 'created_at'],
        ]
    ],
];