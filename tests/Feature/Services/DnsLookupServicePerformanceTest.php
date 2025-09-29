<?php

declare(strict_types=1);

use App\Contracts\DnsPerformanceMonitorInterface;
use App\Contracts\DnsResolverInterface;
use App\DTOs\DnsLookupResult;
use App\Models\DnsLookupCache;
use App\Models\DnsLookupMetrics;
use App\Services\DnsLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->dnsResolver = Mockery::mock(DnsResolverInterface::class);
    $this->performanceMonitor = Mockery::mock(DnsPerformanceMonitorInterface::class);

    $this->dnsService = new DnsLookupService(
        resolver: $this->dnsResolver,
        performanceMonitor: $this->performanceMonitor
    );
});

describe('DNS Lookup Service Performance Integration', function (): void {
    it('records performance metrics for successful DNS lookup', function (): void {
        $domain = 'example.com';

        // Mock DNS response
        $mockResponse = (object) ['answer' => [['type' => 'A']]];
        $this->dnsResolver->shouldReceive('query')
            ->with($domain, 'A')
            ->once()
            ->andReturn($mockResponse);

        // Mock other record types returning empty
        $emptyResponse = (object) ['answer' => []];
        $this->dnsResolver->shouldReceive('query')
            ->with($domain, Mockery::type('string'))
            ->andReturn($emptyResponse);

        // Expect performance recording
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                domain: $domain,
                responseTimeMs: Mockery::type('float'),
                successful: true,
                cacheHit: false,
                error: null
            );

        $result = $this->dnsService->checkDomain($domain);

        expect($result->hasRecords)->toBeTrue()
            ->and($result->recordTypes)->toContain('A');
    });

    it('records performance metrics for cached DNS lookup', function (): void {
        $domain = 'cached.com';

        // Create cached result
        DnsLookupCache::create([
            'domain' => 'cached',
            'tld' => 'com',
            'has_records' => true,
            'record_types' => ['A'],
            'checked_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        // Expect performance recording for cache hit
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                domain: $domain,
                responseTimeMs: Mockery::type('float'),
                successful: true,
                cacheHit: true,
                error: null
            );

        $result = $this->dnsService->checkDomain($domain);

        expect($result->hasRecords)->toBeTrue()
            ->and($result->recordTypes)->toContain('A');
    });

    it('records performance metrics for failed DNS lookup', function (): void {
        $domain = 'error.com';
        $errorMessage = 'DNS timeout';

        // Mock DNS resolver to throw exception
        $this->dnsResolver->shouldReceive('query')
            ->andThrow(new Exception($errorMessage));

        // Expect performance recording for error
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                domain: $domain,
                responseTimeMs: Mockery::type('float'),
                successful: false,
                cacheHit: false,
                error: $errorMessage
            );

        $result = $this->dnsService->checkDomain($domain);

        expect($result->isError())->toBeTrue()
            ->and($result->error)->toBe($errorMessage);
    });

    it('records performance metrics for invalid domain', function (): void {
        $invalidDomain = '';

        // Expect performance recording for validation error
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                domain: $invalidDomain,
                responseTimeMs: Mockery::type('float'),
                successful: false,
                cacheHit: false,
                error: 'Invalid domain format'
            );

        $result = $this->dnsService->checkDomain($invalidDomain);

        expect($result->isError())->toBeTrue()
            ->and($result->error)->toBe('Invalid domain format');
    });

    it('handles missing performance monitor gracefully', function (): void {
        $domain = 'test.com';

        // Create service without performance monitor
        $serviceWithoutMonitor = new DnsLookupService(resolver: $this->dnsResolver);

        // Mock DNS response
        $mockResponse = (object) ['answer' => []];
        $this->dnsResolver->shouldReceive('query')
            ->andReturn($mockResponse);

        // Should not throw exception when performance monitor is null
        $result = $serviceWithoutMonitor->checkDomain($domain);

        expect($result)->toBeInstanceOf(DnsLookupResult::class);
    });

    it('measures response time accurately', function (): void {
        $domain = 'timing.com';

        // Mock DNS response with delay
        $mockResponse = (object) ['answer' => []];
        $this->dnsResolver->shouldReceive('query')
            ->andReturnUsing(function () use ($mockResponse) {
                usleep(10000); // 10ms delay
                return $mockResponse;
            });

        // Expect response time to be measured
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                domain: $domain,
                responseTimeMs: Mockery::on(function ($time) {
                    return $time >= 10.0; // At least 10ms
                }),
                successful: true,
                cacheHit: false,
                error: null
            );

        $this->dnsService->checkDomain($domain);
    });

    it('records metrics for each domain in batch lookup', function (): void {
        $domains = ['test1.com', 'test2.com'];

        // Mock DNS responses
        $mockResponse = (object) ['answer' => []];
        $this->dnsResolver->shouldReceive('query')
            ->andReturn($mockResponse);

        // Expect performance recording for each domain
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->times(2)
            ->with(
                domain: Mockery::type('string'),
                responseTimeMs: Mockery::type('float'),
                successful: true,
                cacheHit: false,
                error: null
            );

        $results = $this->dnsService->checkBatch($domains);

        expect($results)->toHaveCount(2)
            ->and($results['test1.com'])->toBeInstanceOf(DnsLookupResult::class)
            ->and($results['test2.com'])->toBeInstanceOf(DnsLookupResult::class);
    });
});