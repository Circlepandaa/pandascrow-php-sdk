<?php

namespace Pandascrow\Http;

use Pandascrow\PandascrowException;

class CurlTransport implements Transport
{
    /** @var int */
    private $connectTimeout;

    public function __construct(int $connectTimeout = 10)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function send(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new PandascrowException('Could not initialise cURL');
        }

        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $options);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        $errno  = curl_errno($ch);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        if ($raw === false || $errno !== 0) {
            throw new PandascrowException('Network error contacting Pandascrow: ' . ($error !== '' ? $error : 'unknown error'));
        }

        return ['status' => $status, 'body' => (string) $raw];
    }
}
