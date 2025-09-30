<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsLookupServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * DNS service degradation handler.
 *
 * Provides graceful degradation strategies when DNS services are unavailable
 * or experiencing issues, ensuring the application continues to function.
 */
final readonly class DnsDegradationService
{
    public function __construct(
        private DnsHealthCheckService $healthService,
        private ?DnsLookupServiceInterface $dnsService = null
    ) {}

    /**
     * Check if DNS service is in degraded mode
     */
    public function isDegradedMode(): bool
    {
        $healthStatus = $this->healthService->getHealthStatus();

        // Consider degraded if:
        // 1. Error rate is very high (>50%)
        // 2. Circuit breaker is open
        // 3. Manual degradation mode is enabled
        return $healthStatus['error_rate'] > 50
            || in_array('Circuit breaker open', $healthStatus['issues'])
            || $this->isManualDegradationEnabled();
    }

    /**
     * Get degraded mode information
     *
     * @return array{degraded: bool, mode: string, reason: string|null, fallback_strategy: string|null, health_status?: array<string, mixed>, estimated_recovery?: Carbon|null}
     */
    public function getDegradationStatus(): array
    {
        if (! $this->isDegradedMode()) {
            return [
                'degraded' => false,
                'mode' => 'normal',
                'reason' => null,
                'fallback_strategy' => null,
            ];
        }

        $healthStatus = $this->healthService->getHealthStatus();
        $reason = $this->determineDegradationReason($healthStatus);
        $strategy = $this->getFallbackStrategy($reason);

        return [
            'degraded' => true,
            'mode' => 'degraded',
            'reason' => $reason,
            'fallback_strategy' => $strategy,
            'health_status' => $healthStatus,
            'estimated_recovery' => $this->getEstimatedRecoveryTime(),
        ];
    }

    /**
     * Handle domain checking in degraded mode
     *
     * @return array<string, mixed>
     */
    public function checkDomainInDegradedMode(string $domain): array
    {
        $status = $this->getDegradationStatus();

        if (! $status['degraded']) {
            throw new \RuntimeException('DNS service is not in degraded mode');
        }

        $strategy = $status['fallback_strategy'];

        Log::info('DNS degraded mode domain check', [
            'domain' => $domain,
            'strategy' => $strategy,
            'reason' => $status['reason'],
        ]);

        return match ($strategy) {
            'cache_only' => $this->checkDomainCacheOnly($domain),
            'pessimistic' => $this->checkDomainPessimistic($domain),
            'optimistic' => $this->checkDomainOptimistic($domain),
            'disabled' => $this->checkDomainDisabled($domain),
            default => $this->checkDomainPessimistic($domain),
        };
    }

    /**
     * Enable manual degradation mode
     */
    public function enableDegradationMode(string $reason = 'Manual override'): void
    {
        Cache::put('dns_manual_degradation', [
            'enabled' => true,
            'reason' => $reason,
            'enabled_at' => now(),
        ], now()->addHours(24));

        Log::warning('DNS degradation mode manually enabled', [
            'reason' => $reason,
            'enabled_at' => now(),
        ]);
    }

    /**
     * Disable manual degradation mode
     */
    public function disableDegradationMode(): void
    {
        Cache::forget('dns_manual_degradation');

        Log::info('DNS degradation mode manually disabled');
    }

    /**
     * Check if manual degradation is enabled
     */
    private function isManualDegradationEnabled(): bool
    {
        $manual = Cache::get('dns_manual_degradation');

        return $manual['enabled'] ?? false;
    }

    /**
     * Determine the reason for degradation
     *
     * @param  array<string, mixed>  $healthStatus
     */
    private function determineDegradationReason(array $healthStatus): string
    {
        if ($this->isManualDegradationEnabled()) {
            $manual = Cache::get('dns_manual_degradation');

            return $manual['reason'] ?? 'Manual override';
        }

        if (in_array('Circuit breaker open', $healthStatus['issues'])) {
            return 'Circuit breaker protection activated';
        }

        if ($healthStatus['error_rate'] > 50) {
            return 'High DNS error rate detected';
        }

        if (in_array('High response time', $healthStatus['issues'])) {
            return 'DNS response times too slow';
        }

        return 'DNS service health degraded';
    }

    /**
     * Get appropriate fallback strategy based on degradation reason
     */
    private function getFallbackStrategy(string $reason): string
    {
        $config = config('dns.degradation', []);

        return match (true) {
            str_contains($reason, 'Circuit breaker') => $config['circuit_breaker_strategy'] ?? 'cache_only',
            str_contains($reason, 'error rate') => $config['error_strategy'] ?? 'pessimistic',
            str_contains($reason, 'response time') => $config['timeout_strategy'] ?? 'cache_only',
            str_contains($reason, 'Manual') => $config['manual_strategy'] ?? 'optimistic',
            default => $config['default_strategy'] ?? 'pessimistic',
        };
    }

    /**
     * Check domain using only cached DNS results
     */
    /**
     * @return array<string, mixed>
     */
    private function checkDomainCacheOnly(string $domain): array
    {
        if (! $this->dnsService) {
            return $this->getDegradedResult($domain, 'no_dns_service');
        }

        try {
            $cachedResult = $this->dnsService->getCachedResult($domain);

            if ($cachedResult) {
                return [
                    'domain' => $domain,
                    'dns_checked' => true,
                    'dns_has_records' => $cachedResult->hasRecords,
                    'dns_source' => 'cache',
                    'degraded_mode' => true,
                    'fallback_strategy' => 'cache_only',
                ];
            }
        } catch (\Exception $e) {
            Log::debug('Cache lookup failed in degraded mode', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDegradedResult($domain, 'no_cache');
    }

    /**
     * Check domain with pessimistic assumption (assume domain is taken)
     */
    /**
     * @return array<string, mixed>
     */
    private function checkDomainPessimistic(string $domain): array
    {
        // Try cache first
        $cacheResult = $this->checkDomainCacheOnly($domain);
        if ($cacheResult['dns_checked']) {
            return $cacheResult;
        }

        // Assume domain is taken when DNS is unavailable (safer approach)
        return [
            'domain' => $domain,
            'dns_checked' => false,
            'dns_has_records' => true, // Pessimistic assumption
            'dns_source' => 'degraded_pessimistic',
            'degraded_mode' => true,
            'fallback_strategy' => 'pessimistic',
            'degraded_reason' => 'Assuming domain is taken due to DNS unavailability',
        ];
    }

    /**
     * Check domain with optimistic assumption (assume domain is available)
     */
    /**
     * @return array<string, mixed>
     */
    private function checkDomainOptimistic(string $domain): array
    {
        // Try cache first
        $cacheResult = $this->checkDomainCacheOnly($domain);
        if ($cacheResult['dns_checked']) {
            return $cacheResult;
        }

        // Assume domain is available when DNS is unavailable
        return [
            'domain' => $domain,
            'dns_checked' => false,
            'dns_has_records' => false, // Optimistic assumption
            'dns_source' => 'degraded_optimistic',
            'degraded_mode' => true,
            'fallback_strategy' => 'optimistic',
            'degraded_reason' => 'Assuming domain is available due to DNS unavailability',
        ];
    }

    /**
     * Disable DNS checking completely
     */
    /**
     * @return array<string, mixed>
     */
    private function checkDomainDisabled(string $domain): array
    {
        return [
            'domain' => $domain,
            'dns_checked' => false,
            'dns_has_records' => null,
            'dns_source' => 'disabled',
            'degraded_mode' => true,
            'fallback_strategy' => 'disabled',
            'degraded_reason' => 'DNS checking disabled due to service unavailability',
        ];
    }

    /**
     * Get degraded result with specified reason
     *
     * @return array<string, mixed>
     */
    private function getDegradedResult(string $domain, string $reason): array
    {
        return [
            'domain' => $domain,
            'dns_checked' => false,
            'dns_has_records' => null,
            'dns_source' => 'degraded',
            'degraded_mode' => true,
            'degraded_reason' => $reason,
        ];
    }

    /**
     * Estimate recovery time based on degradation reason
     */
    private function getEstimatedRecoveryTime(): ?Carbon
    {
        $healthStatus = $this->healthService->getHealthStatus();

        if (in_array('Circuit breaker open', $healthStatus['issues'])) {
            $timeoutMinutes = config('dns.circuit_breaker.timeout_minutes', 5);

            return now()->addMinutes($timeoutMinutes);
        }

        if ($this->isManualDegradationEnabled()) {
            return null; // Manual recovery required
        }

        // Default estimate for automatic recovery
        return now()->addMinutes(15);
    }

    /**
     * Get degradation metrics for monitoring
     *
     * @return array<string, mixed>
     */
    public function getDegradationMetrics(): array
    {
        $status = $this->getDegradationStatus();

        return [
            'degraded_mode_active' => $status['degraded'],
            'degradation_reason' => $status['reason'] ?? null,
            'fallback_strategy' => $status['fallback_strategy'] ?? null,
            'manual_override' => $this->isManualDegradationEnabled(),
            'estimated_recovery' => $status['estimated_recovery'] ?? null,
            'health_metrics' => $status['health_status'] ?? [],
        ];
    }
}
