<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Core\Facades\JsonApi;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| CRM Routes
|--------------------------------------------------------------------------
|
| Here are the CRM routes for leads, statuses and exports
|
*/

// JSON:API routes
JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function ($server) {
        // Lead routes
        $server->resource('leads', App\JsonApi\V1\Leads\LeadController::class)
            ->only('index', 'show', 'store', 'update', 'destroy')
            ->middleware('ip_filter');
        
        // Status routes
        $server->resource('statuses', App\JsonApi\V1\Statuses\StatusController::class)
            ->only('index', 'show', 'store', 'update', 'destroy');
    });

// Bulk operations for leads
Route::prefix('v1/leads')->group(function () {
    Route::post('bulk-update-status', [App\JsonApi\V1\Leads\LeadController::class, 'bulkUpdateStatus'])
        ->middleware('ip_filter');
    Route::get('kanban', [App\JsonApi\V1\Leads\LeadController::class, 'getKanban'])
        ->middleware('ip_filter');
    Route::post('{leadId}/resend', [App\JsonApi\V1\Leads\LeadController::class, 'resendLead'])
        ->middleware('ip_filter');
    Route::post('bulk-resend', [App\JsonApi\V1\Leads\LeadController::class, 'bulkResendLeads'])
        ->middleware('ip_filter');
    Route::get('filter-counts', [App\JsonApi\V1\Leads\LeadController::class, 'getFilterCounts'])
        ->middleware('ip_filter');
});

// Status operations
Route::prefix('v1/statuses')->group(function () {
    Route::get('with-counts', [App\JsonApi\V1\Statuses\StatusController::class, 'getStatusesWithCounts']);
});

// Export routes
Route::prefix('export')->group(function () {
    Route::get('export', [App\Http\Controllers\ExportController::class, 'export']);
    Route::get('download', [App\Http\Controllers\ExportController::class, 'download']);
});

/*
|--------------------------------------------------------------------------
| Integration Routes
|--------------------------------------------------------------------------
|
| Here are the integration routes for various services
|
*/

Route::prefix('integrations')->group(function () {
    
    // New integration API routes
    Route::get('types', [App\Http\Controllers\IntegrationController::class, 'getTypes']);
    Route::post('test', [App\Http\Controllers\IntegrationController::class, 'testConnection']);
    Route::post('send', [App\Http\Controllers\IntegrationController::class, 'sendLead']);
    Route::post('update', [App\Http\Controllers\IntegrationController::class, 'updateLead']);
    Route::post('send-batch', [App\Http\Controllers\IntegrationController::class, 'sendLeadBatch']);
    Route::get('{type}/config', [App\Http\Controllers\IntegrationController::class, 'getConfig']);
    Route::get('{type}/form-request', [App\Http\Controllers\IntegrationController::class, 'getFormRequestClass']);
    
    Route::get('available', [App\Http\Controllers\Integrations\IntegrationController::class, 'availableIntegrations']);

    // AmoCRM routes
    Route::prefix('amocrm')->group(function () {
        Route::get('savetoken', [App\Http\Controllers\Integrations\AmoCrmController::class, 'saveToken']);
        Route::get('authlink', [App\Http\Controllers\Integrations\AmoCrmController::class, 'getAuthLink']);
        Route::get('ownerdetails', [App\Http\Controllers\Integrations\AmoCrmController::class, 'getOwnerDetails']);
    });

    // Bitrix24 routes
    Route::prefix('bitrix24')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\Bitrix24Controller::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\Bitrix24Controller::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\Bitrix24Controller::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\Bitrix24Controller::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\Bitrix24Controller::class, 'testConnection']);
    });

    // Email routes
    Route::prefix('email')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\EmailNotificationsController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\EmailNotificationsController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\EmailNotificationsController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\EmailNotificationsController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\EmailNotificationsController::class, 'testConnection']);
    });

    // GetResponse routes
    Route::prefix('getresponse')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\GetResponseController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\GetResponseController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\GetResponseController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\GetResponseController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\GetResponseController::class, 'testConnection']);
    });

    // LpTracker routes
    Route::prefix('lptracker')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\LpTrackerController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\LpTrackerController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\LpTrackerController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\LpTrackerController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\LpTrackerController::class, 'testConnection']);
    });

    // SendPulse routes
    Route::prefix('sendpulse')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\SendpulseController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\SendpulseController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\SendpulseController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\SendpulseController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\SendpulseController::class, 'testConnection']);
    });

    // UniSender routes
    Route::prefix('unisender')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\UnisenderController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\UnisenderController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\UnisenderController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\UnisenderController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\UnisenderController::class, 'testConnection']);
    });

    // UON Travel routes
    Route::prefix('uontravel')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\UonTravelController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\UonTravelController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\UonTravelController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\UonTravelController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\UonTravelController::class, 'testConnection']);
    });

    // Webhooks routes
    Route::prefix('webhooks')->group(function () {
        Route::post('credentials', [App\Http\Controllers\Integrations\WebhooksController::class, 'addCredentials']);
        Route::get('credentials', [App\Http\Controllers\Integrations\WebhooksController::class, 'getCredentials']);
        Route::patch('credentials', [App\Http\Controllers\Integrations\WebhooksController::class, 'updateCredentials']);
        Route::delete('credentials', [App\Http\Controllers\Integrations\WebhooksController::class, 'deleteCredentials']);
        Route::post('test', [App\Http\Controllers\Integrations\WebhooksController::class, 'testConnection']);
    });

    // Telegram routes
    Route::prefix('telegram')->group(function () {
        Route::get('link', [App\Http\Controllers\Integrations\TelegramController::class, 'getLink']);
        Route::post('updates', [App\Http\Controllers\Integrations\TelegramController::class, 'setUpdates']);
    });

    // Zapier routes
    Route::prefix('zapier')->group(function () {
        Route::get('me', [App\Http\Controllers\Integrations\ZapierController::class, 'me']);
        Route::post('subscribe', [App\Http\Controllers\Integrations\ZapierController::class, 'subscribe']);
        Route::delete('unsubscribe', [App\Http\Controllers\Integrations\ZapierController::class, 'unsubscribe']);
        Route::get('index', [App\Http\Controllers\Integrations\ZapierController::class, 'index']);
        Route::get('apikey', [App\Http\Controllers\Integrations\ZapierController::class, 'generateKey']);
    });
});

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| Here are the settings routes for email templates and maintenance
|
*/

Route::prefix('settings')->group(function () {
    
    // Email template routes
    Route::prefix('email')->group(function () {
        Route::post('template', [App\Http\Controllers\Settings\EmailTemplateController::class, 'store']);
        Route::get('template', [App\Http\Controllers\Settings\EmailTemplateController::class, 'show']);
        Route::delete('template', [App\Http\Controllers\Settings\EmailTemplateController::class, 'destroy']);
        Route::patch('template', [App\Http\Controllers\Settings\EmailTemplateController::class, 'update']);
    });
});

// Maintenance route
Route::get('test', [App\Http\Controllers\Settings\MaintenanceController::class, 'test']);



