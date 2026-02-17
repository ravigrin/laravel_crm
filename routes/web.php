<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Marquiz CRM v1.0.0';
});

// Include Horizon routes
require_once __DIR__ . '/horizon.php';

