<?php

declare(strict_types=1);

namespace Pandascrow\Utils;

use Pandascrow\Exceptions\WebhookException;

class SignatureValidator
{
    private const DEFAULT_ALGORITHM = 'sha256';
    private const SIGNATURE_PREFIX = 'v1=';

    /**
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @param int $tolerance
     * @return bool
     * @throws WebhookException
     */
    public function validate(
        string $payload,
        string $signature,
        string $secret,
        int $tolerance = 300
    ): bool {
        $parsed = $this->parseSignature($signature);

        if ($parsed === null) {
            throw new WebhookException('Invalid signature format');
        }

        if (!$this->validateTimestamp($parsed['timestamp'], $tolerance)) {
            throw new WebhookException('Webhook timestamp is too old or in the future');
        }

        $expected = $this->computeSignature($payload, $secret, $parsed['timestamp']);
        $provided = $parsed['signature'];

        if (!$this->compareSignatures($provided, $expected)) {
            throw new WebhookException('Signature mismatch');
        }

        return true;
    }

    /**
     * @param string $signature
     * @return array{timestamp: int, signature: string}|null
     */
    private function parseSignature(string $signature): ?array
    {
        if (strpos($signature, self::SIGNATURE_PREFIX) === 0) {
            $signature = substr($signature, strlen(self::SIGNATURE_PREFIX));
        }

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

    private function validateTimestamp(int $timestamp, int $tolerance): bool
    {
        $currentTime = time();
        $difference = abs($currentTime - $timestamp);
        return $difference <= $tolerance;
    }

    private function computeSignature(string $payload, string $secret, int $timestamp): string
    {
        $message = $timestamp . '.' . $payload;
        return hash_hmac(self::DEFAULT_ALGORITHM, $message, $secret);
    }

    private function compareSignatures(string $provided, string $expected): bool
    {
        if (strlen($provided) !== strlen($expected)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($provided); $i++) {
            $result |= ord($provided[$i]) ^ ord($expected[$i]);
        }

        return $result === 0;
    }

    public function generateSignature(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signature = $this->computeSignature($payload, $secret, $timestamp);
        return self::SIGNATURE_PREFIX . $timestamp . '.' . $signature;
    }

    public function extractEventType(string $payload): ?string
    {
        /** @var array{event?: string, type?: string} $data */
        $data = json_decode($payload, true);
        return $data['event'] ?? $data['type'] ?? null;
    }

    public function extractWebhookId(string $payload): ?string
    {
        /** @var array{id?: string, webhook_id?: string} $data */
        $data = json_decode($payload, true);
        return $data['id'] ?? $data['webhook_id'] ?? null;
    }
}
