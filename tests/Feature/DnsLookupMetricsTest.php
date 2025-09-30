<?php

declare(strict_types=1);

use App\Models\DnsLookupMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DNS Lookup Metrics', function (): void {
    it('can create DNS metrics with valid data', function (): void {
        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'batch-123',
            'domains_checked' => 10,
            'successful_lookups' => 8,
            'failed_lookups' => 2,
            'cache_hits' => 3,
            'average_lookup_time' => 150.250,
            'total_processing_time' => 1502.500,
            'started_at' => now(),
            'completed_at' => now()->addSeconds(2),
        ]);

        expect($metrics->batch_id)->toBe('batch-123')
            ->and($metrics->domains_checked)->toBe(10)
            ->and($metrics->successful_lookups)->toBe(8)
            ->and($metrics->failed_lookups)->toBe(2)
            ->and($metrics->cache_hits)->toBe(3)
            ->and($metrics->average_lookup_time)->toBe('150.250')
            ->and($metrics->total_processing_time)->toBe('1502.500');
    });

    it('casts decimal fields correctly', function (): void {
        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'decimal-test',
            'domains_checked' => 5,
            'successful_lookups' => 5,
            'failed_lookups' => 0,
            'cache_hits' => 2,
            'average_lookup_time' => '123.456',
            'total_processing_time' => '789.123',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        expect($metrics->average_lookup_time)->toBe('123.456')
            ->and($metrics->total_processing_time)->toBe('789.123');
    });

    it('casts datetime fields correctly', function (): void {
        $startTime = now();
        $endTime = now()->addSeconds(5);

        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'datetime-test',
            'domains_checked' => 1,
            'successful_lookups' => 1,
            'failed_lookups' => 0,
            'cache_hits' => 0,
            'average_lookup_time' => 100.0,
            'total_processing_time' => 100.0,
            'started_at' => $startTime,
            'completed_at' => $endTime,
        ]);

        expect($metrics->started_at)->toBeInstanceOf(\Carbon\Carbon::class)
            ->and($metrics->completed_at)->toBeInstanceOf(\Carbon\Carbon::class);

        // Check that times are close (within 1 second)
        expect($metrics->started_at->diffInSeconds($startTime))->toBeLessThanOrEqual(1)
            ->and($metrics->completed_at->diffInSeconds($endTime))->toBeLessThanOrEqual(1);
    });

    it('calculates success rate correctly', function (): void {
        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'success-rate-test',
            'domains_checked' => 20,
            'successful_lookups' => 16,
            'failed_lookups' => 4,
            'cache_hits' => 5,
            'average_lookup_time' => 125.0,
            'total_processing_time' => 2500.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Add method to calculate success rate
        $successRate = ($metrics->successful_lookups / $metrics->domains_checked) * 100;

        expect($successRate)->toBe(80.0);
    });

    it('calculates cache hit rate correctly', function (): void {
        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'cache-hit-test',
            'domains_checked' => 15,
            'successful_lookups' => 12,
            'failed_lookups' => 3,
            'cache_hits' => 6,
            'average_lookup_time' => 75.5,
            'total_processing_time' => 906.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Add method to calculate cache hit rate
        $cacheHitRate = ($metrics->cache_hits / $metrics->domains_checked) * 100;

        expect($cacheHitRate)->toBe(40.0);
    });

    it('handles zero domains checked gracefully', function (): void {
        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'zero-domains-test',
            'domains_checked' => 0,
            'successful_lookups' => 0,
            'failed_lookups' => 0,
            'cache_hits' => 0,
            'average_lookup_time' => 0.0,
            'total_processing_time' => 0.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        expect($metrics->domains_checked)->toBe(0)
            ->and($metrics->successful_lookups)->toBe(0)
            ->and($metrics->failed_lookups)->toBe(0);
    });

    it('can query metrics by batch ID', function (): void {
        DnsLookupMetrics::create([
            'batch_id' => 'query-test-1',
            'domains_checked' => 5,
            'successful_lookups' => 5,
            'failed_lookups' => 0,
            'cache_hits' => 2,
            'average_lookup_time' => 100.0,
            'total_processing_time' => 500.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        DnsLookupMetrics::create([
            'batch_id' => 'query-test-2',
            'domains_checked' => 3,
            'successful_lookups' => 2,
            'failed_lookups' => 1,
            'cache_hits' => 1,
            'average_lookup_time' => 200.0,
            'total_processing_time' => 600.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $metrics1 = DnsLookupMetrics::where('batch_id', 'query-test-1')->first();
        $metrics2 = DnsLookupMetrics::where('batch_id', 'query-test-2')->first();

        expect($metrics1->domains_checked)->toBe(5)
            ->and($metrics2->domains_checked)->toBe(3);
    });

    it('can aggregate metrics across batches', function (): void {
        DnsLookupMetrics::create([
            'batch_id' => 'agg-test-1',
            'domains_checked' => 10,
            'successful_lookups' => 8,
            'failed_lookups' => 2,
            'cache_hits' => 4,
            'average_lookup_time' => 100.0,
            'total_processing_time' => 1000.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        DnsLookupMetrics::create([
            'batch_id' => 'agg-test-2',
            'domains_checked' => 15,
            'successful_lookups' => 12,
            'failed_lookups' => 3,
            'cache_hits' => 6,
            'average_lookup_time' => 150.0,
            'total_processing_time' => 2250.0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $totalDomains = DnsLookupMetrics::whereIn('batch_id', ['agg-test-1', 'agg-test-2'])
            ->sum('domains_checked');
        $totalSuccessful = DnsLookupMetrics::whereIn('batch_id', ['agg-test-1', 'agg-test-2'])
            ->sum('successful_lookups');
        $totalCacheHits = DnsLookupMetrics::whereIn('batch_id', ['agg-test-1', 'agg-test-2'])
            ->sum('cache_hits');

        expect($totalDomains)->toBe(25)
            ->and($totalSuccessful)->toBe(20)
            ->and($totalCacheHits)->toBe(10);
    });

    it('validates required fields', function (): void {
        expect(fn () => DnsLookupMetrics::create([]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('stores processing duration accurately', function (): void {
        $metrics = DnsLookupMetrics::create([
            'batch_id' => 'duration-test',
            'domains_checked' => 1,
            'successful_lookups' => 1,
            'failed_lookups' => 0,
            'cache_hits' => 0,
            'average_lookup_time' => 2000.0,
            'total_processing_time' => 2000.0,
            'started_at' => '2023-01-01 10:00:00',
            'completed_at' => '2023-01-01 10:00:02',
        ]);

        expect($metrics->started_at->format('Y-m-d H:i:s'))->toBe('2023-01-01 10:00:00')
            ->and($metrics->completed_at->format('Y-m-d H:i:s'))->toBe('2023-01-01 10:00:02')
            ->and($metrics->total_processing_time)->toBe('2000.000');
    });
});
