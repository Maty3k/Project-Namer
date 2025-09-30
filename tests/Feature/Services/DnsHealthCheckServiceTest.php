<?php

declare(strict_types=1);

use App\Contracts\DnsHealthAlertServiceInterface;
use App\Contracts\DnsPerformanceMonitorInterface;
use App\Services\DnsHealthCheckService;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    Log::spy();
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.thresholds.error_rate' => 20.0,
        'dns.alerts.thresholds.response_time' => 5000.0,
        'dns.alerts.thresholds.cache_hit_rate' => 50.0,
        'dns.alerts.thresholds.circuit_breaker_failures' => 5,
        'dns.alerts.suppression_window' => 60,
    ]);
});

test('DNS health check service can be instantiated from container', function (): void {
    // Test that the service can be created via dependency injection
    $healthCheck = app(DnsHealthCheckService::class);

    expect($healthCheck)->toBeInstanceOf(DnsHealthCheckService::class);
});

test('DNS health check service performs comprehensive health check', function (): void {
    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock performance metrics - healthy scenario
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(10.0); // Below 20% threshold

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(2000.0); // Below 5000ms threshold

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(75.0); // Above 50% threshold

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(2); // Below 5 threshold

    $alertService->shouldNotReceive('sendAlert');

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result['overall_status'])->toBe('healthy')
        ->and($result['error_rate']['status'])->toBe('healthy')
        ->and($result['response_time']['status'])->toBe('healthy')
        ->and($result['cache_hit_rate']['status'])->toBe('healthy')
        ->and($result['circuit_breaker']['status'])->toBe('healthy');
});

test('DNS health check service detects unhealthy error rate', function (): void {
    // Explicitly ensure config is set
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.thresholds.error_rate' => 20.0,
        'dns.alerts.thresholds.response_time' => 5000.0,
        'dns.alerts.thresholds.cache_hit_rate' => 50.0,
        'dns.alerts.thresholds.circuit_breaker_failures' => 5,
    ]);

    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock performance metrics - high error rate
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(25.0); // Above 20% threshold

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(2000.0);

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(75.0);

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(2);

    $alertService->shouldReceive('sendAlert')
        ->once()
        ->with('error_rate', \Mockery::any());

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result['overall_status'])->toBe('critical')
        ->and($result['error_rate']['status'])->toBe('critical');
});

test('DNS health check service detects slow response times', function (): void {
    // Ensure config is set before service construction
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.thresholds.error_rate' => 20.0,
        'dns.alerts.thresholds.response_time' => 5000.0,
        'dns.alerts.thresholds.cache_hit_rate' => 50.0,
        'dns.alerts.thresholds.circuit_breaker_failures' => 5,
    ]);

    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock performance metrics - slow response time
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(10.0);

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(7000.0); // Above 5000ms threshold

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(75.0);

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(2);

    $alertService->shouldReceive('sendAlert')
        ->once()
        ->with('response_time', [
            'metric' => 'response_time',
            'current_value' => 7000.0,
            'threshold' => 5000.0,
            'status' => 'critical',
        ]);

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result['overall_status'])->toBe('critical')
        ->and($result['response_time']['status'])->toBe('critical');
});

test('DNS health check service detects low cache hit rate', function (): void {
    // Ensure config is set before service construction
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.thresholds.error_rate' => 20.0,
        'dns.alerts.thresholds.response_time' => 5000.0,
        'dns.alerts.thresholds.cache_hit_rate' => 50.0,
        'dns.alerts.thresholds.circuit_breaker_failures' => 5,
    ]);

    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock performance metrics - low cache hit rate
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(10.0);

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(2000.0);

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(30.0); // Below 50% threshold

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(2);

    $alertService->shouldReceive('sendAlert')
        ->once()
        ->with('cache_hit_rate', [
            'metric' => 'cache_hit_rate',
            'current_value' => 30.0,
            'threshold' => 50.0,
            'status' => 'warning',
        ]);

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result['overall_status'])->toBe('warning')
        ->and($result['cache_hit_rate']['status'])->toBe('warning');
});

test('DNS health check service detects circuit breaker failures', function (): void {
    // Explicitly ensure config is set
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.thresholds.error_rate' => 20.0,
        'dns.alerts.thresholds.response_time' => 5000.0,
        'dns.alerts.thresholds.cache_hit_rate' => 50.0,
        'dns.alerts.thresholds.circuit_breaker_failures' => 5,
    ]);

    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock performance metrics - high circuit breaker failures
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(10.0);

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(2000.0);

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(75.0);

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(8); // Above 5 threshold

    $alertService->shouldReceive('sendAlert')
        ->once()
        ->with('circuit_breaker', [
            'metric' => 'circuit_breaker',
            'current_value' => 8,
            'threshold' => 5,
            'status' => 'critical',
        ]);

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result['overall_status'])->toBe('critical')
        ->and($result['circuit_breaker']['status'])->toBe('critical');
});

test('DNS health check service respects disabled configuration', function (): void {
    config(['dns.alerts.enabled' => false]);

    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock poor performance metrics
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(50.0);

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(10000.0);

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(10.0);

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(10);

    // No alerts should be sent when disabled
    $alertService->shouldNotReceive('sendAlert');

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    // Should still report status but not send alerts
    expect($result['overall_status'])->toBe('critical');
});

test('DNS health check service handles multiple simultaneous issues', function (): void {
    // Explicitly ensure config is set
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.thresholds.error_rate' => 20.0,
        'dns.alerts.thresholds.response_time' => 5000.0,
        'dns.alerts.thresholds.cache_hit_rate' => 50.0,
        'dns.alerts.thresholds.circuit_breaker_failures' => 5,
    ]);

    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    // Mock multiple issues
    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(30.0); // Critical

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(8000.0); // Critical

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(20.0); // Warning

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(3); // Healthy

    // Should send alerts for all unhealthy metrics
    $alertService->shouldReceive('sendAlert')
        ->times(3);

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result['overall_status'])->toBe('critical')
        ->and($result['error_rate']['status'])->toBe('critical')
        ->and($result['response_time']['status'])->toBe('critical')
        ->and($result['cache_hit_rate']['status'])->toBe('warning')
        ->and($result['circuit_breaker']['status'])->toBe('healthy');
});

test('DNS health check service provides detailed health metrics', function (): void {
    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(15.0);

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(3000.0);

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(80.0);

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(1);

    $alertService->shouldNotReceive('sendAlert');

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $result = $healthCheck->performHealthCheck();

    expect($result)->toHaveKeys([
        'overall_status',
        'error_rate',
        'response_time',
        'cache_hit_rate',
        'circuit_breaker',
        'checked_at',
    ])
        ->and($result['error_rate'])->toHaveKeys(['value', 'threshold', 'status'])
        ->and($result['error_rate']['value'])->toBe(15.0)
        ->and($result['error_rate']['threshold'])->toBe(20.0);
});

test('DNS health check service logs health check results', function (): void {
    $performanceMonitor = mock(DnsPerformanceMonitorInterface::class);
    $alertService = mock(DnsHealthAlertServiceInterface::class);

    $performanceMonitor->shouldReceive('getErrorRate')
        ->andReturn(10.0);

    $performanceMonitor->shouldReceive('getAverageResponseTime')
        ->andReturn(2000.0);

    $performanceMonitor->shouldReceive('getCacheHitRate')
        ->andReturn(75.0);

    $performanceMonitor->shouldReceive('getCircuitBreakerFailures')
        ->andReturn(2);

    $healthCheck = new DnsHealthCheckService($performanceMonitor, $alertService);
    $healthCheck->performHealthCheck();

    Log::shouldHaveReceived('log')
        ->once()
        ->withArgs(fn ($level, $message, $context) => $level === 'info' &&
               $message === 'DNS health check completed' &&
               isset($context['overall_status']) &&
               $context['overall_status'] === 'healthy');
});
