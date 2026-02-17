<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;
use Illuminate\Http\Request;

class TelegramController extends IntegrationController
{
    protected $service = AvailableIntegrations::telegram->value;

    /**
     * @OA\Get(
     *     path="/api/integrations/telegram/link",
     *     summary="Получение ссылки для подключения Telegram",
     *     tags={"Integrations", "Telegram"},
     *     @OA\Response(response="200", description="Успешное получение ссылки"),
     * )
     */
    public function getLink(Request $request)
    {
        return $this->respondSuccess(['message' => 'Telegram link not implemented yet']);
    }

    /**
     * @OA\Post(
     *     path="/api/integrations/telegram/updates",
     *     summary="Настройка обновлений Telegram",
     *     tags={"Integrations", "Telegram"},
     *     @OA\Response(response="200", description="Успешная настройка"),
     * )
     */
    public function setUpdates(Request $request)
    {
        return $this->respondSuccess(['message' => 'Telegram updates not implemented yet']);
    }
}



