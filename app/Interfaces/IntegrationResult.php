<?php

namespace App\Interfaces;

class IntegrationResult
{
    protected bool $success;
    protected ?string $message;
    protected ?string $externalId;
    protected array $data;
    protected ?int $httpCode;

    public function __construct(
        bool $success,
        ?string $message = null,
        ?string $externalId = null,
        array $data = [],
        ?int $httpCode = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->externalId = $externalId;
        $this->data = $data;
        $this->httpCode = $httpCode;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'external_id' => $this->externalId,
            'data' => $this->data,
            'http_code' => $this->httpCode,
        ];
    }

    public static function success(string $message = null, string $externalId = null, array $data = []): self
    {
        return new self(true, $message, $externalId, $data);
    }

    public static function failure(string $message, int $httpCode = null, array $data = []): self
    {
        return new self(false, $message, null, $data, $httpCode);
    }
}
