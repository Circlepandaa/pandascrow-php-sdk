<?php

declare(strict_types=1);

namespace Pandascrow\Resources;

use Pandascrow\Auth\Authenticator;
use Pandascrow\Config;
use Pandascrow\Contracts\LoggerInterface;
use Pandascrow\HttpClient\ClientInterface;
use Pandascrow\Utils\NullLogger;

abstract class BaseResource
{
    protected ClientInterface $httpClient;
    protected Authenticator $authenticator;
    protected Config $config;
    protected LoggerInterface $logger;
    protected string $basePath = '';

    public function __construct(
        ClientInterface $httpClient,
        Authenticator $authenticator,
        Config $config,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->authenticator = $authenticator;
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    protected function ensureAuthenticated(): void
    {
        $token = $this->authenticator->getToken();
        if ($token === null || $token->isExpired()) {
            $this->authenticator->authenticate();
        }
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    protected function get(string $path, array $params = [], array $options = []): array
    {
        $this->ensureAuthenticated();
        $uri = $this->buildPath($path);

        if ($params !== []) {
            $uri .= '?' . http_build_query($params);
        }

        return $this->httpClient->get($uri, $options);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    protected function post(string $path, array $data = [], array $options = []): array
    {
        $this->ensureAuthenticated();
        $uri = $this->buildPath($path);
        return $this->httpClient->post($uri, $data, $options);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    protected function put(string $path, array $data = [], array $options = []): array
    {
        $this->ensureAuthenticated();
        $uri = $this->buildPath($path);
        return $this->httpClient->put($uri, $data, $options);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    protected function patch(string $path, array $data = [], array $options = []): array
    {
        $this->ensureAuthenticated();
        $uri = $this->buildPath($path);
        return $this->httpClient->patch($uri, $data, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    protected function delete(string $path, array $options = []): array
    {
        $this->ensureAuthenticated();
        $uri = $this->buildPath($path);
        return $this->httpClient->delete($uri, $options);
    }

    protected function buildPath(string $path): string
    {
        $base = rtrim($this->basePath, '/');
        $path = ltrim($path, '/');
        return $base !== '' ? $base . '/' . $path : $path;
    }

    protected function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * @param array<mixed> $data
     * @param array<string> $required
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * @param string|null $idempotencyKey
     * @return array<string, string>
     */
    protected function getIdempotencyHeaders(?string $idempotencyKey): array
    {
        if ($idempotencyKey !== null) {
            return ['Idempotency-Key' => $idempotencyKey];
        }
        return [];
    }

    /**
     * @param array<mixed> $response
     * @return array{data: array<mixed>, pagination: array{total: mixed|null, page: mixed|null, per_page: mixed|null, total_pages: mixed|null}}
     */
    protected function handlePaginatedResponse(array $response): array
    {
        return [
            'data' => $response['data'] ?? $response['items'] ?? [],
            'pagination' => [
                'total' => $response['total'] ?? null,
                'page' => $response['page'] ?? null,
                'per_page' => $response['per_page'] ?? null,
                'total_pages' => $response['total_pages'] ?? null,
            ],
        ];
    }
}
