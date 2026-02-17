<?php

namespace App\Http\Controllers\Settings;

use App\Enums\DefaultStatuses;
use App\Http\Controllers\Controller;
use App\Models\Integration\EntityCredentials;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class MaintenanceController extends Controller {

    /**
     * @OA\Get(
     *     path="/api/test",
     *     summary="Тестовый эндпоинт для обслуживания",
     *     tags={"Settings", "Maintenance"},
     *     @OA\Response(response="200", description="Успешный ответ"),
     * )
     */
    public function test()
    {
        dd(array_flip(array_column(DefaultStatuses::cases(), 'value', 'name')));
    }
}



