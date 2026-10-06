<?php
namespace Pandascrow\Resources;

class Escrow extends Resource
{
    /** One-time escrow. Required: uuid, initiator_role, initiator_id, title, currency, description,
     *  inspection_period, delivery_date, who_pay_fees, amount, buyer_details, seller_details, payout. */
    public function createOnetime(array $p): array
    {
        return $this->client->post('/escrow/initialize', $p + ['escrow_type' => 'onetime']);
    }

    /** Milestone escrow. Also needs deposit_option ("full"|"milestone"), how_dispute_is_handled, milestones. */
    public function createMilestone(array $p): array
    {
        return $this->client->post('/escrow/initialize', $p + ['escrow_type' => 'milestone']);
    }

    public function complete(string $uuid, int $escrowId, string $otp): array
    {
        return $this->client->post('/escrow/complete', ['uuid' => $uuid, 'escrow_id' => $escrowId, 'otp' => $otp]);
    }

    public function payNextMilestone(string $uuid, $escrowId, array $extra = []): array
    {
        return $this->client->post('/escrow/next-payment', ['uuid' => $uuid, 'escrow_id' => (string) $escrowId] + $extra);
    }

    public function completeMilestone(string $uuid, $escrowId, $milestoneId, string $otp): array
    {
        return $this->client->post('/escrow/milestone-item/complete', [
            'uuid' => $uuid, 'escrow_id' => (string) $escrowId, 'milestone_id' => (string) $milestoneId, 'otp' => $otp,
        ]);
    }

    public function all(string $uuid): array
    {
        return $this->client->get('/escrow', ['uuid' => $uuid]);
    }

    public function find(string $uuid, $escrowId): array
    {
        return $this->client->get('/escrow/single', ['uuid' => $uuid, 'escrow_id' => $escrowId]);
    }

    public function resendReleaseOtp(string $uuid, $escrowId): array
    {
        return $this->client->post('/escrow/resend-otp', ['uuid' => $uuid, 'escrow_id' => (string) $escrowId]);
    }

    public function dispute(string $uuid, $escrowId, string $reason, ?string $screenshotUrl = null): array
    {
        $b = ['uuid' => $uuid, 'escrow_id' => (string) $escrowId, 'reason' => $reason];
        if ($screenshotUrl) { $b['screenshot_url'] = $screenshotUrl; }
        return $this->client->post('/escrow/dispute', $b);
    }
}
