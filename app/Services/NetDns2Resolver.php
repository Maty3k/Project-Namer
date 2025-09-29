<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsResolverInterface;
use NetDNS2\Packet\Response;
use NetDNS2\Resolver;

final class NetDns2Resolver implements DnsResolverInterface
{
    public function __construct(
        private readonly Resolver $resolver
    ) {}

    public function query(string $domain, string $type): object
    {
        return $this->resolver->query($domain, $type);
    }
}