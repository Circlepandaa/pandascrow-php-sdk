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
     * @param array{amount: int|float, currency: string, customer: array{email: string, name?: string, phone?: string}, description?: string, metadata?: array<string, mixed>, return_url?: string} $data
     * @param string|null $idempotencyKey
     * @return array<mixed>
     * @throws \Pandascrow\Exceptions\ValidationException
     * @throws \Pandascrow\Exceptions\AuthenticationException
     * @throws \Pandascrow\Exceptions\ApiException
     */
    public function create(array $data, ?string $idempotencyKey = null): array
    {
        $this->validateRequired($data, ['amount', 'currency', 'customer']);

        $options = [];
        if ($idempotencyKey !== null) {
            $options['headers'] = $this->getIdempotencyHeaders($idempotencyKey);
        }

        return $this->post('', $data, $options);
    }

    /**
     * Get payment details by ID
     *
     * @param string $paymentId
     * @return array<mixed>
     */
    public function getPayment(string $paymentId): array
    {
        return $this->get('/' . $paymentId);
    }

    /**
     * Get payment by reference
     *
     * @param string $reference
     * @return array<mixed>
     */
    public function getByReference(string $reference): array
    {
        return $this->get('/reference/' . $reference);
    }

    /**
     * List all payments with pagination
     *
     * @param array{status?: string, date_from?: string, date_to?: string, customer_id?: string} $filters
     * @param int $page
     * @param int $perPage
     * @return array{data: array<mixed>, pagination: array{total: mixed|null, page: mixed|null, per_page: mixed|null, total_pages: mixed|null}}
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

    // ... rest of the methods with similar PHPDoc additions
}
