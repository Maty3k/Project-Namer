<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\DnsLookupMetrics;

interface DnsPerformanceMonitorInterface
{
    public function startBatch(string $batchId = null): string;

    public function recordLookup(
        string $domain,
        float $responseTimeMs,
        bool $successful,
        bool $cacheHit = false,
        ?string $error = null
    ): void;

    public function completeBatch(): ?DnsLookupMetrics;

    public function getCurrentBatchStats(): array;

    public function getBatchMetrics(string $batchId): ?array;

    public function getAggregatedStats(int $recentBatches = 10): array;

    public function checkPerformanceThresholds(): array;
}