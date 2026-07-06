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
     * @param array{amount: int|float, recipient: array{type?: string, account_number: string, bank_code: string, account_name?: string}, currency: string, reference?: string, narration?: string} $data
     * @param string|null $idempotencyKey Idempotency key
     * @return array<mixed>
     */
    public function initiate(array $data, ?string $idempotencyKey = null): array
    {
        $this->validateRequired($data, ['amount', 'recipient', 'currency']);

        $options = [];
        if ($idempotencyKey !== null) {
            $options['headers'] = $this->getIdempotencyHeaders($idempotencyKey);
        }

        return $this->post('', $data, $options);
    }

    /**
     * Get transfer details by ID
     *
     * @param string $transferId Transfer ID
     * @return array<mixed>
     */
    public function getTransfer(string $transferId): array
    {
        return $this->get('/' . $transferId);
    }

    /**
     * Get transfer by reference
     *
     * @param string $reference Transfer reference
     * @return array<mixed>
     */
    public function getByReference(string $reference): array
    {
        return $this->get('/reference/' . $reference);
    }

    /**
     * List all transfers with pagination
     *
     * @param array{status?: string, date_from?: string, date_to?: string, recipient?: string} $filters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data: array<mixed>, pagination: array{total: mixed|null, page: mixed|null, per_page: mixed|null, total_pages: mixed|null}}
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
     * @return array<mixed>
     */
    public function cancel(string $transferId, ?string $reason = null): array
    {
        $data = [];
        if ($reason !== null) {
            $data['reason'] = $reason;
        }
        return $this->post('/' . $transferId . '/cancel', $data);
    }

    /**
     * Get transfer status
     *
     * @param string $transferId Transfer ID
     * @return array<mixed>
     */
    public function getTransferStatus(string $transferId): array
    {
        return $this->get('/' . $transferId . '/status');
    }

    /**
     * Get transfer statistics
     *
     * @param array{date_from?: string, date_to?: string, currency?: string} $filters
     * @return array<mixed>
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->get('/statistics', $filters);
    }

    /**
     * Create a bulk transfer (multiple recipients)
     *
     * @param array<int, array{amount: int|float, recipient: array{account_number: string, bank_code: string, account_name?: string}, currency: string, reference?: string}> $transfers
     * @param string|null $idempotencyKey Idempotency key
     * @return array<mixed>
     */
    public function bulk(array $transfers, ?string $idempotencyKey = null): array
    {
        if ($transfers === []) {
            throw new \InvalidArgumentException('Transfers array cannot be empty');
        }

        $data = ['transfers' => $transfers];

        $options = [];
        if ($idempotencyKey !== null) {
            $options['headers'] = $this->getIdempotencyHeaders($idempotencyKey);
        }

        return $this->post('/bulk', $data, $options);
    }

    /**
     * Get bulk transfer status
     *
     * @param string $batchId Bulk transfer batch ID
     * @return array<mixed>
     */
    public function getBulkStatus(string $batchId): array
    {
        return $this->get('/bulk/' . $batchId);
    }

    /**
     * Estimate transfer fees
     *
     * @param array{amount: int|float, currency: string, recipient_country: string, recipient_type?: string} $data
     * @return array<mixed>
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
     * @return array<mixed>
     */
    public function getRate(string $fromCurrency, string $toCurrency): array
    {
        return $this->get('/rate', [
            'from' => $fromCurrency,
            'to' => $toCurrency,
        ]);
    }
}
