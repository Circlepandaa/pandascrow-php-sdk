<?php
namespace Pandascrow\Resources;

class Invoice extends Resource
{
    /**
     * @param array $client  ['full_name','email','billing_address','phone'?,'company_name'?]
     * @param array $invoice ['due_date','notes','currency']
     * @param array $items   [['description'=>,'quantity'=>,'unit_price'=>], ...]
     * @param string $paymentMethod e.g. bank_transfer
     */
    public function create(string $ownerUuid, array $client, array $invoice, array $items, string $paymentMethod = 'bank_transfer'): array
    {
        return $this->client->post('/invoice', [
            'owner_uuid' => $ownerUuid, 'client' => $client, 'invoice' => $invoice,
            'items' => $items, 'payment' => ['method' => $paymentMethod],
        ]);
    }

    public function all(string $uuid): array
    {
        return $this->client->get('/invoice', ['uuid' => $uuid]);
    }

    public function find(string $uuid, int $invoiceId): array
    {
        return $this->client->get('/invoice/single', ['uuid' => $uuid, 'invoice_id' => $invoiceId]);
    }
}
