<?php

declare(strict_types=1);

namespace Pandascrow\Resources;

use Pandascrow\Contracts\LoggerInterface;

class Payment extends BaseResource
{
    public function __construct(
        \Pandascrow\HttpClient\ClientInterface $httpClient,
        \Pandascrow\Auth\Authenticator $authenticator,
        \Pandascrow\Config $config,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($httpClient, $authenticator, $config, $logger);
        $this->setBasePath('/payments');
    }

    /**
     * Create a new payment
     * 
     * @param array $data Payment data
     * @param string|null $idempotencyKey Idempotency key to prevent duplicate payments
     * @return array Payment response
     * 
     * @throws \Pandascrow\Exceptions\ValidationException
     * @throws \Pandascrow\Exceptions\AuthenticationException
     * @throws \Pandascrow\Exceptions\ApiException
     */
    public function create(array $data, ?string $idempotencyKey = null): array
    {
        $this->validateRequired($data, ['amount', 'currency', 'customer']);

        $options = [];
        if ($idempotencyKey) {
            $options['headers'] = $this->getIdempotencyHeaders($idempotencyKey);
        }

        return $this->post('', $data, $options);
    }

    /**
     * Get payment details by ID
     * 
     * @param string $paymentId Payment ID
     * @return array Payment details
     */
    public function getPayment(string $paymentId): array
    {
        return $this->get('/' . $paymentId);
    }

    /**
     * Get payment by reference
     * 
     * @param string $reference Payment reference
     * @return array Payment details
     */
    public function getByReference(string $reference): array
    {
        return $this->get('/reference/' . $reference);
    }

    /**
     * List all payments with pagination
     * 
     * @param array $filters Filter parameters (status, date_from, date_to, etc.)
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Paginated payment list
     */
    public function listPayments(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $params = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->get('', $params);
        return $this->handlePaginatedResponse($response);
    }

    /**
     * Update an existing payment
     * 
     * @param string $paymentId Payment ID
     * @param array $data Update data
     * @return array Updated payment
     */
    public function updatePayment(string $paymentId, array $data): array
    {
        return $this->put('/' . $paymentId, $data);
    }

    /**
     * Cancel a payment
     * 
     * @param string $paymentId Payment ID
     * @param string|null $reason Cancellation reason
     * @return array Cancelled payment
     */
    public function cancel(string $paymentId, ?string $reason = null): array
    {
        $data = [];
        if ($reason) {
            $data['reason'] = $reason;
        }
        return $this->post('/' . $paymentId . '/cancel', $data);
    }

    /**
     * Confirm a payment
     * 
     * @param string $paymentId Payment ID
     * @param array $data Confirmation data (OTP, etc.)
     * @return array Confirmed payment
     */
    public function confirm(string $paymentId, array $data = []): array
    {
        return $this->post('/' . $paymentId . '/confirm', $data);
    }

    /**
     * Initiate refund for a payment
     * 
     * @param string $paymentId Payment ID
     * @param array $data Refund data (amount, reason, etc.)
     * @return array Refund response
     */
    public function refund(string $paymentId, array $data): array
    {
        $this->validateRequired($data, ['amount']);
        return $this->post('/' . $paymentId . '/refund', $data);
    }

    /**
     * Get payment status
     * 
     * @param string $paymentId Payment ID
     * @return array Status information
     */
    public function getStatus(string $paymentId): array
    {
        return $this->get('/' . $paymentId . '/status');
    }

    /**
     * Get payment statistics
     * 
     * @param array $filters Date range and other filters
     * @return array Statistics data
     */
    public function getStatistics(array $filters = []): array
    {
        return $this->get('/statistics', $filters);
    }

    /**
     * Initialize payment with customer redirection
     * 
     * @param array $data Payment data including return_url
     * @return array Payment initialization with redirect URL
     */
    public function initialize(array $data): array
    {
        $this->validateRequired($data, ['amount', 'currency', 'customer', 'return_url']);
        return $this->post('/initialize', $data);
    }
}
