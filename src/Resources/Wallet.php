<?php

namespace Pandascrow\Resources;

class Wallet extends Resource
{
    /**
     * The user's wallets (one per currency).
     *
     * @return array<int|string, mixed>
     */
    public function list(string $uuid): array
    {
        return $this->client->get('/wallets', ['uuid' => $uuid]);
    }

    /**
     * Balances across all currencies.
     *
     * @return array<string, mixed>
     */
    public function balances(string $uuid): array
    {
        return $this->client->get('/wallet/balance', ['uuid' => $uuid]);
    }

    /**
     * @deprecated since 1.1.0, use balances(). The API has no date range on balances.
     * @return array<string, mixed>
     */
    public function balance(string $uuid, string $range = 'today'): array
    {
        return $this->balances($uuid);
    }

    /**
     * Wallet history for one currency.
     *
     * @param string|null $type deposit | withdrawal | ... (optional filter)
     * @return array<string, mixed> balance, currency, transactions
     */
    public function transactions(string $uuid, string $currency, ?string $type = null): array
    {
        return $this->client->get('/wallet', ['uuid' => $uuid, 'currency' => strtoupper($currency), 'type' => $type]);
    }

    /**
     * Virtual bank accounts attached to the user's wallets (for receiving bank transfers).
     *
     * @return array<string, mixed>
     */
    public function accounts(string $uuid): array
    {
        return $this->client->get('/wallet/account', ['uuid' => $uuid]);
    }

    /**
     * Request a manual payout from the wallet.
     * For an instant payout to a saved settlement account, use $client->payouts->create().
     * For an instant NGN bank transfer, use $client->bank->transfer().
     *
     * @param array<string, mixed>|string $destination e.g. ['bank_code' => '058', 'account_number' => '...', 'account_name' => '...']
     * @param int|float|string            $amount
     * @return array<string, mixed> reference, amount, currency, status, method, destination
     */
    public function payout(string $uuid, $amount, string $currency, $destination, string $method = 'bank_transfer'): array
    {
        return $this->client->post('/wallet/payout', [
            'uuid'        => $uuid,
            'amount'      => (string) $amount,
            'currency'    => strtoupper($currency),
            'method'      => $method,
            // The API stores destination as text, so send it as JSON
            'destination' => is_array($destination) ? json_encode($destination, JSON_UNESCAPED_SLASHES) : (string) $destination,
        ]);
    }
}
