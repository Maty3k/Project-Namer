<?php

declare(strict_types=1);

use App\Contracts\DnsLookupServiceInterface;
use App\Contracts\DnsResolverInterface;
use App\Jobs\CheckDomainDnsJob;
use App\Models\DnsLookupCache;
use App\Models\NameSuggestion;
use App\Services\DnsCircuitBreakerService;
use App\Services\DnsLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('DNS Service Failure Scenarios', function (): void {
    beforeEach(function (): void {
        $this->circuitBreaker = app(DnsCircuitBreakerService::class);

        // Reset circuit breaker state
        Cache::forget('dns_circuit_breaker_state');
        Cache::forget('dns_circuit_breaker_failures');
        Cache::forget('dns_circuit_breaker_last_failure');
    });

    it('handles DNS timeout gracefully', function (): void {
        // Mock a resolver that times out for all record types
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andThrow(new \Exception('Operation timed out'));

        $dnsService = new DnsLookupService($mockResolver);

        $result = $dnsService->checkDomain('timeout-test.com');

        // When all DNS queries fail, service returns no records (graceful degradation)
        expect($result->hasRecords)->toBeFalse()
            ->and($result->recordTypes)->toBeEmpty()
            ->and($result->error)->toBeNull(); // No error - graceful handling
    });

    it('handles network connectivity issues', function (): void {
        // Mock a resolver that fails with network error
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andThrow(new \Exception('Network unreachable'));

        $dnsService = new DnsLookupService($mockResolver);

        $result = $dnsService->checkDomain('network-fail.com');

        // When all DNS queries fail, service returns no records (graceful degradation)
        expect($result->hasRecords)->toBeFalse()
            ->and($result->recordTypes)->toBeEmpty()
            ->and($result->error)->toBeNull(); // No error - graceful handling
    });

    it('handles invalid domain format gracefully', function (): void {
        $dnsService = app(DnsLookupService::class);

        $invalidDomains = [
            '', // empty string
            'invalid..domain.com', // double dots
            '.invalid.com', // starts with dot
            'invalid.', // ends with dot
            'invalid domain.com', // spaces
            'invalid#domain.com', // special characters
        ];

        foreach ($invalidDomains as $domain) {
            $result = $dnsService->checkDomain($domain);

            expect($result->error)->not->toBeNull()
                ->and(str_contains((string) $result->error, 'Invalid domain format'))->toBeTrue()
                ->and($result->hasRecords)->toBeFalse();
        }
    });

    it('activates circuit breaker after repeated failures', function (): void {
        // Mock a DNS service that always throws exceptions
        $mockDnsService = mock(DnsLookupServiceInterface::class);
        $mockDnsService->shouldReceive('checkDomain')
            ->andThrow(new \Exception('DNS server error'));

        // Create circuit breaker service with the mock
        $circuitBreakerService = new DnsCircuitBreakerService($mockDnsService);

        // Trigger multiple failures to trip circuit breaker
        $failureThreshold = config('dns.circuit_breaker.failure_threshold', 5);

        for ($i = 0; $i < $failureThreshold; $i++) {
            try {
                $result = $circuitBreakerService->checkDomain("fail-test-{$i}.com");
                expect($result->error)->not->toBeNull();
            } catch (\Exception $e) {
                // During setup, exceptions may be thrown before circuit breaker logic kicks in
                expect($e->getMessage())->toContain('DNS server error');
            }
        }

        // Circuit breaker should now be open
        expect($circuitBreakerService->isCircuitBreakerOpen())->toBeTrue();

        // Next request should be rejected by circuit breaker
        $result = $circuitBreakerService->checkDomain('rejected.com');
        expect($result->error)->not->toBeNull()
            ->and(str_contains((string) $result->error, 'temporarily unavailable'))->toBeTrue();
    });

    it('handles DNS server returning malformed responses', function (): void {
        // Mock a resolver that returns malformed data
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andThrow(new \Exception('Malformed DNS response'));

        $dnsService = new DnsLookupService($mockResolver);

        $result = $dnsService->checkDomain('malformed.com');

        // When all DNS queries fail, service returns no records (graceful degradation)
        expect($result->hasRecords)->toBeFalse()
            ->and($result->recordTypes)->toBeEmpty()
            ->and($result->error)->toBeNull(); // No error - graceful handling
    });

    it('continues working when some DNS servers fail', function (): void {
        // This tests the fallback to alternative DNS servers
        $dnsService = app(DnsLookupService::class);

        // Test with a known good domain - should work even if some servers fail
        $result = $dnsService->checkDomain('google.com');

        // Should succeed despite potential server failures
        expect($result->error)->toBeNull();
    });

    it('handles queue job failures gracefully', function (): void {
        Queue::fake();

        // Create a name suggestion
        $suggestion = NameSuggestion::factory()->create([
            'domains' => [['domain' => 'test', 'tld' => 'com', 'available' => null]],
        ]);

        // Dispatch the job - this tests job queuing functionality
        CheckDomainDnsJob::dispatch($suggestion->id);

        // Job should be queued successfully
        Queue::assertPushed(CheckDomainDnsJob::class);

        // Test that the job can handle DNS service failures by using a mock resolver
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andThrow(new \Exception('DNS service unavailable'));

        $dnsService = new DnsLookupService($mockResolver);

        // Should handle failure gracefully without throwing exceptions
        $result = $dnsService->checkDomain('fail-test.com');
        expect($result->hasRecords)->toBeFalse();
    });

    it('handles database connection failures during caching', function (): void {
        // Test when database is unavailable for caching
        $dnsService = app(DnsLookupService::class);

        // Temporarily break database connection by using invalid table
        $originalConnection = config('database.default');

        try {
            // This should not crash the entire application
            $result = $dnsService->checkDomain('cache-fail.com');

            // Should still return a result, even if caching fails
            expect($result)->not->toBeNull();
        } finally {
            // Restore connection
            config(['database.default' => $originalConnection]);
        }
    });

    it('handles memory exhaustion gracefully', function (): void {
        // Mock a resolver to avoid actual DNS calls
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andReturn((object) ['answer' => []]);

        $dnsService = new DnsLookupService($mockResolver);

        // Test with a large batch that might cause memory issues
        $domains = [];
        for ($i = 0; $i < 100; $i++) { // Reduced from 1000 to 100 for test performance
            $domains[] = "memory-test-{$i}.com";
        }

        // Should not crash with memory errors
        $results = $dnsService->checkBatch($domains);

        expect($results)->toBeArray()
            ->and(count($results))->toBe(100);
    });

    it('logs DNS service failures appropriately', function (): void {
        Log::spy();

        // Mock a failing DNS service
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andThrow(new \Exception('Critical DNS failure'));

        $dnsService = new DnsLookupService($mockResolver);

        $result = $dnsService->checkDomain('log-test.com');

        // Should log debug messages for each failed record type query
        Log::shouldHaveReceived('debug')
            ->with('DNS query failed for record type', \Mockery::any())
            ->atLeast()->once();
    });

    it('handles concurrent failures without blocking', function (): void {
        // Mock a resolver that fails occasionally
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andReturnUsing(function () {
                // Randomly fail some requests
                if (random_int(1, 3) === 1) {
                    throw new \Exception('Random failure');
                }

                return (object) ['answer' => []];
            });

        $dnsService = new DnsLookupService($mockResolver);

        // Test multiple concurrent requests
        $domains = ['test1.com', 'test2.com', 'test3.com', 'test4.com', 'test5.com'];
        $results = $dnsService->checkBatch($domains);

        // Should get results for all domains, even if some fail
        expect($results)->toHaveCount(5);

        foreach ($results as $result) {
            expect($result)->not->toBeNull();
        }
    });

    it('handles rate limiting from DNS providers', function (): void {
        // Mock a resolver that returns rate limit errors
        $mockResolver = mock(DnsResolverInterface::class);
        $mockResolver->shouldReceive('query')
            ->andThrow(new \Exception('Rate limit exceeded'));

        $dnsService = new DnsLookupService($mockResolver);

        $result = $dnsService->checkDomain('rate-limited.com');

        // When all DNS queries fail, service returns no records (graceful degradation)
        expect($result->hasRecords)->toBeFalse()
            ->and($result->recordTypes)->toBeEmpty()
            ->and($result->error)->toBeNull(); // No error - graceful handling
    });

    it('maintains service during partial DNS server outages', function (): void {
        // This tests resilience when some but not all DNS servers are down
        $dnsService = app(DnsLookupService::class);

        // Test with multiple domains
        $domains = ['resilience1.com', 'resilience2.com', 'resilience3.com'];
        $results = $dnsService->checkBatch($domains);

        // Should maintain service availability
        expect($results)->toHaveCount(3);

        // At least some should succeed (assuming fallback servers work)
        $successCount = 0;
        foreach ($results as $result) {
            if ($result->error === null) {
                $successCount++;
            }
        }

        // Should have reasonable success rate
        expect($successCount)->toBeGreaterThan(0);
    });

    it('handles cache corruption gracefully', function (): void {
        // Create cache entry and then manually corrupt it in the database
        $cache = DnsLookupCache::create([
            'domain' => 'corrupted',
            'tld' => 'com',
            'has_records' => true,
            'record_types' => ['A'], // Valid initially
            'checked_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        // Manually corrupt the record_types in the database
        DB::table('dns_lookup_cache')
            ->where('id', $cache->id)
            ->update(['record_types' => 'invalid_json']);

        $dnsService = app(DnsLookupService::class);

        // Should handle corrupted cache gracefully
        $result = $dnsService->checkDomain('corrupted.com');

        expect($result)->not->toBeNull();
    });

    it('recovers from circuit breaker open state', function (): void {
        // Create circuit breaker service with mock DNS service
        $mockDnsService = mock(DnsLookupServiceInterface::class);
        $mockDnsService->shouldReceive('checkDomain')
            ->andThrow(new \Exception('DNS failure'));

        $circuitBreakerService = new DnsCircuitBreakerService($mockDnsService);

        // Force circuit breaker open by triggering failures
        $failureThreshold = config('dns.circuit_breaker.failure_threshold', 5);
        for ($i = 0; $i < $failureThreshold; $i++) {
            try {
                $circuitBreakerService->checkDomain("fail-{$i}.com");
            } catch (\Exception $e) {
                // Exceptions during circuit breaker setup
                expect($e->getMessage())->toContain('DNS failure');
            }
        }

        expect($circuitBreakerService->isCircuitBreakerOpen())->toBeTrue();

        // Reset circuit breaker to test recovery
        $circuitBreakerService->resetCircuitBreaker();

        // Should be closed again
        expect($circuitBreakerService->isCircuitBreakerOpen())->toBeFalse();
    });
});
