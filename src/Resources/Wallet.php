<?php
namespace Pandascrow\Resources;

class Wallet extends Resource
{
    public function list(string $uuid): array
    {
        return $this->client->get('/wallets', ['uuid' => $uuid]);
    }

    /** $range: today | 1week | 1month | 1year */
    public function balance(string $uuid, string $range = 'today'): array
    {
        return $this->client->get('/wallet/balance', ['uuid' => $uuid, 'range' => $range]);
    }

    public function transactions(string $uuid, string $currency): array
    {
        return $this->client->get('/wallet', ['uuid' => $uuid, 'currency' => $currency]);
    }

    /** $method: card | bank_transfer. Returns payment_url, reference, provider. */
    public function fund(string $uuid, string $currency, $amount, string $method = 'card'): array
    {
        return $this->client->post('/wallet/deposit', [
            'uuid' => $uuid, 'currency' => $currency, 'amount' => (string) $amount, 'method' => $method,
        ]);
    }

    /** $destination: ['account_number' => , 'account_name' => , 'bank_code' => ] */
    public function payout(string $uuid, string $walletId, $amount, string $currency, array $destination, string $method = 'bank_transfer'): array
    {
        return $this->client->post('/wallet/payout', [
            'uuid' => $uuid, 'wallet_id' => $walletId, 'amount' => (string) $amount,
            'currency' => $currency, 'method' => $method, 'destination' => $destination,
        ]);
    }
}
