<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class UnisenderController extends IntegrationController
{
    protected $service = AvailableIntegrations::uniSender->value;
}



