<?php

declare(strict_types=1);

namespace App\Contracts;

interface DnsResolverInterface
{
    public function query(string $domain, string $type): object;
}