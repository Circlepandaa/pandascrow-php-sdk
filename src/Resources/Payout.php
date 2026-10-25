<?php

namespace Pandascrow\Resources;

/**
 * Payouts from a wallet to the user's saved settlement account.
 */
class Payout extends Resource
{
    /**
     * Pay out to a settlement account. If $settlementAccountId is null, the active account for
     * the currency is used. NGN is sent instantly; other currencies are queued for processing.
     *
     * @param int|float|string     $amount
     * @param array<string, mixed> $extra e.g. ['internal_notes' => '...']
     * @return array<string, mixed> payout_id, reference, status, amount_sent, fee, destination...
     */
    public function create(string $uuid, $amount, string $currency, ?int $settlementAccountId = null, array $extra = []): array
    {
        return $this->client->post('/payouts', [
            'uuid'                  => $uuid,
            'amount'                => $amount,
            'currency'              => strtoupper($currency),
            'settlement_account_id' => $settlementAccountId,
        ] + $extra);
    }

    /**
     * Payout history.
     *
     * @param array{status?: string, currency?: string, payout_id?: int} $filters
     * @return array<string, mixed> ['payouts' => [...]]
     */
    public function all(string $uuid, array $filters = []): array
    {
        return $this->client->get('/payouts', ['uuid' => $uuid] + $filters);
    }

    /**
     * Settlement accounts saved for the user.
     *
     * @return array<string, mixed>
     */
    public function settlementAccounts(string $uuid): array
    {
        return $this->client->get('/setting/settlement', ['uuid' => $uuid]);
    }
}
