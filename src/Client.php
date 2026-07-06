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

    // Resources
    private ?Payment $payment = null;
    private ?Transfer $transfer = null;
    private ?Verification $verification = null;
    private ?Webhook $webhook = null;

    /**
     * @param string|array $apiKey API key or configuration array
     * @param array $options Additional options
     */
    public function __construct(string|array $apiKey, array $options = [])
    {
        $this->config = new Config($apiKey, $options);
        $this->logger = $options['logger'] ?? new NullLogger();

        $this->httpClient = new GuzzleAdapter($this->config, $this->logger);
        $this->authenticator = new Authenticator($this->config, $this->httpClient, $this->logger);
    }

    /**
     * Get Payment resource
     */
    public function payments(): Payment
    {
        if ($this->payment === null) {
            $this->payment = new Payment($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->payment;
    }

    /**
     * Get Transfer resource
     */
    public function transfers(): Transfer
    {
        if ($this->transfer === null) {
            $this->transfer = new Transfer($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->transfer;
    }

    /**
     * Get Verification resource
     */
    public function verifications(): Verification
    {
        if ($this->verification === null) {
            $this->verification = new Verification($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->verification;
    }

    /**
     * Get Webhook resource
     */
    public function webhooks(): Webhook
    {
        if ($this->webhook === null) {
            $this->webhook = new Webhook($this->httpClient, $this->authenticator, $this->config, $this->logger);
        }
        return $this->webhook;
    }

    /**
     * Set logger
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Enable debug mode
     */
    public function enableDebug(): self
    {
        $this->config->setDebug(true);
        return $this;
    }

    /**
     * Get configuration
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
