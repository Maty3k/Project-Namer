<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DnsLookupMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class DnsHealthAlertService
{
    public function __construct(
        protected DnsCircuitBreakerService $circuitBreaker
    ) {
    }

    /**
     * Check DNS service health status
     */
    public function checkDnsHealth(): array
    {
        $metrics = $this->getRecentMetrics();

        if ($metrics->isEmpty()) {
            return [
                'is_healthy' => true,
                'cache_hit_rate' => 0.0,
                'error_rate' => 0.0,
                'avg_response_time' => 0.0,
                'total_requests' => 0,
                'last_check' => now(),
                'issues' => [],
            ];
        }

        $totalRequests = $metrics->sum('domains_checked');
        $totalCacheHits = $metrics->sum('cache_hits');
        $totalErrors = $metrics->sum('failed_lookups');
        $avgResponseTime = $metrics->avg('average_lookup_time');

        $cacheHitRate = $totalRequests > 0 ? ($totalCacheHits / $totalRequests) * 100 : 0;
        $errorRate = $totalRequests > 0 ? ($totalErrors / $totalRequests) * 100 : 0;

        $config = $this->getAlertConfig();
        $issues = [];

        // Check health thresholds
        if ($errorRate > $config['thresholds']['error_rate']) {
            $issues[] = 'High error rate';
        }

        if ($cacheHitRate < $config['thresholds']['cache_hit_rate']) {
            $issues[] = 'Low cache hit rate';
        }

        if ($avgResponseTime > $config['thresholds']['response_time']) {
            $issues[] = 'High response time';
        }

        // Check circuit breaker status
        $circuitBreakerStats = $this->circuitBreaker->getCircuitBreakerStats();
        if ($circuitBreakerStats['state'] === 'open') {
            $issues[] = 'Circuit breaker open';
        }

        return [
            'is_healthy' => empty($issues),
            'cache_hit_rate' => round($cacheHitRate, 2),
            'error_rate' => round($errorRate, 2),
            'avg_response_time' => round($avgResponseTime, 2),
            'total_requests' => $totalRequests,
            'last_check' => now(),
            'issues' => $issues,
        ];
    }

    /**
     * Check for alert conditions and trigger alerts
     */
    public function checkAndTriggerAlerts(): array
    {
        $config = $this->getAlertConfig();

        if (!$config['enabled']) {
            return [];
        }

        $healthStatus = $this->checkDnsHealth();
        $alerts = $this->evaluateAlertThresholds([
            'error_rate' => $healthStatus['error_rate'],
            'cache_hit_rate' => $healthStatus['cache_hit_rate'],
            'avg_response_time' => $healthStatus['avg_response_time'],
        ]);

        // Check circuit breaker status
        $circuitBreakerStats = $this->circuitBreaker->getCircuitBreakerStats();
        if ($circuitBreakerStats['state'] === 'open') {
            $alerts[] = [
                'type' => 'circuit_breaker_open',
                'severity' => 'critical',
                'message' => 'DNS circuit breaker is open - service degraded',
                'value' => $circuitBreakerStats['failure_count'],
                'threshold' => $config['thresholds']['circuit_breaker_failures'],
                'timestamp' => now(),
            ];
        }

        $triggeredAlerts = [];

        foreach ($alerts as $alert) {
            if ($this->shouldTriggerAlert($alert)) {
                $this->sendAlert($alert);
                $triggeredAlerts[] = $alert;
            }
        }

        return $triggeredAlerts;
    }

    /**
     * Evaluate metrics against alert thresholds
     */
    public function evaluateAlertThresholds(array $metrics): array
    {
        $config = $this->getAlertConfig();
        $alerts = [];

        // High error rate
        if ($metrics['error_rate'] > $config['thresholds']['error_rate']) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'severity' => 'critical',
                'message' => "DNS error rate is too high: {$metrics['error_rate']}%",
                'value' => $metrics['error_rate'],
                'threshold' => $config['thresholds']['error_rate'],
                'timestamp' => now(),
            ];
        }

        // Low cache hit rate
        if ($metrics['cache_hit_rate'] < $config['thresholds']['cache_hit_rate']) {
            $alerts[] = [
                'type' => 'low_cache_hit_rate',
                'severity' => 'warning',
                'message' => "DNS cache hit rate is too low: {$metrics['cache_hit_rate']}%",
                'value' => $metrics['cache_hit_rate'],
                'threshold' => $config['thresholds']['cache_hit_rate'],
                'timestamp' => now(),
            ];
        }

        // High response time
        if ($metrics['avg_response_time'] > $config['thresholds']['response_time']) {
            $alerts[] = [
                'type' => 'high_response_time',
                'severity' => 'warning',
                'message' => "DNS response time is too high: {$metrics['avg_response_time']}ms",
                'value' => $metrics['avg_response_time'],
                'threshold' => $config['thresholds']['response_time'],
                'timestamp' => now(),
            ];
        }

        return $alerts;
    }

    /**
     * Send alert notification
     */
    public function sendAlert(array $alert): void
    {
        $message = $this->formatAlertMessage($alert);

        // Log alert
        Log::channel('single')->error('DNS Health Alert', [
            'type' => $alert['type'],
            'severity' => $alert['severity'],
            'message' => $message,
            'value' => $alert['value'] ?? null,
            'threshold' => $alert['threshold'] ?? null,
            'timestamp' => $alert['timestamp'],
        ]);

        // Cache alert to prevent duplication
        $cacheKey = "dns_alert:{$alert['type']}";
        $suppressionWindow = $this->getAlertConfig()['suppression_window'];
        Cache::put($cacheKey, now(), now()->addMinutes($suppressionWindow));

        // Store in alert history
        $this->addToAlertHistory($alert);

        // Here you could add additional notification channels:
        // - Email notifications
        // - Slack/Discord webhooks
        // - SMS alerts
        // - External monitoring service notifications
    }

    /**
     * Format alert message for notifications
     */
    public function formatAlertMessage(array $alert): string
    {
        $message = $alert['message'] ?? "DNS health alert: {$alert['type']}";

        if (isset($alert['threshold'])) {
            $message .= " (threshold: {$alert['threshold']}";
            if (str_contains($alert['type'], 'rate')) {
                $message .= '%';
            } elseif (str_contains($alert['type'], 'time')) {
                $message .= 'ms';
            }
            $message .= ')';
        }

        $message .= " - Severity: {$alert['severity']}";
        $message .= " - Time: {$alert['timestamp']->format('Y-m-d H:i:s')}";

        return $message;
    }

    /**
     * Check if alert should be triggered (not suppressed)
     */
    protected function shouldTriggerAlert(array $alert): bool
    {
        $cacheKey = "dns_alert:{$alert['type']}";
        return !Cache::has($cacheKey);
    }

    /**
     * Get alert configuration
     */
    public function getAlertConfig(): array
    {
        return [
            'enabled' => config('dns.alerts.enabled', true),
            'suppression_window' => config('dns.alerts.suppression_window', 60), // minutes
            'thresholds' => [
                'error_rate' => config('dns.alerts.thresholds.error_rate', 20.0), // %
                'cache_hit_rate' => config('dns.alerts.thresholds.cache_hit_rate', 50.0), // %
                'response_time' => config('dns.alerts.thresholds.response_time', 5000.0), // ms
                'circuit_breaker_failures' => config('dns.alerts.thresholds.circuit_breaker_failures', 5),
            ],
        ];
    }

    /**
     * Get recent DNS metrics for analysis
     */
    protected function getRecentMetrics()
    {
        return DnsLookupMetrics::where('created_at', '>=', now()->subHours(1))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Add alert to history
     */
    protected function addToAlertHistory(array $alert): void
    {
        $history = $this->getAlertHistory();

        $history[] = [
            'type' => $alert['type'],
            'severity' => $alert['severity'],
            'message' => $alert['message'],
            'value' => $alert['value'] ?? null,
            'threshold' => $alert['threshold'] ?? null,
            'triggered_at' => $alert['timestamp'],
            'resolved_at' => null,
        ];

        // Keep only last 50 alerts
        $history = array_slice($history, -50);

        Cache::put('dns_alert_history', $history, now()->addDays(7));
    }

    /**
     * Get alert history
     */
    public function getAlertHistory(): array
    {
        return Cache::get('dns_alert_history', []);
    }

    /**
     * Clear alert history
     */
    public function clearAlertHistory(): void
    {
        Cache::forget('dns_alert_history');
    }

    /**
     * Send test alert for testing notification system
     */
    public function sendTestAlert(): array
    {
        $testAlert = [
            'type' => 'test_alert',
            'severity' => 'info',
            'message' => 'This is a test alert from the DNS health monitoring system',
            'timestamp' => now(),
        ];

        $this->sendAlert($testAlert);

        return [
            'success' => true,
            'message' => 'Test alert sent successfully',
            'alert' => $testAlert,
        ];
    }
}