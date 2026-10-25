<?php

namespace Pandascrow\Http;

/**
 * Sends one HTTP request. The default is CurlTransport; pass your own
 * implementation in the client options (`'transport' => ...`) to add logging,
 * retries, or to fake responses in your tests.
 */
interface Transport
{
    /**
     * @param string               $method  GET, POST, PUT, PATCH or DELETE
     * @param string               $url     Absolute URL including the query string
     * @param array<int, string>   $headers e.g. ['Token: pk_...', 'Accept: application/json']
     * @param string|null          $body    Raw request body (JSON) or null
     *
     * @return array{status: int, body: string}
     *
     * @throws \Pandascrow\PandascrowException on network failure
     */
    public function send(string $method, string $url, array $headers, ?string $body, int $timeout): array;
}
