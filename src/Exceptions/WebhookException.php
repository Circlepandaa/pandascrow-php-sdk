<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class WebhookException extends ApiException
{
    public function __construct(
        string $message = 'Webhook error',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);
    }

    /**
     * Check if this is an invalid signature error
     */
    public function isInvalidSignature(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'signature');
    }

    /**
     * Check if this is an expired payload error
     */
    public function isExpiredPayload(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'timestamp');
    }

    /**
     * Check if this is an invalid JSON error
     */
    public function isInvalidJson(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'json');
    }
}
