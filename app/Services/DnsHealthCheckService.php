<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsHealthAlertServiceInterface;
use App\Contracts\DnsPerformanceMonitorInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * DNS Health Check Service
 *
 * Monitors DNS service health by evaluating performance metrics against
 * configured thresholds and triggering alerts when issues are detected.
 */
final class DnsHealthCheckService
{
    /**
     * @var array<string, mixed>
     */
    private array $thresholds;

    public function __construct(
        private readonly DnsPerformanceMonitorInterface $performanceMonitor,
        private readonly DnsHealthAlertServiceInterface $alertService
    ) {
        $thresholds = config('dns.alerts.thresholds', []);
        $this->thresholds = is_array($thresholds) ? $thresholds : [];
    }

    /**
     * Perform comprehensive DNS health check.
     *
     * @return array<string, mixed>
     */
    public function performHealthCheck(): array
    {
        $checkedAt = Carbon::now();

        // Gather all health metrics
        $metrics = $this->gatherHealthMetrics();

        // Evaluate each metric against thresholds
        $healthStatus = $this->evaluateHealth($metrics);

        // Determine overall status
        $overallStatus = $this->determineOverallStatus($healthStatus);

        $result = [
            'overall_status' => $overallStatus,
            'error_rate' => $healthStatus['error_rate'],
            'response_time' => $healthStatus['response_time'],
            'cache_hit_rate' => $healthStatus['cache_hit_rate'],
            'circuit_breaker' => $healthStatus['circuit_breaker'],
            'checked_at' => $checkedAt->toISOString(),
        ];

        // Send alerts for unhealthy metrics
        if (config('dns.alerts.enabled', true)) {
            $this->processAlerts($healthStatus);
        }

        // Log health check results
        $this->logHealthCheck($result);

        return $result;
    }

    /**
     * Get current health status without performing full check.
     *
     * @return array<string, mixed>
     */
    public function getHealthStatus(): array
    {
        $status = $this->performHealthCheck();
        $metrics = $this->gatherHealthMetrics();

        return [
            'overall_status' => $status['overall_status'],
            'error_rate' => $metrics['error_rate'], // Direct numeric value
            'response_time' => $metrics['response_time'], // Direct numeric value
            'cache_hit_rate' => $metrics['cache_hit_rate'], // Direct numeric value
            'circuit_breaker_failures' => $metrics['circuit_breaker_failures'], // Direct numeric value
            'issues' => $this->extractIssueMessages($status),
            'checked_at' => $status['checked_at'],
        ];
    }

    /**
     * Check if DNS service is currently healthy.
     */
    public function isHealthy(): bool
    {
        $status = $this->performHealthCheck();

        return $status['overall_status'] === 'healthy';
    }

    /**
     * Get health check summary for monitoring dashboards.
     *
     * @return array<string, mixed>
     */
    public function getHealthSummary(): array
    {
        $status = $this->performHealthCheck();

        return [
            'status' => $status['overall_status'],
            'last_checked' => $status['checked_at'],
            'issues' => $this->extractIssues($status),
        ];
    }

    /**
     * Gather all health-related metrics.
     *
     * @return array<string, mixed>
     */
    private function gatherHealthMetrics(): array
    {
        return [
            'error_rate' => $this->performanceMonitor->getErrorRate(),
            'response_time' => $this->performanceMonitor->getAverageResponseTime(),
            'cache_hit_rate' => $this->performanceMonitor->getCacheHitRate(),
            'circuit_breaker_failures' => $this->performanceMonitor->getCircuitBreakerFailures(),
        ];
    }

    /**
     * Evaluate health metrics against configured thresholds.
     *
     * @param  array<string, mixed>  $metrics
     * @return array<string, array<string, mixed>>
     */
    private function evaluateHealth(array $metrics): array
    {
        $errorRate = is_numeric($metrics['error_rate']) ? (float) $metrics['error_rate'] : 0.0;
        $responseTime = is_numeric($metrics['response_time']) ? (float) $metrics['response_time'] : 0.0;
        $cacheHitRate = is_numeric($metrics['cache_hit_rate']) ? (float) $metrics['cache_hit_rate'] : 0.0;
        $circuitBreakerFailures = is_numeric($metrics['circuit_breaker_failures']) ? (int) $metrics['circuit_breaker_failures'] : 0;

        return [
            'error_rate' => $this->evaluateErrorRate($errorRate),
            'response_time' => $this->evaluateResponseTime($responseTime),
            'cache_hit_rate' => $this->evaluateCacheHitRate($cacheHitRate),
            'circuit_breaker' => $this->evaluateCircuitBreaker($circuitBreakerFailures),
        ];
    }

    /**
     * Evaluate error rate metric.
     *
     * @return array<string, mixed>
     */
    private function evaluateErrorRate(float $errorRate): array
    {
        $threshold = $this->thresholds['error_rate'] ?? 20.0;
        $status = $errorRate > $threshold ? 'critical' : 'healthy';

        return [
            'value' => $errorRate,
            'threshold' => $threshold,
            'status' => $status,
            'unit' => '%',
        ];
    }

    /**
     * Evaluate response time metric.
     *
     * @return array<string, mixed>
     */
    private function evaluateResponseTime(float $responseTime): array
    {
        $threshold = $this->thresholds['response_time'] ?? 5000.0;
        $status = $responseTime > $threshold ? 'critical' : 'healthy';

        return [
            'value' => $responseTime,
            'threshold' => $threshold,
            'status' => $status,
            'unit' => 'ms',
        ];
    }

    /**
     * Evaluate cache hit rate metric.
     *
     * @return array<string, mixed>
     */
    private function evaluateCacheHitRate(float $cacheHitRate): array
    {
        $threshold = $this->thresholds['cache_hit_rate'] ?? 50.0;
        $status = $cacheHitRate < $threshold ? 'warning' : 'healthy';

        return [
            'value' => $cacheHitRate,
            'threshold' => $threshold,
            'status' => $status,
            'unit' => '%',
        ];
    }

    /**
     * Evaluate circuit breaker failures metric.
     *
     * @return array<string, mixed>
     */
    private function evaluateCircuitBreaker(int $failures): array
    {
        $threshold = $this->thresholds['circuit_breaker_failures'] ?? 5;
        $status = $failures > $threshold ? 'critical' : 'healthy';

        return [
            'value' => $failures,
            'threshold' => $threshold,
            'status' => $status,
            'unit' => 'failures',
        ];
    }

    /**
     * Determine overall system health status.
     *
     * @param  array<string, array<string, mixed>>  $healthStatus
     */
    private function determineOverallStatus(array $healthStatus): string
    {
        $statuses = array_column($healthStatus, 'status');

        if (in_array('critical', $statuses)) {
            return 'critical';
        }

        if (in_array('warning', $statuses)) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Process and send alerts for unhealthy metrics.
     *
     * @param  array<string, array<string, mixed>>  $healthStatus
     */
    private function processAlerts(array $healthStatus): void
    {
        foreach ($healthStatus as $metric => $status) {
            if ($status['status'] !== 'healthy') {
                $alertData = [
                    'metric' => $metric,
                    'current_value' => $status['value'],
                    'threshold' => $status['threshold'],
                    'status' => $status['status'],
                ];

                $this->alertService->sendAlert($metric, $alertData);
            }
        }
    }

    /**
     * Log health check results.
     *
     * @param  array<string, mixed>  $result
     */
    private function logHealthCheck(array $result): void
    {
        $level = match ($result['overall_status']) {
            'critical' => 'error',
            'warning' => 'warning',
            default => 'info'
        };

        $errorRate = is_array($result['error_rate']) ? $result['error_rate'] : [];
        $responseTime = is_array($result['response_time']) ? $result['response_time'] : [];
        $cacheHitRate = is_array($result['cache_hit_rate']) ? $result['cache_hit_rate'] : [];
        $circuitBreaker = is_array($result['circuit_breaker']) ? $result['circuit_breaker'] : [];

        Log::log($level, 'DNS health check completed', [
            'overall_status' => $result['overall_status'],
            'error_rate' => $errorRate['value'] ?? 0,
            'response_time' => $responseTime['value'] ?? 0,
            'cache_hit_rate' => $cacheHitRate['value'] ?? 0,
            'circuit_breaker_failures' => $circuitBreaker['value'] ?? 0,
            'checked_at' => $result['checked_at'],
        ]);
    }

    /**
     * Extract issues from health status for summary.
     *
     * @param  array<string, mixed>  $status
     * @return array<int, array<string, mixed>>
     */
    private function extractIssues(array $status): array
    {
        $issues = [];

        foreach (['error_rate', 'response_time', 'cache_hit_rate', 'circuit_breaker'] as $metric) {
            $metricData = is_array($status[$metric]) ? $status[$metric] : [];
            $statusValue = $metricData['status'] ?? 'unknown';
            $metricStatus = is_string($statusValue) ? $statusValue : 'unknown';

            if ($metricStatus !== 'healthy') {
                $issues[] = [
                    'metric' => $metric,
                    'status' => $metricStatus,
                    'current' => $metricData['value'] ?? 0,
                    'threshold' => $metricData['threshold'] ?? 0,
                ];
            }
        }

        return $issues;
    }

    /**
     * Extract simple issue messages for degradation service.
     *
     * @param  array<string, mixed>  $status
     * @return array<int, string>
     */
    private function extractIssueMessages(array $status): array
    {
        $messages = [];

        $errorRate = is_array($status['error_rate']) ? $status['error_rate'] : [];
        $errorRateStatusValue = $errorRate['status'] ?? 'unknown';
        $errorRateStatus = is_string($errorRateStatusValue) ? $errorRateStatusValue : 'unknown';
        if ($errorRateStatus === 'critical') {
            $messages[] = 'High error rate';
        }

        $responseTime = is_array($status['response_time']) ? $status['response_time'] : [];
        $responseTimeStatusValue = $responseTime['status'] ?? 'unknown';
        $responseTimeStatus = is_string($responseTimeStatusValue) ? $responseTimeStatusValue : 'unknown';
        if ($responseTimeStatus === 'critical') {
            $messages[] = 'High response time';
        }

        $cacheHitRate = is_array($status['cache_hit_rate']) ? $status['cache_hit_rate'] : [];
        $cacheHitRateStatusValue = $cacheHitRate['status'] ?? 'unknown';
        $cacheHitRateStatus = is_string($cacheHitRateStatusValue) ? $cacheHitRateStatusValue : 'unknown';
        if ($cacheHitRateStatus === 'critical') {
            $messages[] = 'Low cache hit rate';
        }

        $circuitBreaker = is_array($status['circuit_breaker']) ? $status['circuit_breaker'] : [];
        $circuitBreakerStatusValue = $circuitBreaker['status'] ?? 'unknown';
        $circuitBreakerStatus = is_string($circuitBreakerStatusValue) ? $circuitBreakerStatusValue : 'unknown';
        if ($circuitBreakerStatus === 'critical') {
            $messages[] = 'Circuit breaker open';
        }

        return $messages;
    }
}
