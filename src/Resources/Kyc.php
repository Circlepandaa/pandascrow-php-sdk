<?php
namespace Pandascrow\Resources;

class Kyc extends Resource
{
    public function bvn(string $bvn): array { return $this->client->get('/kyc/ng/lookup/bvn', ['bvn' => $bvn]); }
    public function nin(string $nin): array { return $this->client->get('/kyc/ng/lookup/nin', ['nin' => $nin]); }
}
