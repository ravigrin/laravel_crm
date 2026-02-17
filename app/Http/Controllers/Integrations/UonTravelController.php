<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class UonTravelController extends IntegrationController
{
    protected $service = AvailableIntegrations::uonTravel->value;
}



