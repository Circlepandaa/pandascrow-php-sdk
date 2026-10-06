<?php
namespace Pandascrow;

class Webhook
{
    /**
     * Verify a webhook per the docs: HMAC-SHA256 over json_encode([event, data, timestamp])
     * using your secret key, compared with the X-Pandascrow-Signature header.
     * NOTE: `wallet.deposit.success` payloads in the docs have no `data`/`timestamp` envelope;
     * verify those against the signing scheme confirmed with Pandascrow support.
     *
     * @return array the decoded event
     * @throws PandascrowException
     */
    public static function verify(string $secret, ?string $rawBody = null, ?string $signature = null): array
    {
        $rawBody   = $rawBody ?? file_get_contents('php://input');
        $signature = $signature ?? ($_SERVER['HTTP_X_PANDASCROW_SIGNATURE'] ?? '');
        $event = json_decode($rawBody, true);

        if (!is_array($event) || !isset($event['event'], $event['data'], $event['timestamp'])) {
            throw new PandascrowException('Invalid webhook payload', 400);
        }
        $expected = hash_hmac('sha256', json_encode([
            'event'     => $event['event'],
            'data'      => $event['data'],
            'timestamp' => $event['timestamp'],
        ]), $secret);

        if (!hash_equals($expected, $signature)) {
            throw new PandascrowException('Invalid signature', 401);
        }
        return $event;
    }
}
