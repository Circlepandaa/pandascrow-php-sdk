<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class WebhookException extends ApiException
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
        string $message = 'Webhook error',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);
    }

    public function isInvalidSignature(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'signature');
    }

    public function isExpiredPayload(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'timestamp');
    }

    public function isInvalidJson(): bool
    {
        return str_contains(strtolower($this->getMessage()), 'json');
    }
}
