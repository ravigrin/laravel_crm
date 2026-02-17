<?php

namespace App\Services;

use App\Exceptions\ClientException;
use App\Interfaces\HttpServiceInterface;
use Illuminate\Support\Facades\Http as LaravelHttp;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class HttpService implements HttpServiceInterface
{
    protected string $baseUrl;

    public function __construct(string $baseUrl)
    {
        if (empty($baseUrl) || !is_string($baseUrl)) {
            throw new ClientException('Base URL is required and must be a string');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send POST request
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param bool $httpErrors
     * @return ResponseInterface
     */
    public function post(string $uri, array $data = [], array $headers = [], bool $httpErrors = false): ResponseInterface
    {
        $response = $this->client()
            ->withHeaders($headers)
            ->post($this->buildUrl($uri), $data);

        $this->logRequest('POST', $uri, $data, $response);

        return $response;
    }

    /**
     * Send GET request
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param bool $httpErrors
     * @return ResponseInterface
     */
    public function get(string $uri, array $data = [], array $headers = [], bool $httpErrors = false): ResponseInterface
    {
        $response = $this->client()
            ->withHeaders($headers)
            ->get($this->buildUrl($uri), $data);

        $this->logRequest('GET', $uri, $data, $response);

        return $response;
    }

    /**
     * Get response body as array
     *
     * @param ResponseInterface $response
     * @return array
     */
    public function getResponseBody(ResponseInterface $response): array
    {
        return $response->json() ?? [];
    }

    /**
     * Create HTTP client with configuration
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function client()
    {
        $timeout = (float) config('http.timeout', 30);
        $connectTimeout = (float) config('http.connect_timeout', 10);
        $retryTimes = (int) config('http.retry.times', 0);
        $retrySleep = (int) config('http.retry.sleep_ms', 1000);

        $client = LaravelHttp::timeout($timeout)
            ->connectTimeout($connectTimeout);

        if ($retryTimes > 0) {
            $client = $client->retry($retryTimes, $retrySleep);
        }

        return $client;
    }

    /**
     * Build absolute URL
     *
     * @param string $uri
     * @return string
     */
    protected function buildUrl(string $uri): string
    {
        $uri = ltrim($uri, '/');
        return $this->baseUrl . '/' . $uri;
    }

    /**
     * Log HTTP request
     *
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param ResponseInterface $response
     */
    protected function logRequest(string $method, string $uri, array $data, ResponseInterface $response): void
    {
        $logChannel = config('http.log_channel');
        if (!$logChannel) {
            return;
        }

        Log::channel($logChannel)->debug('HTTP request', [
            'method' => $method,
            'url' => $this->buildUrl($uri),
            'data' => $data,
            'status' => $response->getStatusCode(),
            'response_size' => strlen($response->getBody()->getContents())
        ]);
    }
}
