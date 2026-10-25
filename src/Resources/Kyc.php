<?php

namespace Pandascrow\Resources;

/**
 * Identity lookups. Each lookup is billed to the account that owns the API key.
 */
class Kyc extends Resource
{
    /** @return array<string, mixed> */
    public function bvn(string $bvn): array
    {
        return $this->client->get('/kyc/ng/lookup/bvn', ['bvn' => $bvn]);
    }

    /** @return array<string, mixed> */
    public function nin(string $nin): array
    {
        return $this->client->get('/kyc/ng/lookup/nin', ['nin' => $nin]);
    }

    /** @return array<string, mixed> */
    public function phone(string $phoneNumber): array
    {
        return $this->client->get('/kyc/ng/lookup/phone', ['phone_number' => $phoneNumber]);
    }

    /** Account name for a NUBAN account number. @return array<string, mixed> */
    public function nuban(string $accountNumber, string $bankCode): array
    {
        return $this->client->get('/kyc/ng/lookup/nuban', ['account_number' => $accountNumber, 'bank_code' => $bankCode]);
    }

    /** @return array<string, mixed> */
    public function driversLicense(string $licenseNumber): array
    {
        return $this->client->get('/kyc/ng/lookup/dl', ['license_number' => $licenseNumber]);
    }

    /** @return array<string, mixed> */
    public function tin(string $tin): array
    {
        return $this->client->get('/kyc/ng/lookup/tin', ['tin' => $tin]);
    }

    /**
     * CAC company lookup by RC number.
     *
     * @param string|null $companyType e.g. BUSINESS_NAME, COMPANY, INCORPORATED_TRUSTEES
     * @return array<string, mixed>
     */
    public function cac(string $rcNumber, ?string $companyType = null): array
    {
        return $this->client->get('/kyc/ng/lookup/cac/rc', ['rc_number' => $rcNumber, 'company_type' => $companyType]);
    }

    /**
     * CAC search by company name.
     *
     * @return array<string, mixed>
     */
    public function searchCac(string $searchTerm, ?string $searchType = null): array
    {
        return $this->client->get('/kyc/ng/lookup/cac/name', ['searchTerm' => $searchTerm, 'searchType' => $searchType]);
    }

    /** Email deliverability / risk check. @return array<string, mixed> */
    public function email(string $email): array
    {
        return $this->client->get('/kyc/lookup/email', ['email' => $email]);
    }
}
