<?php

namespace Pandascrow\Tests;

use Pandascrow\PandascrowException;
use Pandascrow\Webhook;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    private const SECRET = 'sk_test_secret';

    /** Builds a body exactly like the API does (unescaped slashes + unicode). */
    private function body(array $data, ?int $ts = null): string
    {
        return (string) json_encode(
            ['event' => 'escrow.completed', 'data' => $data, 'timestamp' => $ts ?? time()],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function testVerifiesRawBodySignature(): void
    {
        $raw = $this->body(['escrow_id' => 9, 'payment_url' => 'https://pay.io/x', 'name' => 'Ọlá ₦', 'metadata' => ['property_id' => 'PR-204']]);
        $event = Webhook::verify(self::SECRET, $raw, hash_hmac('sha256', $raw, self::SECRET));

        $this->assertSame('escrow.completed', $event['event']);
        $this->assertSame('PR-204', $event['data']['metadata']['property_id']);
    }

    public function testRejectsTamperedBody(): void
    {
        $raw = $this->body(['amount' => 100]);
        $sig = Webhook::sign(self::SECRET, $raw);
        $this->expectException(PandascrowException::class);
        $this->expectExceptionCode(401);
        Webhook::verify(self::SECRET, str_replace('100', '900', $raw), $sig);
    }

    public function testRejectsWrongSecret(): void
    {
        $raw = $this->body([]);
        $this->expectException(PandascrowException::class);
        Webhook::verify(self::SECRET, $raw, hash_hmac('sha256', $raw, 'other'));
    }

    public function testRejectsStaleTimestampUnlessDisabled(): void
    {
        $raw = $this->body([], time() - 3600);
        $sig = Webhook::sign(self::SECRET, $raw);
        $this->assertSame([], Webhook::verify(self::SECRET, $raw, $sig, 0)['data']);

        $this->expectException(PandascrowException::class);
        Webhook::verify(self::SECRET, $raw, $sig);
    }

    public function testReadsSignatureFromServerHeader(): void
    {
        $raw = $this->body(['x' => 1]);
        $_SERVER['HTTP_X_PANDASCROW_SIGNATURE'] = strtoupper(Webhook::sign(self::SECRET, $raw));
        try {
            $this->assertSame(1, Webhook::verify(self::SECRET, $raw)['data']['x']);
        } finally {
            unset($_SERVER['HTTP_X_PANDASCROW_SIGNATURE']);
        }
    }
}
