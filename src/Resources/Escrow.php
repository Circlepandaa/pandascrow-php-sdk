<?php

namespace Pandascrow\Resources;

/**
 * Escrow transactions.
 *
 * Every create method accepts an optional `metadata` object, e.g.
 * ['metadata' => ['property_id' => 'PR-204']]. It is stored on the escrow,
 * returned by find()/all() and included as data.metadata in every webhook for
 * that escrow, so you can match events to your own records.
 */
class Escrow extends Resource
{
    /**
     * Create any escrow type. Prefer createOnetime / createMilestone / createRecurring.
     *
     * @param array<string, mixed> $params Must include escrow_type.
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->client->post('/escrow/initialize', $params);
    }

    /**
     * One-time escrow.
     *
     * Required: uuid, initiator_id, initiator_role (buyer|seller|broker), title, currency, amount,
     * and the counterparty: seller_details (if you are the buyer) or buyer_details (if you are the
     * seller), or both for brokers. Each is ['name' => , 'email' => , 'phone' => ].
     * Optional: description, inspection_period, delivery_date, who_pay_fees (buyer|seller|both),
     * payout (seller's bank: payout_type, bank_code, account_number, account_name, ...),
     * payment_gateway, callback_url, idempotency_key, metadata.
     *
     * Returns escrow_id, transaction_ref, payment_url (or virtual_account) and metadata.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createOnetime(array $params): array
    {
        return $this->create(['escrow_type' => 'onetime'] + $params);
    }

    /**
     * Milestone escrow. Same fields as createOnetime (amount not needed), plus
     * milestones: [['title' => , 'amount' => , 'description' => , 'due_date' => ], ...]
     * and deposit_option: 'milestone' (pay per stage, default) or 'full'.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createMilestone(array $params): array
    {
        return $this->create(['escrow_type' => 'milestone'] + $params);
    }

    /**
     * Recurring (group contribution) escrow.
     *
     * Required: uuid, title, currency, amount and
     * cycle: ['cycle_interval', 'minimum_contributors', 'maximum_contributors',
     *         'due_day_of_month', 'expected_amount', 'due_date'].
     * Optional: contributors [['name' => , 'email' => , 'amount' => ], ...], metadata.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createRecurring(array $params): array
    {
        return $this->create(['escrow_type' => 'recurring'] + $params);
    }

    /**
     * All escrows the user takes part in (with participants, transactions, milestones, payout, metadata...).
     *
     * @return array<int|string, mixed>
     */
    public function all(string $uuid): array
    {
        return $this->client->get('/escrow', ['uuid' => $uuid]);
    }

    /**
     * One escrow with full details.
     *
     * @param int|string $escrowId
     * @return array<string, mixed>
     */
    public function find(string $uuid, $escrowId): array
    {
        return $this->client->get('/escrow/single', ['uuid' => $uuid, 'escrow_id' => $escrowId]);
    }

    /**
     * Buyer confirms delivery with the release OTP; funds are paid out to the seller.
     *
     * @param int|string $escrowId
     * @return array<string, mixed>
     */
    public function complete(string $uuid, $escrowId, string $otp): array
    {
        return $this->client->post('/escrow/complete', ['uuid' => $uuid, 'escrow_id' => (int) $escrowId, 'otp' => $otp]);
    }

    /**
     * Re-send the release OTP to the buyer.
     *
     * @param int|string $escrowId
     * @return array<string, mixed>
     */
    public function resendReleaseOtp(string $uuid, $escrowId): array
    {
        return $this->client->post('/escrow/resend-otp', ['uuid' => $uuid, 'escrow_id' => (int) $escrowId]);
    }

    /**
     * Payment link for the next unpaid milestone.
     *
     * @param int|string           $escrowId
     * @param array<string, mixed> $extra e.g. ['callback_url' => ...]
     * @return array<string, mixed>
     */
    public function payNextMilestone(string $uuid, $escrowId, array $extra = []): array
    {
        return $this->client->post('/escrow/next-payment', ['uuid' => $uuid, 'escrow_id' => (int) $escrowId] + $extra);
    }

    /**
     * Approve one milestone with its OTP; that milestone's funds are paid out.
     *
     * @param int|string $escrowId
     * @param int|string $milestoneId
     * @return array<string, mixed>
     */
    public function completeMilestone(string $uuid, $escrowId, $milestoneId, string $otp): array
    {
        return $this->client->post('/escrow/milestone-item/complete', [
            'uuid'         => $uuid,
            'escrow_id'    => (int) $escrowId,
            'milestone_id' => (int) $milestoneId,
            'otp'          => $otp,
        ]);
    }

    /**
     * New payment link when the original expired.
     *
     * @param int|string           $escrowId
     * @param array<string, mixed> $extra cust_name, cust_email, callback_url
     * @return array<string, mixed>
     */
    public function regeneratePayment(string $uuid, $escrowId, array $extra = []): array
    {
        return $this->client->post('/escrow/regenerate-payment', ['uuid' => $uuid, 'escrow_id' => (int) $escrowId] + $extra);
    }

    /**
     * New payment link for a specific milestone.
     *
     * @param int|string           $escrowId
     * @param int|string           $milestoneId
     * @param array<string, mixed> $extra cust_name, cust_email, callback_url
     * @return array<string, mixed>
     */
    public function regenerateMilestonePayment(string $uuid, $escrowId, $milestoneId, array $extra = []): array
    {
        return $this->client->post('/escrow/regenerate-milestone-payment', [
            'uuid' => $uuid, 'escrow_id' => (int) $escrowId, 'milestone_id' => (int) $milestoneId,
        ] + $extra);
    }

    /**
     * Open a dispute. Funds stay locked until it is resolved.
     *
     * @param int|string $escrowId
     * @return array<string, mixed>
     */
    public function dispute(string $uuid, $escrowId, string $reason, ?string $screenshotUrl = null): array
    {
        return $this->client->post('/escrow/dispute', [
            'uuid'           => $uuid,
            'escrow_id'      => (int) $escrowId,
            'reason'         => $reason,
            'screenshot_url' => $screenshotUrl,
        ]);
    }

    /**
     * Disputes for the user, optionally for one escrow or with a status filter.
     *
     * @param int|string|null $escrowId
     * @return array<int|string, mixed>
     */
    public function disputes(string $uuid, $escrowId = null, ?string $status = null): array
    {
        return $this->client->get('/escrow/dispute', [
            'uuid'      => $uuid,
            'escrow_id' => $escrowId,
            'status'    => $status,
        ]);
    }

    /**
     * Join a recurring escrow as a contributor.
     *
     * @param int|string $escrowId
     * @return array<string, mixed>
     */
    public function joinRecurring(string $uuid, $escrowId, string $name, string $email, $amount): array
    {
        return $this->client->post('/escrow/recurring/join', [
            'uuid'      => $uuid,
            'escrow_id' => (int) $escrowId,
            'name'      => $name,
            'email'     => $email,
            'amount'    => $amount,
        ]);
    }
}
