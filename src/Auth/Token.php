<?php

declare(strict_types=1);

namespace Pandascrow\Auth;

class Token
{
    private string $accessToken;
    private ?string $refreshToken;
    private int $expiresAt;

    public function __construct(string $accessToken, int $expiresIn, ?string $refreshToken = null)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = time() + $expiresIn;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        // Check if token expires in less than 60 seconds
        return time() >= ($this->expiresAt - 60);
    }

    public function getExpiresIn(): int
    {
        return max(0, $this->expiresAt - time());
    }
}
