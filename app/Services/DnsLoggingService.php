<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive DNS logging service for structured error and event logging.
 *
 * Provides centralized logging for all DNS-related operations including
 * lookups, failures, performance metrics, security events, and recovery.
 */
final readonly class DnsLoggingService
{
    private bool $enabled;

    private string $logLevel;

    private bool $includeMetrics;

    public function __construct()
    {
        $this->enabled = (bool) config('dns.logging.enabled', true);
        $logLevel = config('dns.logging.level', 'info');
        $this->logLevel = is_string($logLevel) ? $logLevel : 'info';
        $this->includeMetrics = (bool) config('dns.logging.include_metrics', true);
    }

    /**
     * Log a DNS lookup attempt.
     */
    public function logLookupAttempt(string $domain, string $serverType, string $server): void
    {
        if (! $this->shouldLog('info')) {
            return;
        }

        Log::info('DNS lookup attempt', [
            'domain' => $domain,
            'server_type' => $serverType,
            'server' => $server,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_lookup',
        ]);
    }

    /**
     * Log a successful DNS lookup with performance metrics.
     *
     * @param  array<string>  $recordTypes
     */
    public function logLookupSuccess(string $domain, array $recordTypes, float $responseTimeMs, bool $cacheHit): void
    {
        if (! $this->shouldLog('info')) {
            return;
        }

        $logData = [
            'domain' => $domain,
            'record_types' => $recordTypes,
            'cache_hit' => $cacheHit,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_lookup',
        ];

        if ($this->includeMetrics) {
            $logData['response_time_ms'] = $responseTimeMs;
        }

        Log::info('DNS lookup successful', $logData);
    }

    /**
     * Log a DNS lookup failure with detailed error information.
     */
    public function logLookupFailure(string $domain, Exception $exception, string $serverType, string $server): void
    {
        if (! $this->shouldLog('error')) {
            return;
        }

        Log::error('DNS lookup failed', [
            'domain' => $domain,
            'error_message' => $exception->getMessage(),
            'error_type' => $exception::class,
            'server_type' => $serverType,
            'server' => $server,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_lookup',
        ]);
    }

    /**
     * Log fallback DNS server activation.
     *
     * @param  array<string>  $fallbackServers
     */
    public function logFallbackActivated(string $domain, string $primaryError, array $fallbackServers): void
    {
        if (! $this->shouldLog('warning')) {
            return;
        }

        Log::warning('DNS fallback activated', [
            'domain' => $domain,
            'primary_error' => $primaryError,
            'fallback_servers' => $fallbackServers,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_fallback',
        ]);
    }

    /**
     * Log circuit breaker activation.
     */
    public function logCircuitBreakerTriggered(int $failureCount, int $timeoutSeconds): void
    {
        if (! $this->shouldLog('error')) {
            return;
        }

        Log::error('DNS circuit breaker triggered', [
            'failure_count' => $failureCount,
            'timeout_seconds' => $timeoutSeconds,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_circuit_breaker',
        ]);
    }

    /**
     * Log degradation mode changes.
     */
    public function logDegradationModeChanged(bool $degraded, string $reason, string $strategy): void
    {
        if (! $this->shouldLog('warning')) {
            return;
        }

        Log::warning('DNS degradation mode changed', [
            'degraded' => $degraded,
            'reason' => $reason,
            'strategy' => $strategy,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_degradation',
        ]);
    }

    /**
     * Log DNS performance metrics.
     *
     * @param  array<string, mixed>  $metrics
     */
    public function logPerformanceMetrics(array $metrics): void
    {
        if (! $this->includeMetrics || ! $this->shouldLog('info')) {
            return;
        }

        Log::info('DNS performance metrics', [
            'metrics' => $metrics,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_performance',
        ]);
    }

    /**
     * Log DNS cache operations.
     *
     * @param  array<string>  $recordTypes
     */
    public function logCacheOperation(string $operation, string $domain, array $recordTypes, int $ttlSeconds): void
    {
        if (! $this->shouldLog('debug')) {
            return;
        }

        Log::debug('DNS cache operation', [
            'operation' => $operation,
            'domain' => $domain,
            'record_types' => $recordTypes,
            'ttl_seconds' => $ttlSeconds,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_cache',
        ]);
    }

    /**
     * Log DNS health alerts.
     *
     * @param  array<string, mixed>  $alert
     */
    public function logHealthAlert(array $alert): void
    {
        $severity = $alert['severity'] ?? 'warning';
        $level = is_string($severity) ? $severity : 'warning';

        if (! $this->shouldLog($level)) {
            return;
        }

        Log::$level('DNS health alert', [
            'alert' => $alert,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_health',
        ]);
    }

    /**
     * Log DNS batch operations.
     */
    public function logBatchOperation(string $operation, int $domainCount, string $batchType): void
    {
        if (! $this->shouldLog('info')) {
            return;
        }

        Log::info('DNS batch operation', [
            'operation' => $operation,
            'domain_count' => $domainCount,
            'batch_type' => $batchType,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_batch',
        ]);
    }

    /**
     * Log DNS configuration changes.
     *
     * @param  array<string, mixed>  $changes
     */
    public function logConfigurationChange(array $changes, string $reason): void
    {
        if (! $this->shouldLog('info')) {
            return;
        }

        Log::info('DNS configuration changed', [
            'changes' => $changes,
            'reason' => $reason,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_config',
        ]);
    }

    /**
     * Log DNS error recovery events.
     */
    public function logErrorRecovery(string $recoveryType, string $component, string $description): void
    {
        if (! $this->shouldLog('info')) {
            return;
        }

        Log::info('DNS error recovery', [
            'recovery_type' => $recoveryType,
            'component' => $component,
            'description' => $description,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_recovery',
        ]);
    }

    /**
     * Log DNS security events.
     *
     * @param  array<string, mixed>  $details
     */
    public function logSecurityEvent(string $eventType, string $domain, array $details): void
    {
        if (! $this->shouldLog('error')) {
            return;
        }

        Log::error('DNS security event', [
            'event_type' => $eventType,
            'domain' => $domain,
            'details' => $details,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_security',
        ]);
    }

    /**
     * Log DNS rate limiting events.
     */
    public function logRateLimitEvent(string $provider, int $remainingRequests, int $resetTime): void
    {
        if (! $this->shouldLog('warning')) {
            return;
        }

        Log::warning('DNS rate limit approached', [
            'provider' => $provider,
            'remaining_requests' => $remainingRequests,
            'reset_time' => $resetTime,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_rate_limit',
        ]);
    }

    /**
     * Log DNS service startup and shutdown events.
     *
     * @param  array<string, mixed>  $config
     */
    public function logServiceEvent(string $event, array $config = []): void
    {
        if (! $this->shouldLog('info')) {
            return;
        }

        Log::info("DNS service {$event}", [
            'event' => $event,
            'config' => $config,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_service',
        ]);
    }

    /**
     * Log critical DNS system errors that require immediate attention.
     *
     * @param  array<string, mixed>  $context
     */
    public function logCriticalError(string $component, Exception $exception, array $context = []): void
    {
        if (! $this->shouldLog('critical')) {
            return;
        }

        Log::critical('DNS critical error', [
            'component' => $component,
            'error_message' => $exception->getMessage(),
            'error_type' => $exception::class,
            'error_trace' => $exception->getTraceAsString(),
            'context_data' => $context,
            'timestamp' => $this->getTimestamp(),
            'context' => 'dns_critical',
        ]);
    }

    /**
     * Check if we should log at the given level.
     */
    private function shouldLog(string $level): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $levels = ['debug', 'info', 'warning', 'error', 'critical'];
        $configuredLevelIndex = array_search($this->logLevel, $levels);
        $requestedLevelIndex = array_search($level, $levels);

        // Return false if either level is not found, otherwise compare indices
        if ($configuredLevelIndex === false || $requestedLevelIndex === false) {
            return false;
        }

        return $requestedLevelIndex >= $configuredLevelIndex;
    }

    /**
     * Get current timestamp in ISO 8601 format.
     */
    private function getTimestamp(): string
    {
        $timestamp = Carbon::now()->toISOString();

        return $timestamp ?? date('Y-m-d\TH:i:s.000\Z');
    }
}
