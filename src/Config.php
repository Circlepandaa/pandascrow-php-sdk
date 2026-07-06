<?php

declare(strict_types=1);

namespace Pandascrow;

use Pandascrow\Exceptions\ConfigurationException;

class Config
{
    private string $apiKey;
    private ?string $apiSecret = null;
    private string $baseUrl;
    private bool $isSandbox;
    private int $timeout;
    private int $retries;
    private bool $debug;
    private array $headers;
    private ?string $apiVersion;

    /**
     * @param string|array $apiKey API key or configuration array
     * @param array $options Additional options
     */
    public function __construct(string|array $apiKey, array $options = [])
    {
        // Default values
        $this->isSandbox = true;
        $this->timeout = 30;
        $this->retries = 0;
        $this->debug = false;
        $this->headers = [];
        $this->apiVersion = null;

        if (is_array($apiKey)) {
            $this->parseConfigArray($apiKey);
        } else {
            $this->apiKey = $apiKey;
            $this->apiSecret = $options['api_secret'] ?? null;
            $this->isSandbox = $options['sandbox'] ?? true;
            $this->timeout = $options['timeout'] ?? 30;
            $this->retries = $options['retries'] ?? 0;
            $this->debug = $options['debug'] ?? false;
            $this->headers = $options['headers'] ?? [];
            $this->apiVersion = $options['api_version'] ?? null;
        }

        // Set base URL after sandbox is determined
        $this->baseUrl = $options['base_url'] ?? $this->getDefaultBaseUrl();

        $this->validate();
    }

    private function parseConfigArray(array $config): void
    {
        $this->apiKey = $config['api_key'] ?? throw new ConfigurationException('API key is required');
        $this->apiSecret = $config['api_secret'] ?? null;
        $this->isSandbox = $config['sandbox'] ?? true;
        $this->timeout = $config['timeout'] ?? 30;
        $this->retries = $config['retries'] ?? 0;
        $this->debug = $config['debug'] ?? false;
        $this->headers = $config['headers'] ?? [];
        $this->apiVersion = $config['api_version'] ?? null;
        $this->baseUrl = $config['base_url'] ?? $this->getDefaultBaseUrl();
    }

    private function getDefaultBaseUrl(): string
    {
        return $this->isSandbox
            ? 'https://sandbox.pandascrow.io'
            : 'https://api.pandascrow.io';
    }

    private function validate(): void
    {
        if (empty($this->apiKey)) {
            throw new ConfigurationException('API key cannot be empty');
        }

        if (!is_int($this->timeout) || $this->timeout < 1) {
            throw new ConfigurationException('Timeout must be a positive integer');
        }

        if (!is_int($this->retries) || $this->retries < 0) {
            throw new ConfigurationException('Retries must be a non-negative integer');
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getApiSecret(): ?string
    {
        return $this->apiSecret;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
}
