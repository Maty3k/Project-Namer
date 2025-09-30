<?php

declare(strict_types=1);

use App\Models\DnsLookupMetrics;
use App\Services\DnsPerformanceMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DNS Performance Integration', function (): void {
    it('can create and complete performance monitoring batch', function (): void {
        $monitor = new DnsPerformanceMonitorService;

        // Start a batch
        $batchId = $monitor->startBatch('test-batch');

        expect($batchId)->toBe('test-batch');

        // Record some DNS lookups
        $monitor->recordLookup('example.com', 150.5, true, false);
        $monitor->recordLookup('test.com', 200.0, true, true);
        $monitor->recordLookup('error.com', 300.0, false, false, 'DNS timeout');

        // Get current stats
        $stats = $monitor->getCurrentBatchStats();
        expect($stats['batch_id'])->toBe('test-batch')
            ->and($stats['domains_checked'])->toBe(3)
            ->and($stats['successful_lookups'])->toBe(2)
            ->and($stats['failed_lookups'])->toBe(1)
            ->and($stats['cache_hits'])->toBe(1);

        // Complete the batch
        $metrics = $monitor->completeBatch();

        expect($metrics)->toBeInstanceOf(DnsLookupMetrics::class)
            ->and($metrics->batch_id)->toBe('test-batch')
            ->and($metrics->domains_checked)->toBe(3)
            ->and($metrics->successful_lookups)->toBe(2)
            ->and($metrics->failed_lookups)->toBe(1)
            ->and($metrics->cache_hits)->toBe(1);
    });

    it('can get aggregated performance statistics', function (): void {
        $monitor = new DnsPerformanceMonitorService;

        // Create multiple batches
        for ($i = 0; $i < 3; $i++) {
            $batchId = $monitor->startBatch("test-batch-{$i}");
            $monitor->recordLookup("domain{$i}.com", 100.0 + ($i * 50), true, false);
            $monitor->recordLookup("cache{$i}.com", 50.0, true, true);
            $monitor->completeBatch();
        }

        // Get aggregated stats
        $stats = $monitor->getAggregatedStats(5);

        expect($stats['total_batches'])->toBe(3)
            ->and($stats['total_domains_checked'])->toBe(6)
            ->and($stats['total_successful_lookups'])->toBe(6)
            ->and($stats['total_failed_lookups'])->toBe(0)
            ->and($stats['total_cache_hits'])->toBe(3)
            ->and($stats['overall_success_rate'])->toBe(100)
            ->and($stats['overall_cache_hit_rate'])->toBe(50.0);
    });

    it('can check performance thresholds', function (): void {
        $monitor = new DnsPerformanceMonitorService;

        // Create a batch with good performance
        $batchId = $monitor->startBatch('good-performance');
        $monitor->recordLookup('fast.com', 50.0, true, false);
        $monitor->recordLookup('cached.com', 10.0, true, true);
        $monitor->completeBatch();

        $thresholds = $monitor->checkPerformanceThresholds();

        expect($thresholds['healthy'])->toBeTrue()
            ->and($thresholds['issues'])->toBeEmpty();
    });

    it('detects performance threshold violations', function (): void {
        $monitor = new DnsPerformanceMonitorService;

        // Create a batch with poor performance
        $batchId = $monitor->startBatch('poor-performance');
        $monitor->recordLookup('slow.com', 5000.0, false, false, 'Timeout');  // Very slow and failed
        $monitor->recordLookup('broken.com', 4000.0, false, false, 'Error');  // Also slow and failed
        $monitor->completeBatch();

        $thresholds = $monitor->checkPerformanceThresholds();

        expect($thresholds['healthy'])->toBeFalse()
            ->and($thresholds['issues'])->not()->toBeEmpty();

        // Check for specific issues
        $issueTypes = array_column($thresholds['issues'], 'type');
        expect($issueTypes)->toContain('low_success_rate')
            ->and($issueTypes)->toContain('high_lookup_time');
    });

    it('handles empty metrics gracefully', function (): void {
        $monitor = new DnsPerformanceMonitorService;

        // Get stats when no metrics exist
        $stats = $monitor->getAggregatedStats(10);

        expect($stats['total_batches'])->toBe(0)
            ->and($stats['total_domains_checked'])->toBe(0)
            ->and($stats['overall_success_rate'])->toBe(0)
            ->and($stats['overall_cache_hit_rate'])->toBe(0)
            ->and($stats['average_lookup_time'])->toBe(0);
    });
});
