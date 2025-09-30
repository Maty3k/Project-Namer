<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DnsLookupCache;
use App\Models\DnsLookupMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DNS Recovery Service
 *
 * Handles recovery procedures for restoring DNS service functionality
 * after failures, outages, or performance degradation.
 */
final class DnsRecoveryService
{
    /**
     * @var array<string, mixed>
     */
    private array $recoveryResults = [];

    public function __construct(
        private readonly DnsHealthCheckService $healthCheck,
        private readonly DnsCircuitBreakerService $circuitBreaker,
        private readonly DnsPerformanceMonitorService $performanceMonitor
    ) {}

    /**
     * Execute full DNS service recovery procedure.
     *
     * @return array{started_at: Carbon, steps: array<mixed>, success: bool, health_before: array<string, mixed>, completed_at?: Carbon, health_after?: array<string, mixed>, skipped?: bool, reason?: string, next_available?: Carbon}
     */
    public function executeRecovery(): array
    {
        if (! $this->canRunRecovery()) {
            $lastRecovery = $this->getLastRecoveryTime();
            $nextAvailable = $lastRecovery ? $lastRecovery->addMinutes(5) : Carbon::now();
            Log::warning('Recovery skipped - minimum interval not met', [
                'last_recovery' => $lastRecovery?->toISOString(),
                'next_available' => $nextAvailable->toISOString(),
            ]);

            return [
                'started_at' => Carbon::now(),
                'steps' => [],
                'success' => false,
                'health_before' => [],
                'skipped' => true,
                'reason' => 'Recovery attempted too soon after last run',
                'next_available' => $nextAvailable,
            ];
        }

        Log::info('Starting DNS service recovery procedure');

        $this->recoveryResults = [
            'started_at' => Carbon::now(),
            'steps' => [],
            'success' => true,
            'health_before' => $this->healthCheck->getHealthStatus(),
        ];

        // Execute recovery steps in sequence
        $this->clearStaleCache();
        $this->resetCircuitBreakers();
        $this->verifyDnsServers();
        $this->warmupCache();
        $this->clearMetrics();
        $this->testDnsResolution();

        // Check final health status
        $this->recoveryResults['health_after'] = $this->healthCheck->getHealthStatus();
        $this->recoveryResults['completed_at'] = Carbon::now();
        $this->recoveryResults['duration_seconds'] =
            $this->recoveryResults['started_at']->diffInSeconds($this->recoveryResults['completed_at']);

        // Log recovery completion
        $this->logRecoveryResults();

        return $this->recoveryResults;
    }

    /**
     * Clear stale DNS cache entries.
     */
    private function clearStaleCache(): void
    {
        $step = 'Clear Stale Cache';
        Log::info("Recovery Step: $step");

        try {
            // Delete cache entries older than configured TTL
            $maxAge = config('dns.cache.max_age_hours', 24);
            $deleted = DnsLookupCache::where('created_at', '<', Carbon::now()->subHours($maxAge))
                ->delete();

            // Clear Laravel cache entries
            Cache::tags(['dns'])->flush();

            $this->recordStep($step, true, "Deleted $deleted stale cache entries");
        } catch (\Exception $e) {
            $this->recordStep($step, false, $e->getMessage());
        }
    }

    /**
     * Reset all circuit breakers.
     */
    private function resetCircuitBreakers(): void
    {
        $step = 'Reset Circuit Breakers';
        Log::info("Recovery Step: $step");

        try {
            // Reset the main circuit breaker
            $this->circuitBreaker->reset();

            // Clear circuit breaker cache
            Cache::forget('dns:circuit_breaker:state');
            Cache::forget('dns:circuit_breaker:failure_count');
            Cache::forget('dns:circuit_breaker:last_failure_time');

            $this->recordStep($step, true, 'Circuit breakers reset successfully');
        } catch (\Exception $e) {
            $this->recordStep($step, false, $e->getMessage());
        }
    }

    /**
     * Verify DNS servers are reachable.
     */
    private function verifyDnsServers(): void
    {
        $step = 'Verify DNS Servers';
        Log::info("Recovery Step: $step");

        try {
            $servers = config('dns.servers', ['8.8.8.8', '8.8.4.4']);
            $reachable = [];
            $unreachable = [];

            foreach ($servers as $server) {
                // Test connectivity with a simple DNS query
                $socket = @fsockopen('udp://'.$server, 53, $errno, $errstr, 1);

                if ($socket) {
                    fclose($socket);
                    $reachable[] = $server;
                } else {
                    $unreachable[] = $server;
                    Log::warning("DNS server unreachable: $server");
                }
            }

            $message = sprintf(
                'Reachable: %d/%d servers. %s',
                count($reachable),
                count($servers),
                ! empty($unreachable) ? 'Unreachable: '.implode(', ', $unreachable) : ''
            );

            $this->recordStep($step, empty($unreachable), $message);
        } catch (\Exception $e) {
            $this->recordStep($step, false, $e->getMessage());
        }
    }

    /**
     * Warmup cache with common domains.
     */
    private function warmupCache(): void
    {
        $step = 'Cache Warmup';
        Log::info("Recovery Step: $step");

        try {
            // Get frequently checked domains from metrics
            $popularDomains = DnsLookupMetrics::select('batch_id', DB::raw('AVG(average_lookup_time) as avg_time'))
                ->groupBy('batch_id')
                ->orderBy('avg_time', 'asc')
                ->limit(10)
                ->pluck('batch_id')
                ->toArray();

            // Add some common test domains
            $testDomains = ['google.com', 'cloudflare.com', 'github.com'];
            $domainsToWarm = array_unique(array_merge($popularDomains, $testDomains));

            $warmed = 0;
            foreach ($domainsToWarm as $domain) {
                try {
                    // Perform a DNS lookup to warm the cache
                    $result = dns_get_record($domain, DNS_A);
                    if ($result) {
                        $warmed++;
                    }
                } catch (\Exception $e) {
                    // Individual domain failures don't fail the whole step
                    Log::debug("Failed to warm cache for domain: $domain", ['error' => $e->getMessage()]);
                }
            }

            $this->recordStep($step, true, "Warmed cache with $warmed domains");
        } catch (\Exception $e) {
            $this->recordStep($step, false, $e->getMessage());
        }
    }

    /**
     * Clear old performance metrics.
     */
    private function clearMetrics(): void
    {
        $step = 'Clear Old Metrics';
        Log::info("Recovery Step: $step");

        try {
            // Keep only recent metrics (last 7 days)
            $deleted = DnsLookupMetrics::where('created_at', '<', Carbon::now()->subDays(7))
                ->delete();

            $this->recordStep($step, true, "Deleted $deleted old metric records");
        } catch (\Exception $e) {
            $this->recordStep($step, false, $e->getMessage());
        }
    }

    /**
     * Test DNS resolution with sample domains.
     */
    private function testDnsResolution(): void
    {
        $step = 'Test DNS Resolution';
        Log::info("Recovery Step: $step");

        try {
            $testDomains = ['example.com', 'google.com', 'cloudflare.com'];
            $successful = 0;
            $failed = [];

            foreach ($testDomains as $domain) {
                try {
                    $result = dns_get_record($domain, DNS_A);
                    if ($result && count($result) > 0) {
                        $successful++;
                    } else {
                        $failed[] = $domain;
                    }
                } catch (\Exception $e) {
                    $failed[] = $domain;
                    Log::debug("DNS test failed for domain: $domain", ['error' => $e->getMessage()]);
                }
            }

            $message = sprintf(
                'Resolution test: %d/%d successful%s',
                $successful,
                count($testDomains),
                ! empty($failed) ? '. Failed: '.implode(', ', $failed) : ''
            );

            $this->recordStep($step, empty($failed), $message);
        } catch (\Exception $e) {
            $this->recordStep($step, false, $e->getMessage());
        }
    }

    /**
     * Record a recovery step result.
     */
    private function recordStep(string $step, bool $success, string $message): void
    {
        $this->recoveryResults['steps'][] = [
            'step' => $step,
            'success' => $success,
            'message' => $message,
            'timestamp' => Carbon::now()->toISOString(),
        ];

        if (! $success) {
            $this->recoveryResults['success'] = false;
        }
    }

    /**
     * Log recovery results.
     */
    private function logRecoveryResults(): void
    {
        $level = $this->recoveryResults['success'] ? 'info' : 'warning';

        Log::log($level, 'DNS recovery procedure completed', [
            'success' => $this->recoveryResults['success'],
            'duration_seconds' => $this->recoveryResults['duration_seconds'],
            'steps_executed' => count($this->recoveryResults['steps']),
            'health_improved' => $this->isHealthImproved(),
        ]);
    }

    /**
     * Check if health improved after recovery.
     */
    private function isHealthImproved(): bool
    {
        $before = $this->recoveryResults['health_before']['overall_status'] ?? 'unknown';
        $after = $this->recoveryResults['health_after']['overall_status'] ?? 'unknown';

        $statusRank = [
            'healthy' => 3,
            'warning' => 2,
            'critical' => 1,
            'unknown' => 0,
        ];

        return ($statusRank[$after] ?? 0) > ($statusRank[$before] ?? 0);
    }

    /**
     * Perform emergency DNS service restart.
     *
     * @return array{restart_time: string, actions_performed: array<string>, cache_cleared: bool, circuit_breaker_reset: bool, monitoring_restarted: bool, recovery_successful: bool}
     */
    public function emergencyRestart(): array
    {
        Log::warning('Executing emergency DNS service restart');

        // Force clear all caches
        Cache::tags(['dns'])->flush();
        Cache::flush();

        // Reset circuit breakers
        $this->circuitBreaker->forceClose();

        // Clear database caches
        DnsLookupCache::truncate();

        // Restart performance monitoring
        $this->performanceMonitor->startBatch('emergency-restart-'.time());

        return [
            'restart_time' => Carbon::now()->toISOString(),
            'actions_performed' => [
                'Cache cleared',
                'Circuit breaker reset',
                'Database cache cleared',
                'Performance monitoring restarted',
            ],
            'cache_cleared' => true,
            'circuit_breaker_reset' => true,
            'monitoring_restarted' => true,
            'recovery_successful' => true,
        ];
    }

    /**
     * Get recovery status and recommendations.
     *
     * @return array{health_status: array<string, mixed>, recommendations: array<string>, last_recovery?: array<string, mixed>, auto_recovery_enabled: bool}
     */
    public function getRecoveryStatus(): array
    {
        $health = $this->healthCheck->getHealthStatus();
        $recommendations = [];

        // Generate recommendations based on health status
        if ($health['error_rate']['status'] === 'critical') {
            $recommendations[] = 'High error rate detected. Consider checking DNS server availability.';
        }

        if ($health['response_time']['status'] === 'critical') {
            $recommendations[] = 'Slow response times. Consider adding more DNS servers or optimizing cache.';
        }

        if ($health['cache_hit_rate']['status'] === 'warning') {
            $recommendations[] = 'Low cache hit rate. Consider increasing cache TTL or warming cache.';
        }

        if ($health['circuit_breaker']['status'] === 'critical') {
            $recommendations[] = 'Circuit breaker tripped frequently. DNS service may be unstable.';
        }

        return [
            'health_status' => $health,
            'recommendations' => $recommendations,
            'last_recovery' => Cache::get('dns:last_recovery_time'),
            'auto_recovery_enabled' => config('dns.recovery.auto_enabled', false),
        ];
    }

    /**
     * Get last recovery time
     */
    public function getLastRecoveryTime(): ?Carbon
    {
        $lastRecovery = Cache::get('dns:last_recovery_time');

        return $lastRecovery ? Carbon::parse($lastRecovery) : null;
    }

    /**
     * Check if recovery can be run.
     */
    private function canRunRecovery(): bool
    {
        // Prevent running recovery too frequently (minimum 5 minutes between runs)
        $lastRecovery = $this->getLastRecoveryTime();

        if ($lastRecovery && $lastRecovery->diffInMinutes(Carbon::now()) < 5) {
            return false;
        }

        return true;
    }

    /**
     * Mark recovery as completed.
     */
    public function markRecoveryCompleted(): void
    {
        Cache::put('dns:last_recovery_time', Carbon::now()->toISOString(), 3600);
    }
}
