<?php

declare(strict_types=1);

use App\Contracts\DnsLookupServiceInterface;
use App\DTOs\DnsLookupResult;
use App\Services\CircuitBreakerService;
use App\Services\DnsCircuitBreakerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('DNS Circuit Breaker Service', function (): void {
    beforeEach(function (): void {
        Cache::flush();
        $this->mockDnsService = \Mockery::mock(DnsLookupServiceInterface::class);
        $this->circuitBreaker = new CircuitBreakerService('test_dns', 2, 1, 1);
        $this->dnsCircuitBreakerService = new DnsCircuitBreakerService(
            $this->mockDnsService,
            $this->circuitBreaker
        );
    });

    it('forwards successful DNS lookups when circuit is closed', function (): void {
        $domain = 'example.com';
        $expectedResult = DnsLookupResult::withRecords(['A']);

        $this->mockDnsService->shouldReceive('checkDomain')
            ->once()
            ->with($domain)
            ->andReturn($expectedResult);

        $result = $this->dnsCircuitBreakerService->checkDomain($domain);

        expect($result)->toBe($expectedResult)
            ->and($this->dnsCircuitBreakerService->getCircuitBreakerStats()['state'])->toBe('closed');
    });

    it('tracks failures and opens circuit after threshold', function (): void {
        $domain = 'failing.com';

        $this->mockDnsService->shouldReceive('checkDomain')
            ->times(2)
            ->with($domain)
            ->andThrow(new Exception('DNS lookup failed'));

        // First failure
        expect(fn () => $this->dnsCircuitBreakerService->checkDomain($domain))
            ->toThrow(Exception::class);

        // Second failure should open the circuit
        expect(fn () => $this->dnsCircuitBreakerService->checkDomain($domain))
            ->toThrow(Exception::class);

        expect($this->dnsCircuitBreakerService->isCircuitBreakerOpen())->toBeTrue();
    });

    it('returns error result when circuit breaker is open', function (): void {
        $domain = 'blocked.com';

        // Force failures to open circuit
        $this->mockDnsService->shouldReceive('checkDomain')
            ->times(2)
            ->andThrow(new Exception('DNS lookup failed'));

        for ($i = 0; $i < 2; $i++) {
            try {
                $this->dnsCircuitBreakerService->checkDomain($domain);
            } catch (Exception $e) {
                // Expected
            }
        }

        // Now the circuit should be open
        $result = $this->dnsCircuitBreakerService->checkDomain($domain);

        expect($result->isError())->toBeTrue()
            ->and($result->error)->toBe('DNS service temporarily unavailable due to repeated failures');
    });

    it('bypasses circuit breaker for cached results', function (): void {
        $domain = 'cached.com';
        $cachedResult = DnsLookupResult::withRecords(['A']);

        $this->mockDnsService->shouldReceive('getCachedResult')
            ->once()
            ->with($domain)
            ->andReturn($cachedResult);

        $result = $this->dnsCircuitBreakerService->getCachedResult($domain);

        expect($result)->toBe($cachedResult);
    });

    it('processes batch lookups with circuit breaker protection', function (): void {
        $domains = ['test1.com', 'test2.com'];
        $result1 = DnsLookupResult::withoutRecords();
        $result2 = DnsLookupResult::withRecords(['A']);

        $this->mockDnsService->shouldReceive('checkDomain')
            ->with('test1.com')
            ->once()
            ->andReturn($result1);

        $this->mockDnsService->shouldReceive('checkDomain')
            ->with('test2.com')
            ->once()
            ->andReturn($result2);

        $results = $this->dnsCircuitBreakerService->checkBatch($domains);

        expect($results)->toHaveCount(2)
            ->and($results['test1.com'])->toBe($result1)
            ->and($results['test2.com'])->toBe($result2);
    });

    it('provides circuit breaker health status', function (): void {
        expect($this->dnsCircuitBreakerService->isCircuitBreakerHealthy())->toBeTrue()
            ->and($this->dnsCircuitBreakerService->isCircuitBreakerOpen())->toBeFalse();

        // Force circuit to open
        $this->mockDnsService->shouldReceive('checkDomain')
            ->times(2)
            ->andThrow(new Exception('DNS failure'));

        for ($i = 0; $i < 2; $i++) {
            try {
                $this->dnsCircuitBreakerService->checkDomain('test.com');
            } catch (Exception $e) {
                // Expected
            }
        }

        expect($this->dnsCircuitBreakerService->isCircuitBreakerHealthy())->toBeFalse()
            ->and($this->dnsCircuitBreakerService->isCircuitBreakerOpen())->toBeTrue();
    });

    it('can reset circuit breaker manually', function (): void {
        // Force circuit to open
        $this->mockDnsService->shouldReceive('checkDomain')
            ->times(2)
            ->andThrow(new Exception('DNS failure'));

        for ($i = 0; $i < 2; $i++) {
            try {
                $this->dnsCircuitBreakerService->checkDomain('test.com');
            } catch (Exception $e) {
                // Expected
            }
        }

        expect($this->dnsCircuitBreakerService->isCircuitBreakerOpen())->toBeTrue();

        // Reset circuit breaker
        $this->dnsCircuitBreakerService->resetCircuitBreaker();

        expect($this->dnsCircuitBreakerService->isCircuitBreakerOpen())->toBeFalse()
            ->and($this->dnsCircuitBreakerService->isCircuitBreakerHealthy())->toBeTrue();
    });

    it('handles mixed failure and success patterns in batch operations', function (): void {
        $domains = ['success.com', 'failure.com'];

        $this->mockDnsService->shouldReceive('checkDomain')
            ->with('success.com')
            ->andReturn(DnsLookupResult::withRecords(['A']));

        $this->mockDnsService->shouldReceive('checkDomain')
            ->with('failure.com')
            ->andThrow(new Exception('DNS lookup failed'));

        $results = $this->dnsCircuitBreakerService->checkBatch($domains);

        expect($results)->toHaveCount(2)
            ->and($results['success.com']->hasRecords)->toBeTrue()
            ->and($results['failure.com']->isError())->toBeTrue()
            ->and($results['failure.com']->error)->toBe('DNS lookup failed');
    });

    it('provides detailed circuit breaker statistics', function (): void {
        $stats = $this->dnsCircuitBreakerService->getCircuitBreakerStats();

        expect($stats)->toHaveKeys([
            'service_name',
            'state',
            'failure_count',
            'success_count',
            'failure_threshold',
            'timeout_minutes',
            'success_threshold'
        ])
            ->and($stats['service_name'])->toBe('test_dns')
            ->and($stats['state'])->toBe('closed');
    });

    it('maintains circuit breaker state across multiple operations', function (): void {
        $domain = 'persistent.com';

        // Successful operation
        $this->mockDnsService->shouldReceive('checkDomain')
            ->once()
            ->with($domain)
            ->andReturn(DnsLookupResult::withoutRecords());

        $this->dnsCircuitBreakerService->checkDomain($domain);

        expect($this->circuitBreaker->getFailureCount())->toBe(0);

        // Failed operation
        $this->mockDnsService->shouldReceive('checkDomain')
            ->once()
            ->with($domain)
            ->andThrow(new Exception('DNS failure'));

        try {
            $this->dnsCircuitBreakerService->checkDomain($domain);
        } catch (Exception $e) {
            // Expected
        }

        expect($this->circuitBreaker->getFailureCount())->toBe(1);
    });
});