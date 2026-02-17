<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Horizon Routes
|--------------------------------------------------------------------------
|
| Here are the routes for Laravel Horizon dashboard and API
|
*/

// Horizon dashboard routes
Route::group(['prefix' => 'horizon', 'middleware' => ['web', 'auth:sanctum']], function () {
    Route::get('/', function () {
        return view('horizon::app');
    })->name('horizon.index');
});

// Horizon API routes (internal)
Route::group(['prefix' => 'horizon/api', 'middleware' => ['web']], function () {
    // These routes are handled by Horizon package internally
    // We just need to ensure they're accessible
});

