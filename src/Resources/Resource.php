<?php
namespace Pandascrow\Resources;

use Pandascrow\Client;

abstract class Resource
{
    protected $client;
    public function __construct(Client $client) { $this->client = $client; }
}
