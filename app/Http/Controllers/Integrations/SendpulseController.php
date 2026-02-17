<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class SendpulseController extends IntegrationController
{
    protected $service = AvailableIntegrations::sendPulse->value;
}



