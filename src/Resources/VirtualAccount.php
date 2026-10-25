<?php

namespace Pandascrow\Resources;

/**
 * Dedicated virtual bank accounts (NGN). Money sent to them lands in the user's
 * wallet and triggers a `wallet.deposit.success` webhook.
 *
 * Every create method accepts `metadata` in $options; it is returned in the
 * `wallet.deposit.success` webhook for deposits into that account.
 */
class VirtualAccount extends Resource
{
    /**
     * Temporary account that expires (1–24 hours, default 1).
     *
     * @param int|float|string|null $amount Exact amount to collect, or null to accept any amount.
     * @param array{
     *     expiry_hours?: int, description?: string, reference?: string,
     *     beneficiary?: array{accountnumber: string, bankcode?: string, feeamount?: float},
     *     metadata?: array<string, mixed>, amounttype?: string
     * } $options
     * @return array<string, mixed> account_number, account_name, bank, reference, expiry...
     */
    public function createDynamic(string $uuid, string $accountName, $amount = null, array $options = []): array
    {
        return $this->client->post('/dva/create', $this->payload($uuid, $accountName, $amount, $options, true));
    }

    /**
     * Permanent account (no expiry).
     *
     * @param int|float|string|null $amount Exact amount, or null to accept any amount (default).
     * @param array<string, mixed>  $options Same as createDynamic, without expiry_hours.
     * @return array<string, mixed>
     */
    public function createStatic(string $uuid, string $accountName, $amount = null, array $options = []): array
    {
        return $this->client->post('/dva/static/create', $this->payload($uuid, $accountName, $amount, $options, false));
    }

    /**
     * Give an existing account a new amount/description.
     *
     * @param int|float|string|null $amount
     * @param array<string, mixed>  $options description, amounttype, beneficiary
     * @return array<string, mixed>
     */
    public function reallocate(string $uuid, string $accountNumber, string $accountName, $amount = null, array $options = []): array
    {
        $body = $this->payload($uuid, $accountName, $amount, $options, false);
        $body['customer']['account']['number'] = $accountNumber;
        return $this->client->post('/dva/reallocate', $body);
    }

    /** @return array<string, mixed> */
    public function deactivate(string $uuid, string $accountNumber): array
    {
        return $this->client->post('/dva/deactivate', ['uuid' => $uuid, 'customer' => ['account' => ['number' => $accountNumber]]]);
    }

    /** @return array<string, mixed> */
    public function reactivate(string $uuid, string $accountNumber): array
    {
        return $this->client->post('/dva/reactivate', ['uuid' => $uuid, 'customer' => ['account' => ['number' => $accountNumber]]]);
    }

    /**
     * Ask Pandascrow to re-check a payment into an account (if a webhook was missed).
     *
     * @param int|float|string $amount
     * @return array<string, mixed>
     */
    public function confirmPayment(string $uuid, string $accountNumber, string $reference, $amount, ?string $sessionId = null): array
    {
        return $this->client->post('/dva/confirm-payment', [
            'uuid'          => $uuid,
            'accountnumber' => $accountNumber,
            'reference'     => $reference,
            'sessionid'     => $sessionId,
            'amount'        => (string) $amount,
        ]);
    }

    /**
     * @param int|float|string|null $amount
     * @param array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function payload(string $uuid, string $accountName, $amount, array $options, bool $withExpiry): array
    {
        $account = ['name' => $accountName];
        if ($withExpiry && isset($options['expiry_hours'])) {
            $account['expiry'] = ['hours' => (int) $options['expiry_hours']];
        }

        $order = [
            'amounttype' => $options['amounttype'] ?? ($amount === null ? 'ANY' : 'EXACT'),
        ];
        if ($amount !== null) {
            $order['amount'] = (float) $amount;
        }
        if (isset($options['description'])) {
            $order['description'] = (string) $options['description'];
        }

        $body = ['uuid' => $uuid, 'customer' => ['account' => $account], 'order' => $order];

        if (!empty($options['reference'])) {
            $body['transaction'] = ['reference' => (string) $options['reference']];
        }
        if (!empty($options['beneficiary'])) {
            $body['beneficiarytocredit'] = $options['beneficiary'];
        }
        if (isset($options['metadata'])) {
            $body['metadata'] = $options['metadata'];
        }

        return $body;
    }
}
