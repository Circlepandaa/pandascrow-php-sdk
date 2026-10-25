<?php

namespace Pandascrow;

use Pandascrow\Http\CurlTransport;
use Pandascrow\Http\Transport;
use Pandascrow\Resources\Auth;
use Pandascrow\Resources\Bank;
use Pandascrow\Resources\Escrow;
use Pandascrow\Resources\Invoice;
use Pandascrow\Resources\Kyc;
use Pandascrow\Resources\PaymentLink;
use Pandascrow\Resources\Payout;
use Pandascrow\Resources\VirtualAccount;
use Pandascrow\Resources\Wallet;

/**
 * Entry point for the Pandascrow v3 API.
 *
 *   $ps = new Pandascrow\Client('pk_test_xxx');                      // sandbox
 *   $ps = new Pandascrow\Client('pk_live_xxx', ['sandbox' => false]); // live
 *
 *   $escrow = $ps->escrow->createOnetime([...]);
 */
class Client
{
    public const VERSION = '1.1.0';
    public const SANDBOX = 'https://sandbox.pandascrow.io';
    public const LIVE    = 'https://api.pandascrow.io';

    /** @var Auth */
    public $auth;
    /** @var Escrow */
    public $escrow;
    /** @var Wallet */
    public $wallet;
    /** @var Bank */
    public $bank;
    /** @var Payout */
    public $payouts;
    /** @var VirtualAccount */
    public $virtualAccounts;
    /** @var Invoice */
    public $invoices;
    /** @var PaymentLink */
    public $paymentLinks;
    /** @var Kyc */
    public $kyc;

    /** @var string */
    private $apiKey;
    /** @var string */
    private $baseUrl;
    /** @var int */
    private $timeout;
    /** @var Transport */
    private $transport;
    /** @var array<int, string> */
    private $extraHeaders = [];

    /**
     * @param string $apiKey Public key of your application (Dashboard → Developers). Sent as the `Token` header.
     * @param array{
     *     sandbox?: bool,
     *     base_url?: string,
     *     timeout?: int,
     *     connect_timeout?: int,
     *     headers?: array<string, string>,
     *     transport?: Transport
     * } $options
     *   - sandbox:  true (default) → https://sandbox.pandascrow.io, false → https://api.pandascrow.io
     *   - base_url: override the API host entirely (e.g. a local API during development)
     *   - timeout / connect_timeout: seconds (default 30 / 10)
     *   - headers:  extra headers sent on every request
     *   - transport: custom Http\Transport (logging, retries, test fakes)
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('A Pandascrow API key is required. Find it in your dashboard under Developers.');
        }

        $sandbox         = isset($options['sandbox']) ? (bool) $options['sandbox'] : true;
        $this->apiKey    = $apiKey;
        $this->baseUrl   = rtrim(isset($options['base_url']) ? (string) $options['base_url'] : ($sandbox ? self::SANDBOX : self::LIVE), '/');
        $this->timeout   = isset($options['timeout']) ? (int) $options['timeout'] : 30;
        $this->transport = isset($options['transport']) && $options['transport'] instanceof Transport
            ? $options['transport']
            : new CurlTransport(isset($options['connect_timeout']) ? (int) $options['connect_timeout'] : 10);

        foreach (($options['headers'] ?? []) as $name => $value) {
            $this->extraHeaders[] = $name . ': ' . $value;
        }

        $this->auth            = new Auth($this);
        $this->escrow          = new Escrow($this);
        $this->wallet          = new Wallet($this);
        $this->bank            = new Bank($this);
        $this->payouts         = new Payout($this);
        $this->virtualAccounts = new VirtualAccount($this);
        $this->invoices        = new Invoice($this);
        $this->paymentLinks    = new PaymentLink($this);
        $this->kyc             = new Kyc($this);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function post(string $path, array $body = [], array $query = []): array
    {
        return $this->request('POST', $path, $query, $body);
    }

    /**
     * Low-level call, and your escape hatch for endpoints without a helper:
     *
     *   $ps->request('GET', '/clients', ['uuid' => $uuid]);
     *
     * Returns the `data` object of the Pandascrow response.
     *
     * @param array<string, mixed>      $query
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     *
     * @throws PandascrowException
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $method = strtoupper($method);
        $url    = $this->baseUrl . '/' . ltrim($path, '/');

        $query = self::clean($query);
        if ($query) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }

        $headers = array_merge([
            'Token: ' . $this->apiKey,
            'Accept: application/json',
            'User-Agent: pandascrow-php/' . self::VERSION . ' (PHP ' . PHP_VERSION . ')',
        ], $this->extraHeaders);

        $payload = null;
        if ($body !== null && $method !== 'GET') {
            $payload = json_encode(self::clean($body), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                throw new PandascrowException('Could not encode request body as JSON: ' . json_last_error_msg());
            }
            $headers[] = 'Content-Type: application/json';
        }

        $response = $this->transport->send($method, $url, $headers, $payload, $this->timeout);

        return self::parse((int) $response['status'], (string) $response['body']);
    }

    /**
     * Turn a raw HTTP response into the `data` array, or throw.
     *
     * @return array<string, mixed>
     * @throws PandascrowException
     */
    public static function parse(int $status, string $raw): array
    {
        $json = self::decode($raw);

        if ($json === null) {
            $snippet = trim(substr(strip_tags($raw), 0, 200));
            throw new PandascrowException(
                'Unexpected response from Pandascrow (HTTP ' . $status . ')' . ($snippet !== '' ? ': ' . $snippet : ''),
                $status
            );
        }

        $flag = $json['status'] ?? null;
        $ok   = $flag === true || $flag === 'true' || $flag === 1 || $flag === 'success';

        if ($status >= 400 || !$ok) {
            $data    = (isset($json['data']) && is_array($json['data'])) ? $json['data'] : [];
            $message = $data['message'] ?? $json['message'] ?? $data['error'] ?? $json['error'] ?? null;
            throw new PandascrowException(
                is_string($message) && $message !== '' ? $message : 'Pandascrow request failed (HTTP ' . $status . ')',
                $status,
                isset($data['doc_url']) && is_string($data['doc_url']) ? $data['doc_url'] : null,
                $json
            );
        }

        if (array_key_exists('data', $json)) {
            return is_array($json['data']) ? $json['data'] : ['value' => $json['data']];
        }

        // Some endpoints answer { "status": true, "message": "..." } with no data object
        unset($json['status']);
        return $json;
    }

    /**
     * Decode JSON, tolerating PHP notices/HTML printed before the JSON body.
     *
     * @return array<string, mixed>|null
     */
    private static function decode(string $raw): ?array
    {
        $raw  = trim($raw);
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }

        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json = json_decode(substr($raw, $start, $end - $start + 1), true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    /**
     * Drop null values so optional arguments are simply not sent.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function clean(array $data): array
    {
        return array_filter($data, static function ($v) {
            return $v !== null;
        });
    }
}
