<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\DnsLookupResult;

interface DnsLookupServiceInterface
{
    public function checkDomain(string $fullDomain): DnsLookupResult;

    public function checkBatch(array $domains): array;

    public function getCachedResult(string $fullDomain): ?DnsLookupResult;
}