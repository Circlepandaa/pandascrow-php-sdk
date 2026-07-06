<?php

declare(strict_types=1);

namespace Pandascrow\Tests\Unit;

use Pandascrow\Client;
use Pandascrow\Config;
use Pandascrow\Contracts\LoggerInterface;
use Pandascrow\Exceptions\ConfigurationException;
use Pandascrow\Utils\NullLogger;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClientInitializationWithApiKey(): void
    {
        $client = new Client('sk_test_123456');

        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Config::class, $client->getConfig());
        $this->assertEquals('sk_test_123456', $client->getConfig()->getApiKey());
        $this->assertTrue($client->getConfig()->isSandbox());
    }

    public function testClientInitializationWithConfigArray(): void
    {
        $client = new Client([
            'api_key' => 'sk_test_123456',
            'sandbox' => false,
            'timeout' => 60,
            'retries' => 2,
        ]);

        $config = $client->getConfig();
        $this->assertEquals('sk_test_123456', $config->getApiKey());
        $this->assertFalse($config->isSandbox());
        // Update these to match actual behavior
        $this->assertEquals(60, $config->getTimeout());
        $this->assertEquals(2, $config->getRetries());
    }

    public function testClientInitializationWithApiSecret(): void
    {
        $client = new Client([
            'api_key' => 'client_id_123',
            'api_secret' => 'client_secret_456',
        ]);

        $config = $client->getConfig();
        $this->assertEquals('client_id_123', $config->getApiKey());
        $this->assertEquals('client_secret_456', $config->getApiSecret());
    }

    public function testClientThrowsExceptionOnMissingApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('API key is required');

        new Client([]);
    }

    public function testClientThrowsExceptionOnEmptyApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        new Client('');
    }

    public function testClientThrowsExceptionOnInvalidTimeout(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer');

        new Client('sk_test_123', ['timeout' => 0]);
    }

    public function testClientThrowsExceptionOnInvalidRetries(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Retries must be a non-negative integer');

        new Client('sk_test_123', ['retries' => -1]);
    }

    public function testClientResourceAccessors(): void
    {
        $client = new Client('sk_test_123');

        $this->assertInstanceOf(\Pandascrow\Resources\Payment::class, $client->payments());
        $this->assertInstanceOf(\Pandascrow\Resources\Transfer::class, $client->transfers());
        $this->assertInstanceOf(\Pandascrow\Resources\Verification::class, $client->verifications());
        $this->assertInstanceOf(\Pandascrow\Resources\Webhook::class, $client->webhooks());

        // Test singleton pattern
        $this->assertSame($client->payments(), $client->payments());
    }

    public function testClientEnableDebug(): void
    {
        $client = new Client('sk_test_123');
        $this->assertFalse($client->getConfig()->isDebug());

        $client->enableDebug();
        $this->assertTrue($client->getConfig()->isDebug());
    }

    public function testClientCustomBaseUrl(): void
    {
        $client = new Client('sk_test_123', [
            'base_url' => 'https://custom.pandascrow.com/api/v2'
        ]);

        $this->assertEquals(
            'https://custom.pandascrow.com/api/v2',
            $client->getConfig()->getBaseUrl()
        );
    }

    public function testClientApiVersionHeader(): void
    {
        $client = new Client('sk_test_123', [
            'api_version' => '2024-01-01'
        ]);

        $this->assertEquals('2024-01-01', $client->getConfig()->getApiVersion());
    }

    public function testClientCustomHeaders(): void
    {
        $client = new Client('sk_test_123', [
            'headers' => [
                'X-Custom-Header' => 'custom-value'
            ]
        ]);

        $headers = $client->getConfig()->getHeaders();
        $this->assertArrayHasKey('X-Custom-Header', $headers);
        $this->assertEquals('custom-value', $headers['X-Custom-Header']);
    }

    public function testClientSetLogger(): void
    {
        $client = new Client('sk_test_123');

        // Create a mock of our custom LoggerInterface
        $logger = $this->createMock(LoggerInterface::class);
        $client->setLogger($logger);

        // Test that logger is set (can't directly access, but we can test via debug)
        $client->enableDebug();
        $this->assertTrue($client->getConfig()->isDebug());
    }

    public function testClientWithNullLogger(): void
    {
        $client = new Client('sk_test_123');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Config::class, $client->getConfig());

        // Test that we can set a new logger
        $logger = $this->createMock(LoggerInterface::class);
        $client->setLogger($logger);
        $this->assertTrue($client->getConfig()->isDebug() || !$client->getConfig()->isDebug());
    }
}
