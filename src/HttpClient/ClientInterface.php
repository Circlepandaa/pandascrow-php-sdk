<?php

declare(strict_types=1);

namespace Pandascrow\HttpClient;

interface ClientInterface
{
    /**
     * Send a GET request
     *
     * @param string $uri
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    public function get(string $uri, array $options = []): array;

    /**
     * Send a POST request
     *
     * @param string $uri
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    public function post(string $uri, array $data = [], array $options = []): array;

    /**
     * Send a PUT request
     *
     * @param string $uri
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    public function put(string $uri, array $data = [], array $options = []): array;

    /**
     * Send a PATCH request
     *
     * @param string $uri
     * @param array<mixed> $data
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    public function patch(string $uri, array $data = [], array $options = []): array;

    /**
     * Send a DELETE request
     *
     * @param string $uri
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    public function delete(string $uri, array $options = []): array;

    /**
     * Send a custom request
     *
     * @param string $method
     * @param string $uri
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    public function request(string $method, string $uri, array $options = []): array;

    /**
     * Get the last response headers
     *
     * @return array<string, string>
     */
    public function getLastHeaders(): array;

    /**
     * Get the last status code
     */
    public function getLastStatusCode(): int;

    /**
     * Set authentication token
     */
    public function setAuthToken(string $token): void;

    /**
     * Get authentication token
     */
    public function getAuthToken(): ?string;

    /**
     * Clear authentication token
     */
    public function clearAuthToken(): void;

    /**
     * Add default headers
     *
     * @param array<string, string> $headers
     */
    public function addDefaultHeaders(array $headers): void;
}
