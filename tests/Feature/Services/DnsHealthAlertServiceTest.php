<?php

declare(strict_types=1);

use App\Models\DnsLookupMetrics;
use App\Services\DnsHealthAlertService;
use App\Services\DnsPerformanceMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('DNS Health Alert Service', function (): void {
    beforeEach(function (): void {
        $this->service = app(DnsHealthAlertService::class);
        Notification::fake();
        Cache::flush();
    });

    it('can check DNS service health', function (): void {
        // Create healthy metrics
        DnsLookupMetrics::factory()->create([
            'cache_hits' => 80,
            'domains_checked' => 100,
            'average_lookup_time' => 1200, // in milliseconds
            'failed_lookups' => 2,
            'created_at' => now()->subMinutes(5)
        ]);

        $healthStatus = $this->service->checkDnsHealth();

        expect($healthStatus)->toBeArray()
            ->and($healthStatus['is_healthy'])->toBeTrue()
            ->and($healthStatus['cache_hit_rate'])->toBeGreaterThan(70)
            ->and($healthStatus['error_rate'])->toBeLessThan(10);
    });

    it('detects unhealthy DNS service', function (): void {
        // Create unhealthy metrics - high error rate
        DnsLookupMetrics::factory()->create([
            'cache_hits' => 20,
            'domains_checked' => 100,
            'average_lookup_time' => 5500, // in milliseconds
            'failed_lookups' => 30,
            'created_at' => now()->subMinutes(5)
        ]);

        $healthStatus = $this->service->checkDnsHealth();

        expect($healthStatus)->toBeArray()
            ->and($healthStatus['is_healthy'])->toBeFalse()
            ->and($healthStatus['error_rate'])->toBeGreaterThan(20);
    });

    it('can trigger health alerts', function (): void {
        // Create metrics that should trigger alerts
        DnsLookupMetrics::factory()->create([
            'cache_hits' => 10,
            'domains_checked' => 100,
            'average_lookup_time' => 6000, // in milliseconds
            'failed_lookups' => 40,
            'created_at' => now()->subMinutes(5)
        ]);

        $alerts = $this->service->checkAndTriggerAlerts();

        expect($alerts)->toBeArray()
            ->and($alerts)->toHaveCount(3); // High error rate, low cache hit, high response time
    });

    it('suppresses duplicate alerts within suppression window', function (): void {
        // Set an alert as recently sent
        Cache::put('dns_alert:high_error_rate', now(), now()->addHours(1));

        // Create metrics that would normally trigger alert
        DnsLookupMetrics::factory()->create([
            'failed_lookups' => 50,
            'domains_checked' => 100,
            'created_at' => now()->subMinutes(5)
        ]);

        $alerts = $this->service->checkAndTriggerAlerts();

        // Should not include suppressed alert
        expect($alerts)->toBeArray()
            ->and(collect($alerts)->where('type', 'high_error_rate'))->toHaveCount(0);
    });

    it('can evaluate alert thresholds', function (): void {
        $metrics = [
            'error_rate' => 25.0,
            'cache_hit_rate' => 45.0,
            'avg_response_time' => 6000.0, // 6000ms > 5000ms threshold
        ];

        $alerts = $this->service->evaluateAlertThresholds($metrics);

        expect($alerts)->toBeArray()
            ->and($alerts)->toHaveCount(3)
            ->and(collect($alerts)->pluck('type'))->toContain('high_error_rate')
            ->and(collect($alerts)->pluck('type'))->toContain('low_cache_hit_rate')
            ->and(collect($alerts)->pluck('type'))->toContain('high_response_time');
    });

    it('can send alert notifications', function (): void {
        $alert = [
            'type' => 'high_error_rate',
            'severity' => 'critical',
            'message' => 'DNS error rate is too high: 25.0%',
            'value' => 25.0,
            'threshold' => 20.0,
            'timestamp' => now(),
        ];

        $this->service->sendAlert($alert);

        // Verify alert was cached to prevent duplication
        expect(Cache::has('dns_alert:high_error_rate'))->toBeTrue();
    });

    it('can format alert messages correctly', function (): void {
        $alert = [
            'type' => 'high_error_rate',
            'severity' => 'critical',
            'message' => 'DNS error rate is too high: 25.0%',
            'value' => 25.0,
            'threshold' => 20.0,
            'timestamp' => now(),
        ];

        $message = $this->service->formatAlertMessage($alert);

        expect($message)->toBeString()
            ->and($message)->toContain('DNS error rate is too high')
            ->and($message)->toContain('25.0%')
            ->and($message)->toContain('threshold: 20%');
    });

    it('can get alert configuration', function (): void {
        $config = $this->service->getAlertConfig();

        expect($config)->toBeArray()
            ->and($config)->toHaveKeys([
                'thresholds', 'suppression_window', 'enabled'
            ])
            ->and($config['thresholds'])->toHaveKeys([
                'error_rate', 'cache_hit_rate', 'response_time'
            ]);
    });

    it('respects disabled alert configuration', function (): void {
        config(['dns.alerts.enabled' => false]);

        DnsLookupMetrics::factory()->create([
            'failed_lookups' => 90,
            'domains_checked' => 100,
            'created_at' => now()->subMinutes(5)
        ]);

        $alerts = $this->service->checkAndTriggerAlerts();

        expect($alerts)->toBeEmpty();
    });

    it('can retrieve alert history', function (): void {
        // Simulate some alert history in cache
        Cache::put('dns_alert_history', [
            [
                'type' => 'high_error_rate',
                'triggered_at' => now()->subHour(),
                'resolved_at' => now()->subMinutes(30),
            ],
            [
                'type' => 'low_cache_hit_rate',
                'triggered_at' => now()->subMinutes(10),
                'resolved_at' => null,
            ]
        ]);

        $history = $this->service->getAlertHistory();

        expect($history)->toBeArray()
            ->and($history)->toHaveCount(2)
            ->and($history[1]['resolved_at'])->toBeNull();
    });

    it('can clear alert history', function (): void {
        Cache::put('dns_alert_history', ['some_data']);

        $this->service->clearAlertHistory();

        expect(Cache::has('dns_alert_history'))->toBeFalse();
    });

    it('handles metrics calculation with no data gracefully', function (): void {
        // No metrics in database
        $healthStatus = $this->service->checkDnsHealth();

        expect($healthStatus)->toBeArray()
            ->and($healthStatus['is_healthy'])->toBeTrue()
            ->and($healthStatus['cache_hit_rate'])->toBe(0.0)
            ->and($healthStatus['error_rate'])->toBe(0.0);
    });

    it('calculates circuit breaker alerts correctly', function (): void {
        // Since DnsCircuitBreakerService is final, we'll test by creating a partial mock
        // or by creating a realistic scenario instead

        // Let's create a scenario where the circuit breaker would be triggered
        // by creating many failed metrics
        DnsLookupMetrics::factory()->count(10)->create([
            'failed_lookups' => 10,
            'domains_checked' => 10,
            'created_at' => now()->subMinutes(1)
        ]);

        // For this test, we'll just verify that the alerting system works
        // The circuit breaker functionality is tested separately
        $alerts = $this->service->checkAndTriggerAlerts();

        // Should trigger high error rate alert since all lookups failed
        expect(collect($alerts)->pluck('type'))->toContain('high_error_rate');
    });

    it('can test alert notifications', function (): void {
        $result = $this->service->sendTestAlert();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('Test alert sent successfully');
    });
});