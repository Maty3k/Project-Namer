<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\DnsLookupMetrics;

interface DnsPerformanceMonitorInterface
{
    public function startBatch(?string $batchId = null): string;

    public function recordLookup(
        string $domain,
        float $responseTimeMs,
        bool $successful,
        bool $cacheHit = false,
        ?string $error = null
    ): void;

    public function completeBatch(): ?DnsLookupMetrics;

    /**
     * @return array<string, mixed>
     */
    public function getCurrentBatchStats(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function getBatchMetrics(string $batchId): ?array;

    /**
     * @return array<string, mixed>
     */
    public function getAggregatedStats(int $recentBatches = 10): array;

    /**
     * @return array<string, mixed>
     */
    public function checkPerformanceThresholds(): array;

    /**
     * Get current error rate percentage.
     */
    public function getErrorRate(): float;

    /**
     * Get average response time in milliseconds.
     */
    public function getAverageResponseTime(): float;

    /**
     * Get cache hit rate percentage.
     */
    public function getCacheHitRate(): float;

    /**
     * Get number of circuit breaker failures.
     */
    public function getCircuitBreakerFailures(): int;
}
