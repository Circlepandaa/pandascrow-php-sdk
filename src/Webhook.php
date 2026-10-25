<?php

namespace Pandascrow;

/**
 * Verify and read webhooks sent by Pandascrow.
 *
 * Every webhook is a POST with this JSON body:
 *
 *   { "event": "escrow.completed", "data": { ... }, "timestamp": 1792899114 }
 *
 * and these headers:
 *
 *   X-Pandascrow-Signature: hex HMAC-SHA256 of the raw body, keyed with your secret key
 *   X-Pandascrow-Timestamp: same value as "timestamp"
 *   X-Pandascrow-Event:     same value as "event"
 *
 * Usage in your webhook endpoint:
 *
 *   try {
 *       $event = Pandascrow\Webhook::verify(getenv('PANDASCROW_SECRET_KEY'));
 *   } catch (Pandascrow\PandascrowException $e) {
 *       http_response_code($e->httpStatus ?: 400);
 *       exit;
 *   }
 *   // $event['event'], $event['data'], $event['data']['metadata'] ...
 *   http_response_code(200);
 */
class Webhook
{
    /** Default tolerance for the timestamp check, in seconds. */
    public const DEFAULT_TOLERANCE = 300;

    /**
     * Check the signature and return the decoded event.
     *
     * @param string      $secret    Your application's secret key (not the public key).
     * @param string|null $rawBody   Raw request body. Defaults to php://input. Always pass the
     *                               raw string: re-encoding a decoded array changes the bytes
     *                               and breaks the signature.
     * @param string|null $signature X-Pandascrow-Signature header. Defaults to the current request's header.
     * @param int         $tolerance Reject events whose timestamp is older/newer than this many
     *                               seconds (replay protection). 0 disables the check.
     *
     * @return array<string, mixed> ['event' => string, 'data' => array, 'timestamp' => int]
     *
     * @throws PandascrowException 400 for a malformed payload, 401 for a bad signature or stale timestamp
     */
    public static function verify(string $secret, ?string $rawBody = null, ?string $signature = null, int $tolerance = self::DEFAULT_TOLERANCE): array
    {
        if ($secret === '') {
            throw new PandascrowException('Webhook secret key is empty', 500);
        }

        if ($rawBody === null) {
            $input   = file_get_contents('php://input');
            $rawBody = $input === false ? '' : $input;
        }
        if ($signature === null) {
            $signature = self::header('X-Pandascrow-Signature');
        }

        if ($rawBody === '') {
            throw new PandascrowException('Empty webhook body', 400);
        }

        $signature = strtolower(trim((string) $signature));
        if ($signature === '' || !self::isValidSignature($secret, $rawBody, $signature)) {
            throw new PandascrowException('Invalid webhook signature', 401);
        }

        $event = json_decode($rawBody, true);
        if (!is_array($event) || !isset($event['event'])) {
            throw new PandascrowException('Invalid webhook payload', 400);
        }

        if ($tolerance > 0 && isset($event['timestamp']) && is_numeric($event['timestamp'])) {
            if (abs(time() - (int) $event['timestamp']) > $tolerance) {
                throw new PandascrowException('Webhook timestamp is outside the allowed tolerance', 401);
            }
        }

        if (!isset($event['data']) || !is_array($event['data'])) {
            $event['data'] = [];
        }

        return $event;
    }

    /** Constant-time check of a signature against the raw body. */
    public static function isValidSignature(string $secret, string $rawBody, string $signature): bool
    {
        return hash_equals(self::sign($secret, $rawBody), strtolower(trim($signature)));
    }

    /** The signature Pandascrow sends for this body (useful for tests). */
    public static function sign(string $secret, string $rawBody): string
    {
        return hash_hmac('sha256', $rawBody, $secret);
    }

    private static function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$key])) {
            return (string) $_SERVER[$key];
        }
        if (function_exists('getallheaders')) {
            foreach ((array) getallheaders() as $k => $v) {
                if (strcasecmp((string) $k, $name) === 0) {
                    return (string) $v;
                }
            }
        }
        return '';
    }
}
