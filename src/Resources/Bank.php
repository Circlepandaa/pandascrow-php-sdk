<?php

namespace Pandascrow\Resources;

class Bank extends Resource
{
    /**
     * Supported Nigerian banks with their codes.
     *
     * @return array<int|string, mixed>
     */
    public function banks(): array
    {
        return $this->client->get('/bank/lists');
    }

    /**
     * Resolve the account name for a bank account before paying it.
     *
     * @return array<string, mixed>
     */
    public function validate(string $uuid, string $bankCode, string $accountNumber): array
    {
        return $this->client->post('/bank/validate', [
            'uuid'           => $uuid,
            'bank_code'      => $bankCode,
            'account_number' => $accountNumber,
        ]);
    }

    /**
     * NGN wallet → bank transfer.
     *
     * An idempotency_key is generated if you don't pass one in $extra. Store it: sending the
     * same key again returns the original transfer instead of paying twice.
     *
     * @param int|float|string     $amount
     * @param array<string, string> $bank  ['account_number' => , 'account_name' => , 'bank_code' => ]
     * @param array<string, mixed> $extra idempotency_key, description
     * @return array<string, mixed> transaction, metadata (provider response), idempotent
     */
    public function transfer(string $uuid, $amount, array $bank, string $currency = 'NGN', array $extra = []): array
    {
        foreach (['account_number', 'account_name', 'bank_code'] as $field) {
            if (empty($bank[$field])) {
                throw new \InvalidArgumentException("bank['{$field}'] is required");
            }
        }
        $extra += ['idempotency_key' => 'PS_' . bin2hex(random_bytes(8))];

        return $this->client->post('/bank/transfers', [
            'uuid'     => $uuid,
            'amount'   => (string) $amount,
            'currency' => strtoupper($currency),
            'bank'     => $bank,
        ] + $extra);
    }

    /**
     * The user's saved settlement (payout) bank account.
     *
     * @return array<string, mixed>
     */
    public function settlementAccount(string $uuid): array
    {
        return $this->client->get('/bank/settlement', ['uuid' => $uuid]);
    }
}
