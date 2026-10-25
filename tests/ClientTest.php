<?php

namespace Pandascrow\Tests;

use Pandascrow\Client;
use Pandascrow\PandascrowException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /** @var FakeTransport */
    private $http;
    /** @var Client */
    private $ps;

    protected function setUp(): void
    {
        $this->http = new FakeTransport();
        $this->ps   = new Client('pk_test_123', ['transport' => $this->http]);
    }

    public function testDefaultsToSandbox(): void
    {
        $this->assertSame('https://sandbox.pandascrow.io', $this->ps->getBaseUrl());
        $live = new Client('pk_live', ['sandbox' => false, 'transport' => $this->http]);
        $this->assertSame('https://api.pandascrow.io', $live->getBaseUrl());
        $custom = new Client('pk', ['base_url' => 'http://localhost:8000/', 'transport' => $this->http]);
        $this->assertSame('http://localhost:8000', $custom->getBaseUrl());
    }

    public function testRejectsEmptyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('  ');
    }

    public function testSendsTokenHeaderAndJsonBody(): void
    {
        $this->http->push(200, ['status' => true, 'data' => ['ok' => 1]]);
        $out = $this->ps->post('/escrow/initialize', ['title' => 'Rent', 'description' => null, 'url' => 'https://x.io/a']);

        $r = $this->http->last();
        $this->assertSame(['ok' => 1], $out);
        $this->assertSame('POST', $r['method']);
        $this->assertSame('https://sandbox.pandascrow.io/escrow/initialize', $r['url']);
        $this->assertContains('Token: pk_test_123', $r['headers']);
        $this->assertContains('Content-Type: application/json', $r['headers']);
        $this->assertSame(['title' => 'Rent', 'url' => 'https://x.io/a'], $r['json'], 'nulls are dropped');
        $this->assertStringContainsString('https://x.io/a', (string) $r['body'], 'slashes are not escaped');
    }

    public function testGetUsesQueryStringAndNoBody(): void
    {
        $this->ps->get('/escrow/single', ['uuid' => 'u1', 'escrow_id' => 5, 'skip' => null]);
        $r = $this->http->last();
        $this->assertSame('GET', $r['method']);
        $this->assertNull($r['body']);
        $this->assertSame(['uuid' => 'u1', 'escrow_id' => '5'], $r['query']);
    }

    public function testErrorEnvelopeThrowsWithMessageAndDocUrl(): void
    {
        $this->http->push(400, ['status' => false, 'data' => ['message' => 'Escrow type is required.', 'doc_url' => 'https://docs/x']]);
        try {
            $this->ps->post('/escrow/initialize', []);
            $this->fail('expected exception');
        } catch (PandascrowException $e) {
            $this->assertSame('Escrow type is required.', $e->getMessage());
            $this->assertSame(400, $e->httpStatus);
            $this->assertSame('https://docs/x', $e->getDocUrl());
            $this->assertFalse($e->getResponse()['status']);
        }
    }

    public function testTopLevelMessageErrorAndStatusFalseOn200(): void
    {
        $this->http->push(200, ['status' => false, 'message' => 'Endpoint not found.']);
        $this->expectException(PandascrowException::class);
        $this->expectExceptionMessage('Endpoint not found.');
        $this->ps->get('/nope');
    }

    public function testAuthErrorFromMiddleware(): void
    {
        $this->http->push(401, ['status' => false, 'message' => 'Unauthorized: Invalid public key']);
        try {
            $this->ps->get('/wallets', ['uuid' => 'u']);
            $this->fail('expected exception');
        } catch (PandascrowException $e) {
            $this->assertSame(401, $e->httpStatus);
            $this->assertSame('Unauthorized: Invalid public key', $e->getMessage());
        }
    }

    public function testToleratesPhpNoticesBeforeJson(): void
    {
        $this->http->push(200, "<br />\n<b>Warning</b>: Undefined index in x.php<br />\n{\"status\":true,\"data\":{\"id\":7}}");
        $this->assertSame(['id' => 7], $this->ps->get('/x'));
    }

    public function testNonJsonResponseThrowsReadableError(): void
    {
        $this->http->push(502, '<html><body>Bad Gateway</body></html>');
        $this->expectException(PandascrowException::class);
        $this->expectExceptionMessage('Unexpected response from Pandascrow (HTTP 502): Bad Gateway');
        $this->ps->get('/x');
    }

    public function testMessageOnlySuccessIsReturned(): void
    {
        $this->http->push(200, ['status' => true, 'message' => 'OTP sent']);
        $this->assertSame(['message' => 'OTP sent'], $this->ps->post('/x'));
    }
}
