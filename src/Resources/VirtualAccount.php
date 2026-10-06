<?php
namespace Pandascrow\Resources;

class VirtualAccount extends Resource
{
    public function createDynamic(string $uuid, $amount, string $accountName): array
    {
        return $this->client->post('/dva/create', $this->payload($uuid, $amount, $accountName));
    }

    public function createStatic(string $uuid, $amount, string $accountName): array
    {
        $p = $this->payload($uuid, $amount, $accountName);
        // Docs list these as query params on a POST; sending both body and query to be safe.
        return $this->client->post('/dva/static/create', $p, ['uuid' => $uuid]);
    }

    public function confirmPayment(string $uuid, string $reference, $amount, string $accountNumber): array
    {
        $p = ['uuid' => $uuid, 'reference' => $reference, 'amount' => (string) $amount, 'accountnumber' => $accountNumber];
        return $this->client->post('/dva/confirm-payment', $p, $p);
    }

    private function payload(string $uuid, $amount, string $name): array
    {
        return ['uuid' => $uuid, 'order' => ['amount' => (string) $amount], 'customer' => ['account' => ['name' => $name]]];
    }
}
