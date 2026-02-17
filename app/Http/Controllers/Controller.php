<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Documentation",
 *     description="API документация для системы управления лидами и интеграциями"
 * )
 * @OA\Server(
 *     url="{schema}://{host}",
 *     description="API Server",
 *     @OA\ServerVariable(
 *         serverVariable="schema",
 *         enum={"http", "https"},
 *         default="http"
 *     ),
 *     @OA\ServerVariable(
 *         serverVariable="host",
 *         default="localhost"
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $tokenRequiredActions = ['store', 'show', 'update', 'index', 'destroy'];

    public function __construct()
    {
        // Temporarily disabled - JWT package not installed
        // $this->middleware('jwtauth', [
        //     'only' => $this->tokenRequiredActions
        // ]);
    }

    protected function respond($data, $statusCode, $headers = [])
    {
        return response()->json($data, $statusCode, $headers);
    }

    public function respondSuccess($data = [], $code = 200)
    {
        $message = array_merge([
            'status' => 'success'
        ], $data);

        return $this->respond($message, $code);
    }

    public function respondError($data = [], $code = 500)
    {
        $message = array_merge([
            'status' => 'error'
        ], $data);

        return $this->respond($message, $code);
    }
}
