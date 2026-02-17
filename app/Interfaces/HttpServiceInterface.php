<?php

namespace App\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface HttpServiceInterface
{
    /**
     * Send POST request
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param bool $httpErrors
     * @return ResponseInterface
     */
    public function post(string $uri, array $data = [], array $headers = [], bool $httpErrors = false): ResponseInterface;

    /**
     * Send GET request
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param bool $httpErrors
     * @return ResponseInterface
     */
    public function get(string $uri, array $data = [], array $headers = [], bool $httpErrors = false): ResponseInterface;

    /**
     * Get response body as array
     *
     * @param ResponseInterface $response
     * @return array
     */
    public function getResponseBody(ResponseInterface $response): array;
}
