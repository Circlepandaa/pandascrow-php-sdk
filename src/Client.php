<?php

declare(strict_types=1);

namespace Pandascrow;

use Pandascrow\Auth\Authenticator;
use Pandascrow\Contracts\LoggerInterface;
use Pandascrow\Exceptions\ConfigurationException;
use Pandascrow\HttpClient\ClientInterface;
use Pandascrow\HttpClient\GuzzleAdapter;
use Pandascrow\Resources\Payment;
use Pandascrow\Resources\Transfer;
use Pandascrow\Resources\Verification;
use Pandascrow\Resources\Webhook;
use Pandascrow\Utils\NullLogger;

class Client
{
    private Config $config;
    private ClientInterface $httpClient;
    private Authenticator $authenticator;
    private LoggerInterface $logger;

    private ?Payment $payment = null;
    private ?Transfer $transfer = null;
    private ?Verification $verification = null;
    private ?Webhook $webhook = null;

    /**
     * @param string|array{api_key: string, api_secret?: string, sandbox?: bool, timeout?: int, retries?: int, debug?: bool, headers?: array<string, string>, api_version?: string, base_url?: string, logger?: LoggerInterface} $apiKey
     * @param array{sandbox?: bool, timeout?: int, retries?: int, debug?: bool, headers?: array<string, string>, api_version?: string, base_url?: string, api_secret?: string, logger?: LoggerInterface} $options
     */
    public function __construct(string|array $apiKey, array $options = [])
    {
        $this->config = new Config($apiKey, $options);
        $this->logger = $options['logger'] ?? new NullLogger();

        $this->httpClient = new GuzzleAdapter($this->config, $this->logger);
        $this->authenticator = new Authenticator($this->config, $this->httpClient, $this->logger);
    }

    public function payments(): Payment
    {
        if ($this->payment === null) {
            $this->payment = new Payment($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->payment;
    }

    public function transfers(): Transfer
    {
        if ($this->transfer === null) {
            $this->transfer = new Transfer($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->transfer;
    }

    public function verifications(): Verification
    {
        if ($this->verification === null) {
            $this->verification = new Verification($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->verification;
    }

    public function webhooks(): Webhook
    {
        if ($this->webhook === null) {
            $this->webhook = new Webhook($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->webhook;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function enableDebug(): self
    {
        $this->config->setDebug(true);
        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
