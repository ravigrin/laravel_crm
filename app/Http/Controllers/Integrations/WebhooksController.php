<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class WebhooksController extends IntegrationController
{
    protected $service = AvailableIntegrations::webhooks->value;
}



