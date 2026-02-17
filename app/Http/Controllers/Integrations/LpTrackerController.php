<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class LpTrackerController extends IntegrationController
{
    protected $service = AvailableIntegrations::lptracker->value;
}



