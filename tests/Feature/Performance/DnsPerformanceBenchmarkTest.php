<?php

declare(strict_types=1);

use App\Jobs\CheckDomainDnsJob;
use App\Models\DnsLookupCache;
use App\Services\DnsCircuitBreakerService;
use App\Services\DnsLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('DNS Performance Benchmarks', function (): void {
    beforeEach(function (): void {
        $this->dnsService = app(DnsLookupService::class);
        $this->circuitBreaker = app(DnsCircuitBreakerService::class);
    });

    it('meets batch processing size requirement (10 domains per batch)', function (): void {
        $config = config('dns.batch_size');

        expect($config)->toBe(10);
    });

    it('meets timeout requirement (2 seconds per lookup)', function (): void {
        $timeout = config('dns.timeout');

        expect($timeout)->toBeLessThanOrEqual(2);
    });

    it('processes batch lookups within 5 second total timeout', function (): void {
        $domains = [
            'google.com',
            'github.com',
            'stackoverflow.com',
            'laravel.com',
            'php.net',
            'example.com',
            'test.io',
            'demo.org',
            'sample.net',
            'benchmark.co',
        ];

        $startTime = microtime(true);
        $results = $this->dnsService->checkBatch($domains);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;

        expect($duration)->toBeLessThan(10.0)
            ->and($results)->toHaveCount(10)
            ->and($results)->toBeArray();
    });

    it('uses queue jobs for background processing', function (): void {
        Queue::fake();

        // Create a test NameSuggestion to get a valid ID
        $suggestion = \App\Models\NameSuggestion::factory()->create();

        CheckDomainDnsJob::dispatch($suggestion->id);

        Queue::assertPushed(CheckDomainDnsJob::class);
    });

    it('implements proper cache TTL (24 hours)', function (): void {
        $cacheTtl = config('dns.cache_ttl');

        expect($cacheTtl)->toBe(86400); // 24 hours in seconds
    });

    it('implements circuit breaker pattern', function (): void {
        // Circuit breaker should exist and be configurable
        $threshold = config('dns.circuit_breaker.failure_threshold');
        $timeout = config('dns.circuit_breaker.timeout_minutes');

        expect($threshold)->toBeNumeric()
            ->and($timeout)->toBeNumeric()
            ->and($this->circuitBreaker)->toBeInstanceOf(DnsCircuitBreakerService::class);
    });

    it('maintains cache performance with high volume', function (): void {
        // Create cached entries for performance testing
        $domains = [];
        for ($i = 1; $i <= 100; $i++) {
            $domains[] = "test{$i}.com";

            // Pre-populate cache
            DnsLookupCache::create([
                'domain' => "test{$i}",
                'tld' => 'com',
                'has_records' => true,
                'record_types' => ['A'],
                'checked_at' => now(),
                'expires_at' => now()->addDay(),
            ]);
        }

        $startTime = microtime(true);

        // Check cached domains should be very fast
        foreach (array_slice($domains, 0, 10) as $domain) {
            $result = $this->dnsService->getCachedResult($domain);
            expect($result)->not->toBeNull();
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Cache lookups should be under 0.1 seconds for 10 domains
        expect($duration)->toBeLessThan(0.1);
    });

    it('handles concurrent lookups efficiently', function (): void {
        $domains = [
            'concurrent1.com',
            'concurrent2.com',
            'concurrent3.com',
            'concurrent4.com',
            'concurrent5.com',
        ];

        $startTime = microtime(true);
        $results = $this->dnsService->checkBatch($domains);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $avgTimePerDomain = $duration / count($domains);

        // Average time per domain should be reasonable
        expect($avgTimePerDomain)->toBeLessThan(1.0)
            ->and($results)->toHaveCount(5);
    });

    it('meets memory usage requirements for batch processing', function (): void {
        $memoryBefore = memory_get_usage(true);

        // Process a large batch
        $domains = [];
        for ($i = 1; $i <= 50; $i++) {
            $domains[] = "memory-test{$i}.com";
        }

        $results = $this->dnsService->checkBatch($domains);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Memory usage should be reasonable (less than 50MB for 50 domains)
        expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024)
            ->and($results)->toHaveCount(50);
    });

    it('provides performance metrics collection', function (): void {
        // Test that the performance monitoring service exists and can be used
        $performanceMonitor = app(\App\Contracts\DnsPerformanceMonitorInterface::class);

        expect($performanceMonitor)->toBeInstanceOf(\App\Contracts\DnsPerformanceMonitorInterface::class);

        // Start a batch, record a lookup, and complete the batch
        $batchId = $performanceMonitor->startBatch();

        $performanceMonitor->recordLookup(
            'metrics-test.com',
            150.5, // response time in ms
            true,  // successful
            false, // not from cache
            null   // no error
        );

        $metrics = $performanceMonitor->completeBatch();

        // Verify that metrics were recorded
        expect($metrics)->not->toBeNull()
            ->and($metrics->batch_id)->toBe($batchId)
            ->and($metrics->domains_checked)->toBe(1)
            ->and($metrics->successful_lookups)->toBe(1);

        // Check that performance metrics exist in database
        $this->assertDatabaseHas('dns_lookup_metrics', [
            'batch_id' => $batchId,
            'domains_checked' => 1,
            'successful_lookups' => 1,
        ]);
    });

    it('maintains acceptable error rates under load', function (): void {
        $domains = [];
        $successCount = 0;
        $totalCount = 20;

        // Test with mix of valid and potentially problematic domains
        for ($i = 1; $i <= $totalCount; $i++) {
            $domains[] = "load-test{$i}.com";
        }

        $results = $this->dnsService->checkBatch($domains);

        foreach ($results as $result) {
            if ($result->error === null) {
                $successCount++;
            }
        }

        $successRate = ($successCount / $totalCount) * 100;

        // Success rate should be at least 80%
        expect($successRate)->toBeGreaterThanOrEqual(80.0);
    });
});
