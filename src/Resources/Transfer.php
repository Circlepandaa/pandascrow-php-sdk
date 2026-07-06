<?php

declare(strict_types=1);

namespace Pandascrow\Resources;

use Pandascrow\Contracts\LoggerInterface;

class Transfer extends BaseResource
{
    public function __construct(
        \Pandascrow\HttpClient\ClientInterface $httpClient,
        \Pandascrow\Auth\Authenticator $authenticator,
        \Pandascrow\Config $config,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($httpClient, $authenticator, $config, $logger);
        $this->setBasePath('/transfers');
    }

    /**
     * Initiate a transfer
     * 
     * @param array $data Transfer data (amount, recipient, currency, etc.)
     * @param string|null $idempotencyKey Idempotency key
     * @return array Transfer response
     */
    public function initiate(array $data, ?string $idempotencyKey = null): array
    {
        $this->validateRequired($data, ['amount', 'recipient', 'currency']);

        $options = [];
        if ($idempotencyKey) {
            $options['headers'] = $this->getIdempotencyHeaders($idempotencyKey);
        }

        return $this->post('', $data, $options);
    }

    /**
     * Get transfer details by ID
     * 
     * @param string $transferId Transfer ID
     * @return array Transfer details
     */
    public function getTransfer(string $transferId): array
    {
        return $this->get('/' . $transferId);
    }

    /**
     * Get transfer by reference
     * 
     * @param string $reference Transfer reference
     * @return array Transfer details
     */
    public function getByReference(string $reference): array
    {
        return $this->get('/reference/' . $reference);
    }

    /**
     * List all transfers with pagination
     * 
     * @param array $filters Filter parameters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Paginated transfer list
     */
    public function listTransfers(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $params = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->get('', $params);
        return $this->handlePaginatedResponse($response);
    }

    /**
     * Cancel a pending transfer
     * 
     * @param string $transferId Transfer ID
     * @param string|null $reason Cancellation reason
     * @return array Cancelled transfer
     */
    public function cancel(string $transferId, ?string $reason = null): array
    {
        $data = [];
        if ($reason) {
            $data['reason'] = $reason;
        }
        return $this->post('/' . $transferId . '/cancel', $data);
    }

    /**
     * Get transfer status
     * 
     * @param string $transferId Transfer ID
     * @return array Status information
     */
    public function getTransferStatus(string $transferId): array
    {
        return $this->get('/' . $transferId . '/status');
    }

    /**
     * Get transfer statistics
     * 
     * @param array $filters Date range and other filters
     * @return array Statistics data
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->get('/statistics', $filters);
    }

    /**
     * Create a bulk transfer (multiple recipients)
     * 
     * @param array $transfers Array of transfer data
     * @param string|null $idempotencyKey Idempotency key
     * @return array Bulk transfer response
     */
    public function bulk(array $transfers, ?string $idempotencyKey = null): array
    {
        if (empty($transfers)) {
            throw new \InvalidArgumentException('Transfers array cannot be empty');
        }

        $data = ['transfers' => $transfers];

        $options = [];
        if ($idempotencyKey) {
            $options['headers'] = $this->getIdempotencyHeaders($idempotencyKey);
        }

        return $this->post('/bulk', $data, $options);
    }

    /**
     * Get bulk transfer status
     * 
     * @param string $batchId Bulk transfer batch ID
     * @return array Bulk transfer status
     */
    public function getBulkStatus(string $batchId): array
    {
        return $this->get('/bulk/' . $batchId);
    }

    /**
     * Estimate transfer fees
     * 
     * @param array $data Transfer data for fee estimation
     * @return array Fee estimate
     */
    public function estimateFee(array $data): array
    {
        $this->validateRequired($data, ['amount', 'currency', 'recipient_country']);
        return $this->post('/estimate-fee', $data);
    }

    /**
     * Get transfer rate
     * 
     * @param string $fromCurrency Source currency
     * @param string $toCurrency Target currency
     * @return array Rate information
     */
    public function getRate(string $fromCurrency, string $toCurrency): array
    {
        return $this->get('/rate', [
            'from' => $fromCurrency,
            'to' => $toCurrency,
        ]);
    }
}
