<?php

namespace Pandascrow\Resources;

/**
 * Invoices. Pass `metadata` in $extra to get it back on reads and in the `invoice.paid` webhook.
 */
class Invoice extends Resource
{
    /**
     * @param array<string, mixed>             $client  ['full_name' => , 'email' => , 'phone' => , 'billing_address' => , 'company_name' => ]
     * @param array<string, mixed>             $invoice ['currency' => 'NGN', 'due_date' => 'YYYY-MM-DD', 'notes' => , 'tax' => ]
     * @param array<int, array<string, mixed>> $items   [['description' => , 'quantity' => , 'unit_price' => ], ...]
     * @param array<string, mixed>             $extra   e.g. ['metadata' => ['order_id' => 'A-1']]
     * @return array<string, mixed> invoice_id, invoice_number, payment_url, metadata
     */
    public function create(string $ownerUuid, array $client, array $invoice, array $items, string $paymentMethod = 'payment_link', array $extra = []): array
    {
        return $this->client->post('/invoice', [
            'uuid'       => $ownerUuid,
            'owner_uuid' => $ownerUuid,
            'client'     => $client,
            'invoice'    => $invoice,
            'items'      => $items,
            'payment'    => ['method' => $paymentMethod],
        ] + $extra);
    }

    /** @return array<string, mixed> ['invoices' => [...]] */
    public function all(string $uuid): array
    {
        return $this->client->get('/invoice', ['uuid' => $uuid]);
    }

    /**
     * @param int|string $invoiceId
     * @return array<string, mixed>
     */
    public function find(string $uuid, $invoiceId): array
    {
        return $this->client->get('/invoice/single', ['uuid' => $uuid, 'invoice_id' => $invoiceId]);
    }

    /**
     * Start payment for an open invoice; returns a payment link.
     *
     * @return array<string, mixed>
     */
    public function pay(string $uuid, string $invoiceNumber): array
    {
        return $this->client->post('/invoice/pay', ['uuid' => $uuid, 'invoice_number' => $invoiceNumber]);
    }

    /**
     * @param int|string $invoiceId
     * @return array<string, mixed>
     */
    public function cancel(string $uuid, $invoiceId): array
    {
        return $this->client->post('/invoice/cancel', ['uuid' => $uuid, 'invoice_id' => $invoiceId]);
    }
}
