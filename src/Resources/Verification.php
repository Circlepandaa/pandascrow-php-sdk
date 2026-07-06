<?php

declare(strict_types=1);

namespace Pandascrow\Resources;

use Pandascrow\Contracts\LoggerInterface;

class Verification extends BaseResource
{
    public function __construct(
        \Pandascrow\HttpClient\ClientInterface $httpClient,
        \Pandascrow\Auth\Authenticator $authenticator,
        \Pandascrow\Config $config,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($httpClient, $authenticator, $config, $logger);
        $this->setBasePath('/verifications');
    }

    /**
     * Verify a bank account
     *
     * @param array{account_number: string, bank_code: string} $data
     * @return array<mixed>
     */
    public function verifyBankAccount(array $data): array
    {
        $this->validateRequired($data, ['account_number', 'bank_code']);
        return $this->post('/bank-account', $data);
    }

    /**
     * Verify a mobile money account
     *
     * @param array{phone_number: string, network: string} $data
     * @return array<mixed>
     */
    public function verifyMobileMoney(array $data): array
    {
        $this->validateRequired($data, ['phone_number', 'network']);
        return $this->post('/mobile-money', $data);
    }

    /**
     * Verify a bank card
     *
     * @param array{pan: string, expiry_month: string, expiry_year: string, cvv?: string} $data
     * @return array<mixed>
     */
    public function verifyCard(array $data): array
    {
        $this->validateRequired($data, ['pan', 'expiry_month', 'expiry_year']);
        return $this->post('/card', $data);
    }

    /**
     * Verify a BVN (Bank Verification Number)
     *
     * @param string $bvn BVN number
     * @param array<mixed> $optionalData Additional verification data
     * @return array<mixed>
     */
    public function verifyBVN(string $bvn, array $optionalData = []): array
    {
        $data = array_merge(['bvn' => $bvn], $optionalData);
        return $this->post('/bvn', $data);
    }

    /**
     * Verify a phone number
     *
     * @param string $phone Phone number to verify
     * @param string $country Country code
     * @return array<mixed>
     */
    public function verifyPhone(string $phone, string $country): array
    {
        return $this->post('/phone', [
            'phone' => $phone,
            'country' => $country,
        ]);
    }

    /**
     * Verify an email address
     *
     * @param string $email Email to verify
     * @return array<mixed>
     */
    public function verifyEmail(string $email): array
    {
        return $this->post('/email', ['email' => $email]);
    }

    /**
     * Verify a VAT/TIN number
     *
     * @param string $tin Tax Identification Number
     * @param string $country Country code
     * @return array<mixed>
     */
    public function verifyTIN(string $tin, string $country): array
    {
        return $this->post('/tin', [
            'tin' => $tin,
            'country' => $country,
        ]);
    }

    /**
     * Get available banks for verification
     *
     * @param string $country Country code
     * @return array<mixed>
     */
    public function getBanks(string $country): array
    {
        return $this->get('/banks', ['country' => $country]);
    }

    /**
     * Get available networks for mobile money
     *
     * @param string $country Country code
     * @return array<mixed>
     */
    public function getMobileNetworks(string $country): array
    {
        return $this->get('/mobile-networks', ['country' => $country]);
    }

    /**
     * Verify a business registration
     *
     * @param array{registration_number: string, country: string, business_name?: string} $data
     * @return array<mixed>
     */
    public function verifyBusiness(array $data): array
    {
        $this->validateRequired($data, ['registration_number', 'country']);
        return $this->post('/business', $data);
    }

    /**
     * Perform a KYC verification
     *
     * @param array{type: string, document_id: string, document_image?: string} $data
     * @return array<mixed>
     */
    public function verifyKYC(array $data): array
    {
        $this->validateRequired($data, ['type', 'document_id']);
        return $this->post('/kyc', $data);
    }

    /**
     * Get verification status by ID
     *
     * @param string $verificationId Verification ID
     * @return array<mixed>
     */
    public function getVerificationStatus(string $verificationId): array
    {
        return $this->get('/' . $verificationId . '/status');
    }

    /**
     * List verification attempts
     *
     * @param array{status?: string, type?: string, date_from?: string, date_to?: string} $filters
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data: array<mixed>, pagination: array{total: mixed|null, page: mixed|null, per_page: mixed|null, total_pages: mixed|null}}
     */
    public function listVerifications(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $params = array_merge($filters, [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->get('', $params);
        return $this->handlePaginatedResponse($response);
    }
}
