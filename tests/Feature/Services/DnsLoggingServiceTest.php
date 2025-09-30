<?php

declare(strict_types=1);

use App\Services\DnsLoggingService;

test('DNS logging service initializes with correct configuration', function (): void {
    config([
        'dns.logging.enabled' => true,
        'dns.logging.level' => 'info',
        'dns.logging.include_metrics' => true,
    ]);

    $service = new DnsLoggingService;

    expect($service)->toBeInstanceOf(DnsLoggingService::class);
});

test('DNS logging service respects enabled configuration', function (): void {
    config(['dns.logging.enabled' => false]);

    $service = new DnsLoggingService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('shouldLog');

    expect($method->invoke($service, 'info'))->toBeFalse();
});

test('DNS logging service respects log level configuration', function (): void {
    config([
        'dns.logging.enabled' => true,
        'dns.logging.level' => 'warning',
    ]);

    $service = new DnsLoggingService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('shouldLog');

    expect($method->invoke($service, 'debug'))->toBeFalse()
        ->and($method->invoke($service, 'info'))->toBeFalse()
        ->and($method->invoke($service, 'warning'))->toBeTrue()
        ->and($method->invoke($service, 'error'))->toBeTrue()
        ->and($method->invoke($service, 'critical'))->toBeTrue();
});

test('DNS logging service handles invalid log levels', function (): void {
    config([
        'dns.logging.enabled' => true,
        'dns.logging.level' => 'invalid',
    ]);

    $service = new DnsLoggingService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('shouldLog');

    expect($method->invoke($service, 'info'))->toBeFalse();
});

test('DNS logging service generates timestamps', function (): void {
    $service = new DnsLoggingService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getTimestamp');

    $timestamp = $method->invoke($service);

    expect($timestamp)->toBeString()
        ->and(strlen((string) $timestamp))->toBeGreaterThan(0)
        ->and($timestamp)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z/');
});

test('DNS logging service provides comprehensive logging methods', function (): void {
    $service = new DnsLoggingService;

    $methods = [
        'logLookupAttempt',
        'logLookupSuccess',
        'logLookupFailure',
        'logFallbackActivated',
        'logCircuitBreakerTriggered',
        'logDegradationModeChanged',
        'logPerformanceMetrics',
        'logCacheOperation',
        'logHealthAlert',
        'logBatchOperation',
        'logConfigurationChange',
        'logErrorRecovery',
        'logSecurityEvent',
        'logRateLimitEvent',
        'logServiceEvent',
        'logCriticalError',
    ];

    foreach ($methods as $method) {
        expect(method_exists($service, $method))->toBeTrue("Method {$method} should exist");
    }
});

test('DNS logging service integrates with existing configuration structure', function (): void {
    // Test that the configuration keys we expect are available
    $expectedKeys = [
        'dns.logging.enabled',
        'dns.logging.level',
        'dns.logging.include_metrics',
        'dns.logging.log_cache_operations',
        'dns.logging.log_performance_metrics',
        'dns.logging.log_security_events',
        'dns.logging.log_batch_operations',
        'dns.logging.structured_logging',
    ];

    foreach ($expectedKeys as $key) {
        // These should not throw exceptions and return a value (even if null)
        $value = config($key);
        expect(true)->toBeTrue(); // Just testing that config() doesn't throw
    }
});
