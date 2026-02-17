<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

//@todo make it for our admin page

class OAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/settings/oauth",
     *     summary="Создание OAuth настроек",
     *     tags={"Settings", "OAuth"},
     *     @OA\Response(response="200", description="Успешное создание"),
     * )
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * @OA\Patch(
     *     path="/api/settings/oauth",
     *     summary="Обновление OAuth настроек",
     *     tags={"Settings", "OAuth"},
     *     @OA\Response(response="200", description="Успешное обновление"),
     * )
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * @OA\Delete(
     *     path="/api/settings/oauth",
     *     summary="Удаление OAuth настроек",
     *     tags={"Settings", "OAuth"},
     *     @OA\Response(response="200", description="Успешное удаление"),
     * )
     */
    public function destroy(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/settings/oauth",
     *     summary="Получение OAuth настроек",
     *     tags={"Settings", "OAuth"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function show(Request $request)
    {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/settings/oauth/list",
     *     summary="Получение списка OAuth настроек",
     *     tags={"Settings", "OAuth"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function index(Request $request)
    {
        //
    }
}



