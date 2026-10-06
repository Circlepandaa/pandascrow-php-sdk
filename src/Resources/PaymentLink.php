<?php
namespace Pandascrow\Resources;

class PaymentLink extends Resource
{
    /** Required: uuid, type (onetime|subscription), currency, amount, name, description. */
    public function create(array $p): array
    {
        return $this->client->post('/payme', $p);
    }

    public function all(string $uuid): array
    {
        return $this->client->get('/payme', ['uuid' => $uuid]);
    }

    /** Payments made against a link (id or slug). */
    public function payments(string $linkId): array
    {
        return $this->client->get('/payme/payments', ['link_id' => $linkId]);
    }

    /** Required: link_id, full_name, email, phone_number, amount, delivery_address. */
    public function initiatePayment(array $p): array
    {
        return $this->client->post('/payme/initialize', $p);
    }
}
