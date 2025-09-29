<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsLookupServiceInterface;
use App\DTOs\DnsLookupResult;
use Exception;
use Illuminate\Support\Facades\Log;

final class DnsCircuitBreakerService implements DnsLookupServiceInterface
{
    private readonly CircuitBreakerService $circuitBreaker;

    public function __construct(
        private readonly DnsLookupServiceInterface $dnsService,
        ?CircuitBreakerService $circuitBreaker = null
    ) {
        $this->circuitBreaker = $circuitBreaker ?? new CircuitBreakerService(
            serviceName: 'dns_lookup',
            failureThreshold: config('dns.circuit_breaker.failure_threshold', 5),
            timeoutMinutes: config('dns.circuit_breaker.timeout_minutes', 5),
            successThreshold: config('dns.circuit_breaker.success_threshold', 3)
        );
    }

    public function checkDomain(string $fullDomain): DnsLookupResult
    {
        try {
            return $this->circuitBreaker->call(function () use ($fullDomain) {
                return $this->dnsService->checkDomain($fullDomain);
            });
        } catch (Exception $e) {
            // Check if this is a circuit breaker exception
            if (str_contains($e->getMessage(), 'Circuit breaker is OPEN')) {
                Log::warning('DNS lookup blocked by circuit breaker', [
                    'domain' => $fullDomain,
                    'circuit_breaker_state' => $this->circuitBreaker->getState(),
                    'failure_count' => $this->circuitBreaker->getFailureCount(),
                ]);

                return DnsLookupResult::withError(
                    'DNS service temporarily unavailable due to repeated failures'
                );
            }

            // Re-throw other exceptions to be handled by the circuit breaker
            throw $e;
        }
    }

    public function checkBatch(array $domains): array
    {
        $results = [];

        foreach ($domains as $domain) {
            try {
                $results[$domain] = $this->checkDomain($domain);
            } catch (Exception $e) {
                // Convert exceptions to error results for batch operations
                $results[$domain] = DnsLookupResult::withError($e->getMessage());
            }
        }

        return $results;
    }

    public function getCachedResult(string $fullDomain): ?DnsLookupResult
    {
        // Cache lookups bypass circuit breaker as they don't involve network calls
        return $this->dnsService->getCachedResult($fullDomain);
    }

    public function getCircuitBreakerStats(): array
    {
        return $this->circuitBreaker->getStats();
    }

    public function resetCircuitBreaker(): void
    {
        $this->circuitBreaker->reset();
    }

    public function isCircuitBreakerOpen(): bool
    {
        return $this->circuitBreaker->getState() === 'open';
    }

    public function isCircuitBreakerHealthy(): bool
    {
        $state = $this->circuitBreaker->getState();
        return $state === 'closed' || $state === 'half_open';
    }
}