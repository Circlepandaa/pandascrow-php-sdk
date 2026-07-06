<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class ApiException extends \Exception
{
    private ?string $requestId;
    private ?int $statusCode;
    private ?array $responseData;

    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->requestId = $requestId;
        $this->statusCode = $statusCode;
        $this->responseData = $responseData;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    public function __toString(): string
    {
        $message = parent::__toString();
        if ($this->requestId) {
            $message .= "\nRequest ID: {$this->requestId}";
        }
        if ($this->statusCode) {
            $message .= "\nStatus Code: {$this->statusCode}";
        }
        return $message;
    }
}
