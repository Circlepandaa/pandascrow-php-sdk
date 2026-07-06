<?php

declare(strict_types=1);

namespace Pandascrow\HttpClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Pandascrow\Config;
use Pandascrow\Contracts\LoggerInterface;
use Pandascrow\Exceptions\ApiException;
use Pandascrow\Exceptions\AuthenticationException;
use Pandascrow\Exceptions\RateLimitException;
use Pandascrow\Exceptions\ValidationException;
use Pandascrow\Utils\NullLogger;

class GuzzleAdapter implements ClientInterface
{
    private GuzzleClient $client;
    private Config $config;
    private LoggerInterface $logger;
    private array $lastHeaders = [];
    private int $lastStatusCode = 0;
    private ?string $authToken = null;
    private array $defaultHeaders = [];

    public function __construct(Config $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();

        $handlerStack = HandlerStack::create();

        if ($config->getRetries() > 0) {
            $handlerStack->push(
                Middleware::retry($this->retryDecider(), $this->retryDelay())
            );
        }

        $this->client = new GuzzleClient([
            'base_uri' => $config->getBaseUrl(),
            'timeout' => $config->getTimeout(),
            'handler' => $handlerStack,
            'http_errors' => false,
            'verify' => true,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Pandascrow-PHP-SDK/1.0',
            ],
        ]);

        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($config->getApiVersion()) {
            $this->defaultHeaders['X-API-Version'] = $config->getApiVersion();
        }
    }

    /**
     * Retry decider for middleware
     */
    private function retryDecider(): callable
    {
        return function (
            int $retries,
            Request $request,
            ?Response $response = null,
            ?RequestException $exception = null
        ): bool {
            if ($retries >= $this->config->getRetries()) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                $this->logger->debug('Retrying due to connection error');
                return true;
            }

            if ($response) {
                $statusCode = $response->getStatusCode();
                if ($statusCode >= 500 || $statusCode === 429) {
                    $this->logger->debug('Retrying due to status code', ['code' => $statusCode]);
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * Retry delay with exponential backoff
     */
    private function retryDelay(): callable
    {
        return function (int $retries): int {
            return (int) pow(2, $retries) * 100;
        };
    }

    public function get(string $uri, array $options = []): array
    {
        return $this->request('GET', $uri, $options);
    }

    public function post(string $uri, array $data = [], array $options = []): array
    {
        $options['json'] = $data;
        return $this->request('POST', $uri, $options);
    }

    public function put(string $uri, array $data = [], array $options = []): array
    {
        $options['json'] = $data;
        return $this->request('PUT', $uri, $options);
    }

    public function patch(string $uri, array $data = [], array $options = []): array
    {
        $options['json'] = $data;
        return $this->request('PATCH', $uri, $options);
    }

    public function delete(string $uri, array $options = []): array
    {
        return $this->request('DELETE', $uri, $options);
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        $headers = array_merge($this->defaultHeaders, $options['headers'] ?? []);

        if ($this->authToken) {
            $headers['Authorization'] = 'Bearer ' . $this->authToken;
        }

        $options['headers'] = $headers;

        if ($this->config->isDebug()) {
            $this->logger->debug('API Request', [
                'method' => $method,
                'uri' => $uri,
                'headers' => $this->sanitizeHeaders($headers),
                'body' => $options['json'] ?? $options['body'] ?? null,
            ]);
        }

        $startTime = microtime(true);

        try {
            $response = $this->client->request($method, $uri, $options);

            $this->lastStatusCode = $response->getStatusCode();
            $this->lastHeaders = $response->getHeaders();

            $responseBody = (string) $response->getBody();
            $data = json_decode($responseBody, true);

            if ($this->config->isDebug()) {
                $this->logger->debug('API Response', [
                    'status' => $this->lastStatusCode,
                    'time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
                    'headers' => $this->sanitizeHeaders($this->lastHeaders),
                    'body' => $data,
                ]);
            }

            if ($this->lastStatusCode >= 400) {
                $this->handleErrorResponse($data, $this->lastStatusCode);
            }

            return $data ?? [];
        } catch (GuzzleException $e) {
            $this->logger->error('HTTP Request failed', [
                'message' => $e->getMessage(),
                'uri' => $uri,
                'method' => $method,
            ]);

            throw new ApiException(
                'Request failed: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Handle error response based on status code
     */
    private function handleErrorResponse(?array $data, int $statusCode): void
    {
        $message = $data['message'] ?? $data['error'] ?? 'Unknown error occurred';
        $requestId = $data['request_id'] ?? $data['id'] ?? null;

        $exception = match ($statusCode) {
            400 => new ValidationException($message, $statusCode, null, $requestId, $statusCode, $data),
            401, 403 => new AuthenticationException($message, $statusCode, null, $requestId, $statusCode, $data),
            429 => new RateLimitException($message, $statusCode, null, $requestId, $statusCode, $data),
            default => new ApiException($message, $statusCode, null, $requestId, $statusCode, $data),
        };

        throw $exception;
    }

    /**
     * Sanitize headers for logging (remove sensitive data)
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'x-api-key', 'cookie'];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $headers[$key] = '***REDACTED***';
            }
        }
        return $headers;
    }

    public function getLastHeaders(): array
    {
        return $this->lastHeaders;
    }

    public function getLastStatusCode(): int
    {
        return $this->lastStatusCode;
    }

    public function setAuthToken(string $token): void
    {
        $this->authToken = $token;
    }

    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    public function clearAuthToken(): void
    {
        $this->authToken = null;
    }

    public function addDefaultHeaders(array $headers): void
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
    }
}
