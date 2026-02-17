<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class EmailNotificationsController extends IntegrationController
{
    protected $service = AvailableIntegrations::email->value;
}



