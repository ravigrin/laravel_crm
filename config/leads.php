<?php

return [
    'test_lead_limit' => [
        'total' => env('LEADS_TEST_LIMIT', 20),
        'window_minutes' => env('LEADS_TEST_LIMIT_WINDOW', 10),
    ],

    'phone_verification' => [
        'ttl_minutes' => env('LEAD_PHONE_VERIFICATION_TTL', 10),
    ],
];

