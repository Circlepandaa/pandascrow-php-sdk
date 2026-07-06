<?php

declare(strict_types=1);

namespace Pandascrow\HttpClient;

interface ClientInterface
{
    /**
     * Send a GET request
     */
    public function get(string $uri, array $options = []): array;

    /**
     * Send a POST request
     */
    public function post(string $uri, array $data = [], array $options = []): array;

    /**
     * Send a PUT request
     */
    public function put(string $uri, array $data = [], array $options = []): array;

    /**
     * Send a PATCH request
     */
    public function patch(string $uri, array $data = [], array $options = []): array;

    /**
     * Send a DELETE request
     */
    public function delete(string $uri, array $options = []): array;

    /**
     * Send a custom request
     */
    public function request(string $method, string $uri, array $options = []): array;

    /**
     * Get the last response headers
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
     */
    public function addDefaultHeaders(array $headers): void;
}
