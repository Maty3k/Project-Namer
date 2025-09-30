<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsPerformanceMonitorInterface;
use App\Models\DnsLookupMetrics;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for monitoring and tracking DNS lookup performance.
 *
 * Collects metrics on DNS lookup operations including response times,
 * success/failure rates, cache hit rates, and overall system performance.
 */
final class DnsPerformanceMonitorService implements DnsPerformanceMonitorInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $batchMetrics = [];

    /**
     * @var array<string, mixed>
     */
    private array $currentBatch = [];

    private ?string $currentBatchId = null;

    private ?\Carbon\Carbon $batchStartTime = null;

    /**
     * Start monitoring a new batch of DNS lookups.
     */
    public function startBatch(?string $batchId = null): string
    {
        $this->currentBatchId = $batchId ?? Str::uuid()->toString();
        $this->batchStartTime = now();
        $this->currentBatch = [
            'batch_id' => $this->currentBatchId,
            'domains_checked' => 0,
            'successful_lookups' => 0,
            'failed_lookups' => 0,
            'cache_hits' => 0,
            'total_lookup_time' => 0.0,
            'individual_times' => [],
            'errors' => [],
        ];

        Log::debug('DNS performance monitoring batch started', [
            'batch_id' => $this->currentBatchId,
            'started_at' => $this->batchStartTime->toISOString(),
        ]);

        return $this->currentBatchId;
    }

    /**
     * Record a DNS lookup operation.
     */
    public function recordLookup(
        string $domain,
        float $responseTimeMs,
        bool $successful,
        bool $cacheHit = false,
        ?string $error = null
    ): void {
        if (! $this->currentBatchId) {
            Log::warning('DNS lookup recorded without active batch', [
                'domain' => $domain,
                'response_time' => $responseTimeMs,
            ]);

            return;
        }

        $this->currentBatch['domains_checked']++;
        $this->currentBatch['total_lookup_time'] += $responseTimeMs;
        $this->currentBatch['individual_times'][] = $responseTimeMs;

        if ($successful) {
            $this->currentBatch['successful_lookups']++;
        } else {
            $this->currentBatch['failed_lookups']++;
            if ($error) {
                $this->currentBatch['errors'][] = [
                    'domain' => $domain,
                    'error' => $error,
                    'timestamp' => now()->toISOString(),
                ];
            }
        }

        if ($cacheHit) {
            $this->currentBatch['cache_hits']++;
        }

        Log::debug('DNS lookup recorded', [
            'batch_id' => $this->currentBatchId,
            'domain' => $domain,
            'response_time_ms' => $responseTimeMs,
            'successful' => $successful,
            'cache_hit' => $cacheHit,
            'error' => $error,
        ]);
    }

    /**
     * Complete the current batch and store metrics.
     */
    public function completeBatch(): ?DnsLookupMetrics
    {
        if (! $this->currentBatchId || ! $this->batchStartTime) {
            Log::warning('Attempted to complete batch without active monitoring');

            return null;
        }

        $completedAt = now();
        $totalProcessingTime = $this->batchStartTime->diffInMilliseconds($completedAt);

        // Calculate average lookup time
        $averageLookupTime = $this->currentBatch['domains_checked'] > 0
            ? $this->currentBatch['total_lookup_time'] / $this->currentBatch['domains_checked']
            : 0.0;

        try {
            $metrics = DnsLookupMetrics::create([
                'batch_id' => $this->currentBatchId,
                'domains_checked' => $this->currentBatch['domains_checked'],
                'successful_lookups' => $this->currentBatch['successful_lookups'],
                'failed_lookups' => $this->currentBatch['failed_lookups'],
                'cache_hits' => $this->currentBatch['cache_hits'],
                'average_lookup_time' => round($averageLookupTime, 3),
                'total_processing_time' => round($totalProcessingTime, 3),
                'started_at' => $this->batchStartTime,
                'completed_at' => $completedAt,
            ]);

            // Log performance summary
            $this->logPerformanceSummary($metrics);

            // Store detailed metrics for analysis
            $this->batchMetrics[$this->currentBatchId] = $this->currentBatch;

            // Reset current batch
            $this->currentBatchId = null;
            $this->batchStartTime = null;
            $this->currentBatch = [];

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Failed to store DNS batch metrics', [
                'batch_id' => $this->currentBatchId,
                'error' => $e->getMessage(),
                'metrics' => $this->currentBatch,
            ]);

            return null;
        }
    }

    /**
     * Get current batch statistics.
     */
    public function getCurrentBatchStats(): array
    {
        if (! $this->currentBatchId) {
            return [];
        }

        $runtime = $this->batchStartTime ? now()->diffInMilliseconds($this->batchStartTime) : 0;

        return [
            'batch_id' => $this->currentBatchId,
            'runtime_ms' => $runtime,
            'domains_checked' => $this->currentBatch['domains_checked'],
            'successful_lookups' => $this->currentBatch['successful_lookups'],
            'failed_lookups' => $this->currentBatch['failed_lookups'],
            'cache_hits' => $this->currentBatch['cache_hits'],
            'success_rate' => $this->currentBatch['domains_checked'] > 0
                ? ($this->currentBatch['successful_lookups'] / $this->currentBatch['domains_checked']) * 100
                : 0,
            'cache_hit_rate' => $this->currentBatch['domains_checked'] > 0
                ? ($this->currentBatch['cache_hits'] / $this->currentBatch['domains_checked']) * 100
                : 0,
        ];
    }

    /**
     * Get performance statistics for a completed batch.
     */
    public function getBatchMetrics(string $batchId): ?array
    {
        return $this->batchMetrics[$batchId] ?? null;
    }

    /**
     * Get aggregated performance statistics across recent batches.
     */
    public function getAggregatedStats(int $recentBatches = 10): array
    {
        $recentMetrics = DnsLookupMetrics::orderBy('created_at', 'desc')
            ->limit($recentBatches)
            ->get();

        if ($recentMetrics->isEmpty()) {
            return [
                'total_batches' => 0,
                'total_domains_checked' => 0,
                'overall_success_rate' => 0,
                'overall_cache_hit_rate' => 0,
                'average_lookup_time' => 0,
                'average_batch_time' => 0,
            ];
        }

        $totalDomains = $recentMetrics->sum('domains_checked');
        $totalSuccessful = $recentMetrics->sum('successful_lookups');
        $totalCacheHits = $recentMetrics->sum('cache_hits');

        return [
            'total_batches' => $recentMetrics->count(),
            'total_domains_checked' => $totalDomains,
            'total_successful_lookups' => $totalSuccessful,
            'total_failed_lookups' => $recentMetrics->sum('failed_lookups'),
            'total_cache_hits' => $totalCacheHits,
            'overall_success_rate' => $totalDomains > 0 ? ($totalSuccessful / $totalDomains) * 100 : 0,
            'overall_cache_hit_rate' => $totalDomains > 0 ? ($totalCacheHits / $totalDomains) * 100 : 0,
            'average_lookup_time' => $recentMetrics->avg('average_lookup_time') ?? 0,
            'average_batch_time' => $recentMetrics->avg('total_processing_time') ?? 0,
            'fastest_batch_time' => $recentMetrics->min('total_processing_time') ?? 0,
            'slowest_batch_time' => $recentMetrics->max('total_processing_time') ?? 0,
        ];
    }

    /**
     * Check if current performance meets acceptable thresholds.
     */
    public function checkPerformanceThresholds(): array
    {
        $stats = $this->getAggregatedStats(5); // Last 5 batches

        $thresholds = [
            'min_success_rate' => config('dns.performance.min_success_rate', 90.0),
            'max_average_lookup_time' => config('dns.performance.max_average_lookup_time', 2000.0),
            'min_cache_hit_rate' => config('dns.performance.min_cache_hit_rate', 30.0),
        ];

        $issues = [];

        if ($stats['overall_success_rate'] < $thresholds['min_success_rate']) {
            $issues[] = [
                'type' => 'low_success_rate',
                'current' => $stats['overall_success_rate'],
                'threshold' => $thresholds['min_success_rate'],
                'message' => 'DNS success rate below acceptable threshold',
            ];
        }

        if ($stats['average_lookup_time'] > $thresholds['max_average_lookup_time']) {
            $issues[] = [
                'type' => 'high_lookup_time',
                'current' => $stats['average_lookup_time'],
                'threshold' => $thresholds['max_average_lookup_time'],
                'message' => 'Average DNS lookup time exceeds threshold',
            ];
        }

        if ($stats['overall_cache_hit_rate'] < $thresholds['min_cache_hit_rate']) {
            $issues[] = [
                'type' => 'low_cache_hit_rate',
                'current' => $stats['overall_cache_hit_rate'],
                'threshold' => $thresholds['min_cache_hit_rate'],
                'message' => 'DNS cache hit rate below optimal threshold',
            ];
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'stats' => $stats,
            'thresholds' => $thresholds,
        ];
    }

    /**
     * Log performance summary for a completed batch.
     */
    private function logPerformanceSummary(DnsLookupMetrics $metrics): void
    {
        $successRate = $metrics->domains_checked > 0
            ? ($metrics->successful_lookups / $metrics->domains_checked) * 100
            : 0;

        $cacheHitRate = $metrics->domains_checked > 0
            ? ($metrics->cache_hits / $metrics->domains_checked) * 100
            : 0;

        Log::info('DNS batch performance summary', [
            'batch_id' => $metrics->batch_id,
            'domains_checked' => $metrics->domains_checked,
            'success_rate' => round($successRate, 2),
            'cache_hit_rate' => round($cacheHitRate, 2),
            'average_lookup_time_ms' => $metrics->average_lookup_time,
            'total_processing_time_ms' => $metrics->total_processing_time,
            'successful_lookups' => $metrics->successful_lookups,
            'failed_lookups' => $metrics->failed_lookups,
            'cache_hits' => $metrics->cache_hits,
        ]);
    }

    /**
     * Get current DNS error rate percentage.
     */
    public function getErrorRate(): float
    {
        $stats = $this->getAggregatedStats(10);

        return 100.0 - $stats['overall_success_rate'];
    }

    /**
     * Get current average DNS response time in milliseconds.
     */
    public function getAverageResponseTime(): float
    {
        $stats = $this->getAggregatedStats(10);

        return $stats['average_lookup_time'];
    }

    /**
     * Get current DNS cache hit rate percentage.
     */
    public function getCacheHitRate(): float
    {
        $stats = $this->getAggregatedStats(10);

        return $stats['overall_cache_hit_rate'];
    }

    /**
     * Get current count of circuit breaker failures.
     */
    public function getCircuitBreakerFailures(): int
    {
        // For now, return 0 - this would integrate with circuit breaker service
        // In a real implementation, this would check circuit breaker state
        return 0;
    }
}
