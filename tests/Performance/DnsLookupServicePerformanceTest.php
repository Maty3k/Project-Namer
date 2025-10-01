<?php

declare(strict_types=1);

use App\Contracts\DnsPerformanceMonitorInterface;
use App\Contracts\DnsResolverInterface;
use App\DTOs\DnsLookupResult;
use App\Models\DnsLookupCache;
use App\Services\DnsLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(Tests\TestCase::class, RefreshDatabase::class);

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
                $domain,
                Mockery::type('float'),
                true,
                false,
                null
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
                $domain,
                Mockery::type('float'),
                true,
                true,
                null
            );

        $result = $this->dnsService->checkDomain($domain);

        expect($result->hasRecords)->toBeTrue()
            ->and($result->recordTypes)->toContain('A');
    });

    it('records performance metrics for DNS query failures (graceful handling)', function (): void {
        $domain = 'error.com';
        $errorMessage = 'DNS timeout';

        // Disable fallback to ensure error is not overridden
        config(['dns.fallback.enabled' => false]);

        // Create new service instance with fallback disabled
        $dnsService = new DnsLookupService(
            resolver: $this->dnsResolver,
            performanceMonitor: $this->performanceMonitor
        );

        // Mock DNS resolver to throw exception
        $this->dnsResolver->shouldReceive('query')
            ->andThrow(new Exception($errorMessage));

        // Expect performance recording for successful lookup with no records
        // (DNS query failures are handled gracefully and not considered errors)
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                $domain,
                Mockery::type('float'),
                true,  // successful = true (graceful handling)
                false,
                null   // error = null (no error, just no records)
            );

        $result = $dnsService->checkDomain($domain);

        expect($result->isSuccessful())->toBeTrue()
            ->and($result->hasRecords)->toBeFalse()
            ->and($result->error)->toBeNull();
    });

    it('records performance metrics for invalid domain', function (): void {
        $invalidDomain = '';

        // Expect performance recording for validation error
        $this->performanceMonitor->shouldReceive('recordLookup')
            ->once()
            ->with(
                $invalidDomain,
                Mockery::type('float'),
                false,
                false,
                'Invalid domain format'
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
                $domain,
                Mockery::on(function ($time) {
                    return $time >= 10.0; // At least 10ms
                }),
                true,
                false,
                null
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
                Mockery::type('string'),
                Mockery::type('float'),
                true,
                false,
                null
            );

        $results = $this->dnsService->checkBatch($domains);

        expect($results)->toHaveCount(2)
            ->and($results['test1.com'])->toBeInstanceOf(DnsLookupResult::class)
            ->and($results['test2.com'])->toBeInstanceOf(DnsLookupResult::class);
    });
});
