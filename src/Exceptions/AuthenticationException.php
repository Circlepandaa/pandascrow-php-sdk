<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class AuthenticationException extends ApiException
{
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @param string|null $requestId
     * @param int|null $statusCode
     * @param array<mixed>|null $responseData
     */
    public function __construct(
        string $message = 'Authentication failed',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);
    }

    public function isInvalidApiKey(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'invalid api key');
    }

    public function isExpiredToken(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'expired');
    }

    public function isPermissionError(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'permission');
    }
}
