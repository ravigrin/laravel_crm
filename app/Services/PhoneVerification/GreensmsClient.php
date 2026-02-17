<?php

namespace App\Services\PhoneVerification;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class GreensmsClient
{
    private string $baseUrl;
    private string $login;
    private string $password;
    private HttpFactory $http;

    public function __construct(HttpFactory $http)
    {
        $this->http = $http;
        $this->baseUrl = config('services.greensms.base_url', 'https://api3.greensms.ru');
        $this->login = (string) config('services.greensms.login', '');
        $this->password = (string) config('services.greensms.password', '');
    }

    public function lookup(string $phone): array
    {
        if (!$this->login || !$this->password) {
            Log::channel(config('logging.default'))->warning('Greensms credentials are missing');
            return [];
        }

        $response = $this->http->baseUrl($this->baseUrl)
            ->withBasicAuth($this->login, $this->password)
            ->acceptJson()
            ->post('/lookup/hlr', [
                'to' => $phone,
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }
}

