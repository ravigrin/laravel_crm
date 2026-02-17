<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AmoCrmOAuthClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'amocrm_client';
    }
}
