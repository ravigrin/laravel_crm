<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class Bitrix24Controller extends IntegrationController
{
    protected $service = AvailableIntegrations::bitrix24->value;
}



