<?php

namespace Pandascrow\Resources;

/**
 * PayMe payment links. Every payment made through a link is held in escrow
 * until the owner completes it.
 */
class PaymentLink extends Resource
{
    /**
     * Required: uuid, type (onetime|subscription), name, description.
     * Optional: amount, currency, link_slug + is_custom_link, redirect_link, success_message,
     * intervals (subscriptions), is_phone_required, is_tip_required.
     * New links are created closed; call goLive() to start accepting payments.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed> uuid, link, slug
     */
    public function create(array $params): array
    {
        return $this->client->post('/payme', $params + ['type' => 'onetime']);
    }

    /**
     * @param array{type?: string, is_live?: int|bool} $filters
     * @return array<string, mixed> count, page, limit, links
     */
    public function all(string $uuid, array $filters = []): array
    {
        return $this->client->get('/payme', ['uuid' => $uuid] + $filters);
    }

    /**
     * One link with its payments.
     *
     * @return array<string, mixed>
     */
    public function find(string $uuid, ?string $slug = null): array
    {
        return $this->client->get('/payme/single', ['uuid' => $uuid, 'link_slug' => $slug]);
    }

    /**
     * Is a custom slug free?
     *
     * @return array<string, mixed>
     */
    public function isSlugAvailable(string $slug): array
    {
        return $this->client->post('/payme/slug', ['link_slug' => $slug]);
    }

    /**
     * Toggle a link between live (accepting payments) and closed.
     *
     * @param int|string $paymeId Link id (`_id` from all()/find()).
     * @return array<string, mixed>
     */
    public function goLive(string $uuid, $paymeId): array
    {
        return $this->client->post('/payme/live', ['uuid' => $uuid, 'payme_id' => $paymeId]);
    }

    /**
     * Turn "tip required" on or off for a link.
     *
     * @return array<string, mixed>
     */
    public function toggleTip(string $uuid, string $slug): array
    {
        return $this->client->post('/payme/tip', ['uuid' => $uuid, 'link_slug' => $slug]);
    }

    /**
     * Payments received on a link.
     *
     * @param array{status?: string, search?: string, date_from?: string, date_to?: string, page?: int, limit?: int} $filters
     * @return array<string, mixed> count, payments, page, limit
     */
    public function payments(string $linkId, array $filters = []): array
    {
        return $this->client->get('/payme/payments', ['link_id' => $linkId] + $filters);
    }

    /**
     * A customer pays through a link. Required: link_id (the slug), full_name, email, amount.
     * Optional: phone_number, delivery_address, tip_amount.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed> payment_url, escrow_id, transaction_ref, provider
     */
    public function initiatePayment(array $params): array
    {
        return $this->client->post('/payme/initialize', $params);
    }

    /**
     * Release a PayMe payment to the link owner with the OTP.
     *
     * @param int|string $paymentId
     * @return array<string, mixed>
     */
    public function completePayment(string $uuid, string $slug, $paymentId, string $otp): array
    {
        return $this->client->post('/payme/complete', [
            'uuid' => $uuid, 'slug' => $slug, 'payment_id' => $paymentId, 'otp' => $otp,
        ]);
    }

    /**
     * Ask the payer to top up a PayMe payment (e.g. extra delivery cost).
     *
     * @param int|string       $paymentId
     * @param int|float|string $marginAmount
     * @return array<string, mixed>
     */
    public function addMargin(string $uuid, string $slug, $paymentId, $marginAmount): array
    {
        return $this->client->post('/payme/add-margin', [
            'uuid' => $uuid, 'slug' => $slug, 'payment_id' => $paymentId, 'margin_amount' => $marginAmount,
        ]);
    }
}
