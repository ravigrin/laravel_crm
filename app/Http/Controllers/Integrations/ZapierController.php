<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;

class ZapierController extends IntegrationController
{
    protected $service = AvailableIntegrations::webhooks->value; // Using webhooks for Zapier

    /**
     * @OA\Get(
     *     path="/api/integrations/zapier/me",
     *     summary="Получение информации о текущем пользователе Zapier",
     *     tags={"Integrations", "Zapier"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function me()
    {
        // Implementation pending
    }

    /**
     * @OA\Post(
     *     path="/api/integrations/zapier/subscribe",
     *     summary="Подписка на Zapier",
     *     tags={"Integrations", "Zapier"},
     *     @OA\Response(response="200", description="Успешная подписка"),
     * )
     */
    public function subscribe()
    {
        // Implementation pending
    }

    /**
     * @OA\Delete(
     *     path="/api/integrations/zapier/unsubscribe",
     *     summary="Отписка от Zapier",
     *     tags={"Integrations", "Zapier"},
     *     @OA\Response(response="200", description="Успешная отписка"),
     * )
     */
    public function unsubscribe()
    {
        // Implementation pending
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/zapier/index",
     *     summary="Получение списка данных для Zapier",
     *     tags={"Integrations", "Zapier"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function index()
    {
        // Implementation pending
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/zapier/apikey",
     *     summary="Генерация API ключа для Zapier",
     *     tags={"Integrations", "Zapier"},
     *     @OA\Response(response="200", description="Успешная генерация ключа"),
     * )
     */
    public function generateKey()
    {
        // Implementation pending
    }
}



