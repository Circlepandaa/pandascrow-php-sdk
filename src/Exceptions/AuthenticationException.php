<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class AuthenticationException extends ApiException
{
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

    /**
     * Check if this is an invalid API key error
     */
    public function isInvalidApiKey(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'invalid api key');
    }

    /**
     * Check if this is an expired token error
     */
    public function isExpiredToken(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'expired');
    }

    /**
     * Check if this is a permission error
     */
    public function isPermissionError(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'permission');
    }
}
