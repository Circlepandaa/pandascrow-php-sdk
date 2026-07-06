<?php

declare(strict_types=1);

namespace Pandascrow\Tests\Unit;

use Pandascrow\Config;
use Pandascrow\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testConfigWithApiKeyString(): void
    {
        $config = new Config('sk_test_123456');

        $this->assertEquals('sk_test_123456', $config->getApiKey());
        $this->assertTrue($config->isSandbox());
        $this->assertEquals('https://sandbox.pandascrow.io', $config->getBaseUrl());
        $this->assertEquals(30, $config->getTimeout());
        $this->assertEquals(0, $config->getRetries());
        $this->assertFalse($config->isDebug());
    }

    public function testConfigWithApiKeyStringAndOptions(): void
    {
        $config = new Config('sk_test_123456', [
            'sandbox' => false,
            'timeout' => 60,
            'retries' => 3,
            'debug' => true,
        ]);

        $this->assertFalse($config->isSandbox());
        $this->assertEquals('https://api.pandascrow.io', $config->getBaseUrl());
        $this->assertEquals(60, $config->getTimeout());
        $this->assertEquals(3, $config->getRetries());
        $this->assertTrue($config->isDebug());
    }

    public function testConfigWithArray(): void
    {
        $config = new Config([
            'api_key' => 'sk_test_123456',
            'api_secret' => 'secret_789',
            'sandbox' => false,
            'timeout' => 45,
            'retries' => 2,
        ]);

        $this->assertEquals('sk_test_123456', $config->getApiKey());
        $this->assertEquals('secret_789', $config->getApiSecret());
        $this->assertFalse($config->isSandbox());
        // Update these to match actual behavior if needed
        $this->assertEquals(45, $config->getTimeout());
        $this->assertEquals(2, $config->getRetries());
    }

    public function testConfigWithCustomBaseUrl(): void
    {
        $config = new Config('sk_test_123', [
            'base_url' => 'https://custom.api.com/v2'
        ]);

        $this->assertEquals('https://custom.api.com/v2', $config->getBaseUrl());
    }

    public function testConfigWithApiVersion(): void
    {
        $config = new Config('sk_test_123', [
            'api_version' => '2024-01-01'
        ]);

        $this->assertEquals('2024-01-01', $config->getApiVersion());
    }

    public function testConfigWithCustomHeaders(): void
    {
        $config = new Config('sk_test_123', [
            'headers' => [
                'X-Custom' => 'value',
                'X-Test' => 'test'
            ]
        ]);

        $headers = $config->getHeaders();
        $this->assertArrayHasKey('X-Custom', $headers);
        $this->assertEquals('value', $headers['X-Custom']);
        $this->assertArrayHasKey('X-Test', $headers);
        $this->assertEquals('test', $headers['X-Test']);
    }

    public function testConfigSandboxBaseUrl(): void
    {
        $config = new Config('sk_test_123', ['sandbox' => true]);
        $this->assertEquals('https://sandbox.pandascrow.io', $config->getBaseUrl());
    }

    public function testConfigProductionBaseUrl(): void
    {
        $config = new Config('sk_test_123', ['sandbox' => false]);
        $this->assertEquals('https://api.pandascrow.io', $config->getBaseUrl());
    }

    public function testConfigSetBaseUrl(): void
    {
        $config = new Config('sk_test_123');
        $config->setBaseUrl('https://new.api.com/v3');
        $this->assertEquals('https://new.api.com/v3', $config->getBaseUrl());
    }

    public function testConfigSetDebug(): void
    {
        $config = new Config('sk_test_123');
        $this->assertFalse($config->isDebug());

        $config->setDebug(true);
        $this->assertTrue($config->isDebug());
    }

    public function testConfigThrowsExceptionForMissingApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('API key is required');

        new Config([]);
    }

    public function testConfigThrowsExceptionForEmptyApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        new Config('');
    }

    public function testConfigThrowsExceptionForInvalidTimeout(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Timeout must be a positive integer');

        new Config('sk_test_123', ['timeout' => -5]);
    }

    public function testConfigThrowsExceptionForInvalidRetries(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Retries must be a non-negative integer');

        new Config('sk_test_123', ['retries' => -1]);
    }
}
