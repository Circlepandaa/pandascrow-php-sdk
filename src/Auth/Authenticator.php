<?php

declare(strict_types=1);

namespace Pandascrow\Auth;

use Pandascrow\Config;
use Pandascrow\Contracts\LoggerInterface;
use Pandascrow\Exceptions\AuthenticationException;
use Pandascrow\HttpClient\ClientInterface;
use Pandascrow\Utils\NullLogger;

class Authenticator
{
    private Config $config;
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private ?Token $token = null;
    private ?string $apiKey = null;

    public function __construct(
        Config $config,
        ClientInterface $httpClient,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
        $this->apiKey = $config->getApiKey();
    }

    public function authenticate(): Token
    {
        if ($this->token !== null && !$this->token->isExpired()) {
            return $this->token;
        }

        if ($this->config->getApiSecret() !== null) {
            return $this->authenticateWithOAuth();
        }

        return $this->authenticateWithApiKey();
    }

    private function authenticateWithApiKey(): Token
    {
        $this->logger->debug('Authenticating with API key');

        if ($this->apiKey !== null) {
            $this->httpClient->addDefaultHeaders([
                'X-API-Key' => $this->apiKey,
            ]);
        }

        $this->token = new Token($this->apiKey ?? '', 0);
        return $this->token;
    }

    private function authenticateWithOAuth(): Token
    {
        $this->logger->debug('Authenticating with OAuth2 client credentials');

        try {
            $response = $this->httpClient->post('/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->config->getApiSecret(),
            ]);

            if (!isset($response['access_token'])) {
                throw new AuthenticationException('No access token in response');
            }

            $this->token = new Token(
                (string) $response['access_token'],
                (int) ($response['expires_in'] ?? 3600),
                $response['refresh_token'] ?? null
            );

            $this->httpClient->setAuthToken($this->token->getAccessToken());
            return $this->token;
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException(
                'OAuth authentication failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function refresh(): Token
    {
        if ($this->token === null || $this->token->getRefreshToken() === null) {
            throw new AuthenticationException('No refresh token available');
        }

        $this->logger->debug('Refreshing authentication token');

        try {
            $response = $this->httpClient->post('/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->token->getRefreshToken(),
                'client_id' => $this->apiKey,
                'client_secret' => $this->config->getApiSecret(),
            ]);

            if (!isset($response['access_token'])) {
                throw new AuthenticationException('No access token in refresh response');
            }

            $this->token = new Token(
                (string) $response['access_token'],
                (int) ($response['expires_in'] ?? 3600),
                $response['refresh_token'] ?? $this->token->getRefreshToken()
            );

            $this->httpClient->setAuthToken($this->token->getAccessToken());
            return $this->token;
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException(
                'Token refresh failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function clear(): void
    {
        $this->token = null;
        $this->httpClient->clearAuthToken();
    }
}
