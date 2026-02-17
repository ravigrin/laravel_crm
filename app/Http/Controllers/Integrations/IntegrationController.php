<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;
use App\Exceptions\IntegrationException;
use App\Http\Controllers\Controller;
use App\Http\Validators\IntegrationValidator;
use App\Models\Integration\Credentials;
use App\Models\Integration\EntityCredentials;
use App\Models\Integration\ProjectCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IntegrationController extends Controller
{
    use IntegrationValidator;

    /**
     * @var string
     */
    protected $service;

    /**
     * @var string[]
     */
    protected $tokenRequiredActions = ['addCredentials',
        'updateCredentials', 'getCredentials', 'deleteCredentials', 'testConnection'];

    /**
     * @OA\Post(
     *     path="/api/integrations/{service}/credentials",
     *     summary="Добавление учетных данных для интеграции",
     *     tags={"Integrations"},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         description="Тип сервиса интеграции",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Успешное сохранение учетных данных"),
     *     @OA\Response(response="400", description="Ошибка валидации"),
     *     @OA\Response(response="500", description="Внутренняя ошибка сервера"),
     * )
     */
    public function addCredentials(Request $request)
    {
        try {
            $this->validateSaveCredentials($request);
            $data = $request->all();

            $newCredentials = Credentials::create(
                [
                    'code' => $this->service,
                    'enabled' => 'true',
                    'credentials' => $data['credentials']
                ]
            );

            if (Arr::exists($data, 'external_entity_id')) {
                $newCredentials->entityCredentials()->create([
                    'external_entity_id' => $data['external_entity_id'],
                    'integration_credentials_id' => $newCredentials->id
                ]);
            }

            if (Arr::exists($data, 'external_project_id')) {
                $newCredentials->projectCredentials()->create([
                    'external_project_id' => $data['external_project_id'],
                    'integration_credentials_id' => $newCredentials->id,
                ]);
            }

            return $this->respondSuccess(['message' => 'Credentials saved', 'credentials' => $newCredentials]);
        } catch (IntegrationException $e) {
            \Log::critical($e->getMessage(), $e->getTrace());
            return $this->respondError(['message' => 'Something went wrong']);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/integrations/{service}/credentials",
     *     summary="Обновление учетных данных интеграции",
     *     tags={"Integrations"},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         description="Тип сервиса интеграции",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Успешное обновление данных"),
     *     @OA\Response(response="500", description="Ошибка при обновлении"),
     * )
     */
    public function updateCredentials(Request $request)
    {
        $this->validateUpdateCredentials($request);
        $data = $request->all();
        $credentials = Credentials::where('id', $data['id']);
        unset($data['id']);

        try {
            $credentials->update($data);
            return $this->respondSuccess(['credentials' => $data]);
        } catch (IntegrationException $e) {
            \Log::critical($e->getMessage());
            return $this->respondError(['message' => 'error while updating data']);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/{service}/credentials",
     *     summary="Получение учетных данных интеграции",
     *     tags={"Integrations"},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         description="Тип сервиса интеграции",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function getCredentials(Request $request)
    {
        $this->validateGetCredentials($request);
        $params = $request->all();

        if (Arr::exists($params, 'external_entity_id')) {
            $externalEntityId = $params['external_entity_id'];
            $credentialsByEntityId = EntityCredentials::with('credentials')
                ->where('external_entity_id', $externalEntityId)
                ->get()
                ->pluck('credentials.0')
                ->toArray();
        }

        if (Arr::exists($params, 'external_project_id')) {
            $externalProjectId = $params['external_project_id'];
            $credentialsByProjectId = ProjectCredentials::with('credentials')
                ->where('external_project_id', $externalProjectId)
                ->get()
                ->pluck('credentials.0')
                ->toArray();
        }

        return $this->respondSuccess([
            'credentials_collection' => array_merge($credentialsByEntityId, $credentialsByProjectId)
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/integrations/{service}/credentials",
     *     summary="Удаление учетных данных интеграции",
     *     tags={"Integrations"},
     *     @OA\Parameter(
     *         name="service",
     *         in="path",
     *         required=true,
     *         description="Тип сервиса интеграции",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Успешное удаление"),
     *     @OA\Response(response="500", description="Ошибка при удалении"),
     * )
     */
    public function deleteCredentials(Request $request)
    {
        $this->validateDeleteCredentials($request);
        $data = $request->all();
        try {
            Credentials::whereIn('id', $data['ids'])
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);
            return $this->respondSuccess(['message' => 'Following credentials was deleted', 'ids' => $data['ids']]);
        } catch (IntegrationException $e) {
            \Log::critical($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/available",
     *     summary="Получение списка доступных интеграций",
     *     tags={"Integrations"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function availableIntegrations(Request $request)
    {
        $codes = array_column(AvailableIntegrations::cases(), 'value');
        return $this->respondSuccess(['message' => 'List of allowed integrations', 'integration_codes' => $codes]);
    }
}
