<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class GetResponseController extends IntegrationController
{
    protected $service = AvailableIntegrations::getResponse->value;
}



