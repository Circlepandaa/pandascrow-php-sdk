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
    /** @var array<string, string> */
    private array $headers;
    private ?string $apiVersion;

    /**
     * @param string|array{api_key?: string, api_secret?: string, sandbox?: bool, timeout?: int, retries?: int, debug?: bool, headers?: array<string, string>, api_version?: string, base_url?: string} $apiKey
     * @param array{sandbox?: bool, timeout?: int, retries?: int, debug?: bool, headers?: array<string, string>, api_version?: string, base_url?: string, api_secret?: string} $options
     */
    public function __construct(string|array $apiKey, array $options = [])
    {
        $this->isSandbox = true;
        $this->timeout = 30;
        $this->retries = 0;
        $this->debug = false;
        $this->headers = [];
        $this->apiVersion = null;

        if (is_array($apiKey)) {
            // Check for api_key before parsing
            if (!isset($apiKey['api_key']) || $apiKey['api_key'] === '') {
                throw new ConfigurationException('API key is required');
            }
            /** @var array{api_key: string, api_secret?: string, sandbox?: bool, timeout?: int, retries?: int, debug?: bool, headers?: array<string, string>, api_version?: string, base_url?: string} $apiKey */
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

        $this->baseUrl = $options['base_url'] ?? $this->getDefaultBaseUrl();
        $this->validate();
    }

    /**
     * @param array{api_key: string, api_secret?: string, sandbox?: bool, timeout?: int, retries?: int, debug?: bool, headers?: array<string, string>, api_version?: string, base_url?: string} $config
     */
    private function parseConfigArray(array $config): void
    {
        $this->apiKey = $config['api_key'];
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
        if ($this->apiKey === '') {
            throw new ConfigurationException('API key cannot be empty');
        }

        if ($this->timeout < 1) {
            throw new ConfigurationException('Timeout must be a positive integer');
        }

        if ($this->retries < 0) {
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

    /**
     * @return array<string, string>
     */
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
