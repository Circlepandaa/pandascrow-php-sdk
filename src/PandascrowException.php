<?php

namespace Pandascrow;

/**
 * Thrown for every failed call: network errors, invalid responses, and any
 * response where Pandascrow returns `status: false` or an HTTP error.
 */
class PandascrowException extends \RuntimeException
{
    /** @var int HTTP status code (0 for network errors) */
    public $httpStatus;

    /** @var string|null Link to the relevant API reference page, when Pandascrow sends one */
    public $docUrl;

    /** @var array<string, mixed>|null Full decoded response body, when there was one */
    public $response;

    /**
     * @param array<string, mixed>|null $response
     */
    public function __construct(string $message, int $httpStatus = 0, ?string $docUrl = null, ?array $response = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = $httpStatus;
        $this->docUrl     = ($docUrl === '') ? null : $docUrl;
        $this->response   = $response;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getDocUrl(): ?string
    {
        return $this->docUrl;
    }

    /** @return array<string, mixed>|null */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}
