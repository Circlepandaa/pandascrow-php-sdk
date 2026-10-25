<?php

namespace Pandascrow\Tests;

use Pandascrow\Http\Transport;

/** Records requests and replays queued responses. */
class FakeTransport implements Transport
{
    /** @var array<int, array{method: string, url: string, headers: array<int, string>, body: ?string}> */
    public $requests = [];

    /** @var array<int, array{status: int, body: string}> */
    private $queue = [];

    public function push(int $status, $body): self
    {
        $this->queue[] = ['status' => $status, 'body' => is_string($body) ? $body : (string) json_encode($body)];
        return $this;
    }

    public function send(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        $this->requests[] = compact('method', 'url', 'headers', 'body');
        return $this->queue ? array_shift($this->queue) : ['status' => 200, 'body' => '{"status":true,"data":{}}'];
    }

    /** @return array{method: string, url: string, headers: array<int, string>, body: ?string, path: string, query: array<string, mixed>, json: ?array<string, mixed>} */
    public function last(): array
    {
        $r = end($this->requests);
        $parts = parse_url($r['url']);
        parse_str($parts['query'] ?? '', $query);
        $r['path']  = $parts['path'] ?? '/';
        $r['query'] = $query;
        $r['json']  = $r['body'] !== null ? json_decode($r['body'], true) : null;
        return $r;
    }
}
