<?php

declare(strict_types=1);

namespace Pandascrow\Utils;

use Pandascrow\Exceptions\WebhookException;

class SignatureValidator
{
    private const DEFAULT_ALGORITHM = 'sha256';
    private const SIGNATURE_PREFIX = 'v1=';

    /**
     * Validate webhook signature
     * 
     * @param string $payload Raw webhook payload
     * @param string $signature Signature header value
     * @param string $secret Webhook secret
     * @param int $tolerance Tolerance in seconds for timestamp validation
     * @return bool True if signature is valid
     * @throws WebhookException
     */
    public function validate(
        string $payload,
        string $signature,
        string $secret,
        int $tolerance = 300
    ): bool {
        // Parse signature
        $parsed = $this->parseSignature($signature);

        if (!$parsed) {
            throw new WebhookException('Invalid signature format');
        }

        // Validate timestamp
        if (!$this->validateTimestamp($parsed['timestamp'], $tolerance)) {
            throw new WebhookException('Webhook timestamp is too old or in the future');
        }

        // Compute expected signature
        $expected = $this->computeSignature($payload, $secret, $parsed['timestamp']);
        $provided = $parsed['signature'];

        // Compare signatures using timing-safe comparison
        if (!$this->compareSignatures($provided, $expected)) {
            throw new WebhookException('Signature mismatch');
        }

        return true;
    }

    /**
     * Parse signature header
     * Format: v1=timestamp.signature
     */
    private function parseSignature(string $signature): ?array
    {
        // Remove prefix if present
        if (strpos($signature, self::SIGNATURE_PREFIX) === 0) {
            $signature = substr($signature, strlen(self::SIGNATURE_PREFIX));
        }

        // Split timestamp and signature
        $parts = explode('.', $signature);
        if (count($parts) !== 2) {
            return null;
        }

        [$timestamp, $signatureHash] = $parts;

        if (!ctype_digit($timestamp)) {
            return null;
        }

        return [
            'timestamp' => (int) $timestamp,
            'signature' => $signatureHash,
        ];
    }

    /**
     * Validate timestamp against current time with tolerance
     */
    private function validateTimestamp(int $timestamp, int $tolerance): bool
    {
        $currentTime = time();
        $difference = abs($currentTime - $timestamp);
        return $difference <= $tolerance;
    }

    /**
     * Compute signature using HMAC-SHA256
     */
    private function computeSignature(string $payload, string $secret, int $timestamp): string
    {
        $message = $timestamp . '.' . $payload;
        return hash_hmac(self::DEFAULT_ALGORITHM, $message, $secret);
    }

    /**
     * Compare signatures using timing-safe comparison
     */
    private function compareSignatures(string $provided, string $expected): bool
    {
        // Ensure both have same length
        if (strlen($provided) !== strlen($expected)) {
            return false;
        }

        // Timing-safe comparison
        $result = 0;
        for ($i = 0; $i < strlen($provided); $i++) {
            $result |= ord($provided[$i]) ^ ord($expected[$i]);
        }

        return $result === 0;
    }

    /**
     * Generate signature for testing
     */
    public function generateSignature(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signature = $this->computeSignature($payload, $secret, $timestamp);
        return self::SIGNATURE_PREFIX . $timestamp . '.' . $signature;
    }

    /**
     * Extract webhook event type from payload
     */
    public function extractEventType(string $payload): ?string
    {
        $data = json_decode($payload, true);
        return $data['event'] ?? $data['type'] ?? null;
    }

    /**
     * Extract webhook ID from payload
     */
    public function extractWebhookId(string $payload): ?string
    {
        $data = json_decode($payload, true);
        return $data['id'] ?? $data['webhook_id'] ?? null;
    }
}
