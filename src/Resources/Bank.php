<?php
namespace Pandascrow\Resources;

class Bank extends Resource
{
    public function banks(): array
    {
        return $this->client->get('/bank/lists');
    }

    public function validate(string $uuid, string $bankCode, string $accountNumber): array
    {
        return $this->client->post('/bank/validate', [
            'uuid' => $uuid, 'bank_code' => $bankCode, 'account_number' => $accountNumber,
        ]);
    }

    /** NGN wallet -> bank transfer. $bank: ['account_number','account_name','bank_code'].
     *  An idempotency_key is generated if you don't pass one; store it to safely retry. */
    public function transfer(string $uuid, $amount, array $bank, string $currency = 'NGN', array $extra = []): array
    {
        $extra += ['idempotency_key' => bin2hex(random_bytes(16))];
        return $this->client->post('/bank/transfers', [
            'uuid' => $uuid, 'amount' => (string) $amount, 'currency' => $currency, 'bank' => $bank,
        ] + $extra);
    }
}
