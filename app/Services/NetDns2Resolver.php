<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsResolverInterface;
use NetDNS2\Resolver;

final readonly class NetDns2Resolver implements DnsResolverInterface
{
    public function __construct(
        private Resolver $resolver
    ) {}

    public function query(string $domain, string $type): object
    {
        return $this->resolver->query($domain, $type);
    }
}
