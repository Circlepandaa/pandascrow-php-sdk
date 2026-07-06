<?php

declare(strict_types=1);

namespace Pandascrow\Resources;

use Pandascrow\Contracts\LoggerInterface;
use Pandascrow\Exceptions\WebhookException;
use Pandascrow\Utils\SignatureValidator;

class Webhook extends BaseResource
{
    private SignatureValidator $signatureValidator;

    public function __construct(
        \Pandascrow\HttpClient\ClientInterface $httpClient,
        \Pandascrow\Auth\Authenticator $authenticator,
        \Pandascrow\Config $config,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($httpClient, $authenticator, $config, $logger);
        $this->setBasePath('/webhooks');
        $this->signatureValidator = new SignatureValidator();
    }

    /**
     * Register a new webhook endpoint
     * 
     * @param array $data Webhook data (url, events, etc.)
     * @return array Webhook registration response
     */
    public function register(array $data): array
    {
        $this->validateRequired($data, ['url', 'events']);
        return $this->post('', $data);
    }

    /**
     * Get webhook details by ID
     * 
     * @param string $webhookId Webhook ID
     * @return array Webhook details
     */
    public function getWebhook(string $webhookId): array
    {
        return $this->get('/' . $webhookId);
    }

    /**
     * List all webhook endpoints
     * 
     * @param array $filters Filter parameters
     * @return array List of webhooks
     */
    public function listWebhooks(array $filters = []): array
    {
        return $this->get('', $filters);
    }

    /**
     * Update a webhook endpoint
     * 
     * @param string $webhookId Webhook ID
     * @param array $data Update data
     * @return array Updated webhook
     */
    public function updateWebhook(string $webhookId, array $data): array
    {
        return $this->put('/' . $webhookId, $data);
    }

    /**
     * Delete a webhook endpoint
     * 
     * @param string $webhookId Webhook ID
     * @return array Deletion response
     */
    public function deleteWebhook(string $webhookId): array
    {
        return $this->delete('/' . $webhookId);
    }

    /**
     * Get webhook events
     * 
     * @param string $webhookId Webhook ID
     * @param array $filters Filter parameters
     * @return array List of webhook events
     */
    public function getEvents(string $webhookId, array $filters = []): array
    {
        return $this->get('/' . $webhookId . '/events', $filters);
    }

    /**
     * Get webhook delivery status
     * 
     * @param string $eventId Event ID
     * @return array Delivery status
     */
    public function getDeliveryStatus(string $eventId): array
    {
        return $this->get('/delivery/' . $eventId);
    }

    /**
     * Resend a failed webhook event
     * 
     * @param string $eventId Event ID to resend
     * @return array Resend response
     */
    public function resend(string $eventId): array
    {
        return $this->post('/' . $eventId . '/resend');
    }

    /**
     * Parse and verify incoming webhook payload
     * 
     * @param string $payload Raw webhook payload (JSON string)
     * @param string $signature Signature header value
     * @param string $secret Webhook secret
     * @param int $tolerance Tolerance in seconds for timestamp validation
     * @return array Parsed webhook data
     * @throws WebhookException
     */
    public function parse(
        string $payload,
        string $signature,
        string $secret,
        int $tolerance = 300
    ): array {
        // Validate signature
        if (!$this->signatureValidator->validate($payload, $signature, $secret, $tolerance)) {
            throw new WebhookException('Invalid webhook signature');
        }

        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new WebhookException('Invalid JSON payload: ' . json_last_error_msg());
        }

        $this->logger->info('Webhook received', [
            'event' => $data['event'] ?? 'unknown',
            'id' => $data['id'] ?? null,
        ]);

        return $data;
    }

    /**
     * Validate webhook payload without throwing exception
     * 
     * @param string $payload Raw webhook payload
     * @param string $signature Signature header value
     * @param string $secret Webhook secret
     * @return bool True if valid
     */
    public function validateWebhook(
        string $payload,
        string $signature,
        string $secret
    ): bool {
        try {
            return $this->signatureValidator->validate($payload, $signature, $secret);
        } catch (\Exception $e) {
            $this->logger->warning('Webhook validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Test webhook endpoint
     * 
     * @param string $webhookId Webhook ID
     * @param array $testEvent Test event data
     * @return array Test response
     */
    public function test(string $webhookId, array $testEvent = []): array
    {
        return $this->post('/' . $webhookId . '/test', $testEvent);
    }

    /**
     * Get webhook statistics
     * 
     * @param string $webhookId Webhook ID
     * @param array $filters Date range and other filters
     * @return array Statistics data
     */
    public function getStatistics(string $webhookId, array $filters = []): array
    {
        return $this->get('/' . $webhookId . '/statistics', $filters);
    }

    /**
     * Pause webhook delivery
     * 
     * @param string $webhookId Webhook ID
     * @return array Update response
     */
    public function pause(string $webhookId): array
    {
        return $this->post('/' . $webhookId . '/pause');
    }

    /**
     * Resume webhook delivery
     * 
     * @param string $webhookId Webhook ID
     * @return array Update response
     */
    public function resume(string $webhookId): array
    {
        return $this->post('/' . $webhookId . '/resume');
    }
}
