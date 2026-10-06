<?php
namespace Pandascrow;

use Pandascrow\Resources\{Auth, Escrow, Wallet, Bank, VirtualAccount, Invoice, PaymentLink, Kyc};

class Client
{
    const SANDBOX = 'https://sandbox.pandascrow.io';
    const LIVE    = 'https://api.pandascrow.io';

    private $apiKey;
    private $baseUrl;
    private $timeout;

    public $auth, $escrow, $wallet, $bank, $virtualAccounts, $invoices, $paymentLinks, $kyc;

    /**
     * @param string $apiKey  Your API key from the dashboard (sent in the `Token` header).
     * @param array  $options ['sandbox' => bool (default true), 'base_url' => string, 'timeout' => int]
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $this->apiKey  = $apiKey;
        $sandbox       = $options['sandbox'] ?? true;
        $this->baseUrl = rtrim($options['base_url'] ?? ($sandbox ? self::SANDBOX : self::LIVE), '/');
        $this->timeout = $options['timeout'] ?? 30;

        $this->auth            = new Auth($this);
        $this->escrow          = new Escrow($this);
        $this->wallet          = new Wallet($this);
        $this->bank            = new Bank($this);
        $this->virtualAccounts = new VirtualAccount($this);
        $this->invoices        = new Invoice($this);
        $this->paymentLinks    = new PaymentLink($this);
        $this->kyc             = new Kyc($this);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    public function post(string $path, array $body = [], array $query = []): array
    {
        return $this->request('POST', $path, $query, $body);
    }

    /**
     * Low-level call; also your escape hatch for endpoints without a helper.
     * Returns the `data` object of the Pandascrow envelope; throws PandascrowException on failure.
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        $headers = ['Token: ' . $this->apiKey, 'Accept: application/json'];
        $opts = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new PandascrowException('Network error: ' . $err);
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new PandascrowException('Invalid JSON response (HTTP ' . $status . ')', $status);
        }

        $ok = ($json['status'] ?? false) === true || ($json['status'] ?? null) === 'true';
        if ($status >= 400 || !$ok) {
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];
            throw new PandascrowException(
                $data['message'] ?? $json['message'] ?? 'Request failed',
                $status,
                $data['doc_url'] ?? null,
                $json
            );
        }

        $data = $json['data'] ?? [];
        // Some endpoints (e.g. bank transfers) return extra top-level keys like `metadata`.
        if (is_array($data) && isset($json['metadata'])) {
            $data['_metadata'] = $json['metadata'];
        }
        return is_array($data) ? $data : ['value' => $data];
    }
}
