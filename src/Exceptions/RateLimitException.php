<?php

declare(strict_types=1);

namespace Pandascrow\Exceptions;

class RateLimitException extends ApiException
{
    private ?int $retryAfter;
    private ?int $limit;
    private ?int $remaining;
    private ?int $reset;

    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $requestId = null,
        ?int $statusCode = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous, $requestId, $statusCode, $responseData);

        // Extract rate limit information from response headers or data
        $this->retryAfter = $responseData['retry_after'] ?? null;
        $this->limit = $responseData['limit'] ?? null;
        $this->remaining = $responseData['remaining'] ?? null;
        $this->reset = $responseData['reset'] ?? null;
    }

    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get rate limit
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Get remaining requests
     */
    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    /**
     * Get reset timestamp
     */
    public function getReset(): ?int
    {
        return $this->reset;
    }

    /**
     * Get retry after as DateTime
     */
    public function getRetryAfterDateTime(): ?\DateTimeInterface
    {
        if ($this->retryAfter === null) {
            return null;
        }
        return (new \DateTime())->add(new \DateInterval('PT' . $this->retryAfter . 'S'));
    }

    /**
     * Check if rate limit is about to be exceeded
     */
    public function isApproachingLimit(float $threshold = 0.9): bool
    {
        if ($this->limit === null || $this->remaining === null) {
            return false;
        }
        return ($this->remaining / $this->limit) <= $threshold;
    }

    /**
     * Get wait time recommendation
     */
    public function getRecommendedWaitTime(): int
    {
        if ($this->retryAfter !== null) {
            return $this->retryAfter;
        }

        if ($this->reset !== null) {
            return max(1, $this->reset - time());
        }

        return 60; // Default to 60 seconds
    }
}
