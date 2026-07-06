<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class RateLimitException extends ApiException
{
    private ?int $retryAfter;
    private ?int $limit;
    private ?int $remaining;
    private ?int $reset;

    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @param string|null $requestId
     * @param int|null $statusCode
     * @param array<mixed>|null $responseData
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);

        $this->retryAfter = $responseData['retry_after'] ?? null;
        $this->limit = $responseData['limit'] ?? null;
        $this->remaining = $responseData['remaining'] ?? null;
        $this->reset = $responseData['reset'] ?? null;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    public function getReset(): ?int
    {
        return $this->reset;
    }

    public function getRetryAfterDateTime(): ?\DateTimeInterface
    {
        if ($this->retryAfter === null) {
            return null;
        }
        return (new \DateTime())->add(new \DateInterval('PT' . $this->retryAfter . 'S'));
    }

    public function isApproachingLimit(float $threshold = 0.9): bool
    {
        if ($this->limit === null || $this->remaining === null) {
            return false;
        }
        return ($this->remaining / $this->limit) <= $threshold;
    }

    public function getRecommendedWaitTime(): int
    {
        if ($this->retryAfter !== null) {
            return $this->retryAfter;
        }

        if ($this->reset !== null) {
            return max(1, $this->reset - time());
        }

        return 60;
    }
}
