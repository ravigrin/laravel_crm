<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\AvailableIntegrations;
use App\Exceptions\IntegrationException;
// use App\Facades\AmoCrmOAuthClient;
use App\Integrations\NotificationSenders\AmoCrmByOauth;
use App\Models\Integration\Credentials;
use App\Models\OAuth\Token;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\OAuth2\Client\Token\AccessToken;

class AmoCrmController extends IntegrationController
{
    protected $service = AvailableIntegrations::amocrm->value;
    protected $amoCrmApiClient;

    public function __construct()
    {
        // $this->amoCrmApiClient = new AmoCrmOAuthClient();
        parent::__construct();
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/amocrm/authlink",
     *     summary="Получение ссылки авторизации AmoCRM",
     *     tags={"Integrations", "AmoCRM"},
     *     @OA\Response(response="200", description="Успешное получение ссылки"),
     * )
     */
    public function getAuthLink()
    {
        // return AmoCrmOAuthClient::getOAuthClient()->getAuthorizeUrl([
        //     'state' => bin2hex(random_bytes(16)),
        //     'mode' => 'post_message',
        // ]);
        return 'Auth link not implemented yet';
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/amocrm/savetoken",
     *     summary="Сохранение токена авторизации AmoCRM",
     *     tags={"Integrations", "AmoCRM"},
     *     @OA\Response(response="200", description="Успешное сохранение токена"),
     * )
     */
    public function saveToken(Request $request)
    {
        // AmoCrmOAuthClient::setAccountBaseDomain($request->query('referer'));

        try {
            // $accessToken = AmoCrmOAuthClient::getOAuthClient()->getAccessTokenByCode($request->query('code'));
            // if (!$accessToken->hasExpired()) {
            //     $token = Token::updateOrCreate(
            //         ['service' => $this->service, 'domain' => AmoCrmOAuthClient::getAccountBaseDomain()],
            //         [
            //             'access_token' => $accessToken->getToken(),
            //             'refresh_token' => $accessToken->getRefreshToken(),
            //             'expires' => Carbon::createFromTimestamp($accessToken->getExpires()),
            //         ]
            //     );
            //     Credentials::create(['token_id', $token->id]);
            // }
        } catch (IntegrationException $exception) {
            \Log::critical($exception->getMessage(), $exception->getTrace());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/integrations/amocrm/ownerdetails",
     *     summary="Получение информации о владельце AmoCRM",
     *     tags={"Integrations", "AmoCRM"},
     *     @OA\Response(response="200", description="Успешное получение данных"),
     * )
     */
    public function getOwnerDetails(Request $request)
    {
        /**@var $token AccessToken**/
        // $token = Token::where('id', $request->query['token_id'])->first()->getAsAccessTokenInstance();

        // if ($token->hasExpired()) {
        //     $newToken = AmoCrmOAuthClient::getOAuthClient()->getAccessTokenByRefreshToken($token);
        //     Token::where('domain', $token->getValues()['domain'])
        //        ->where('service', $this->service)
        //        ->update([
        //            'access_token' => $newToken->getToken(),
        //            'refresh_token' => $newToken->getRefreshToken(),
        //            'expires' => Carbon::createFromTimestamp($newToken->getExpires()),
        //        ]);
        // }
        // return AmoCrmOAuthClient::getOAuthClient()->getResourceOwner($token);
        return 'Owner details not implemented yet';
    }
}
