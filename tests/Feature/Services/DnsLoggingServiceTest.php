<?php

declare(strict_types=1);

use App\Services\DnsLoggingService;
use Illuminate\Support\Facades\Log;
use function Pest\Laravel\mock;

beforeEach(function () {
    // Ensure DNS logging is enabled for tests
    config([
        'dns.logging.enabled' => true,
        'dns.logging.level' => 'debug',
        'dns.logging.include_metrics' => true,
    ]);

    Log::spy();
});

test('DNS logging service logs lookup attempts with structured data', function () {
    // Ensure config is set before creating service
    config([
        'dns.logging.enabled' => true,
        'dns.logging.level' => 'debug',
        'dns.logging.include_metrics' => true,
    ]);

    $loggingService = new DnsLoggingService();

    $loggingService->logLookupAttempt('example.com', 'primary', '8.8.8.8');

    Log::shouldHaveReceived('info')
        ->once()
        ->with('DNS lookup attempt', [
            'domain' => 'example.com',
            'server_type' => 'primary',
            'server' => '8.8.8.8',
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_lookup'
        ]);
});

test('DNS logging service logs lookup success with performance metrics', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logLookupSuccess('example.com', ['A'], 1.5, true);

    Log::shouldHaveReceived('info')
        ->once()
        ->with('DNS lookup successful', [
            'domain' => 'example.com',
            'record_types' => ['A'],
            'response_time_ms' => 1.5,
            'cache_hit' => true,
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_lookup'
        ]);
});

test('DNS logging service logs lookup failures with error details', function () {
    $loggingService = new DnsLoggingService();

    $exception = new Exception('DNS server timeout');
    $loggingService->logLookupFailure('example.com', $exception, 'primary', '8.8.8.8');

    Log::shouldHaveReceived('error')
        ->once()
        ->with('DNS lookup failed', [
            'domain' => 'example.com',
            'error_message' => 'DNS server timeout',
            'error_type' => 'Exception',
            'server_type' => 'primary',
            'server' => '8.8.8.8',
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_lookup'
        ]);
});

test('DNS logging service logs fallback activation', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logFallbackActivated('example.com', 'DNS server timeout', ['8.8.8.8']);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('DNS fallback activated', [
            'domain' => 'example.com',
            'primary_error' => 'DNS server timeout',
            'fallback_servers' => ['8.8.8.8'],
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_fallback'
        ]);
});

test('DNS logging service logs circuit breaker events', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logCircuitBreakerTriggered(5, 60);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('DNS circuit breaker triggered', [
            'failure_count' => 5,
            'timeout_seconds' => 60,
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_circuit_breaker'
        ]);
});

test('DNS logging service logs degradation mode changes', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logDegradationModeChanged(true, 'High error rate detected', 'pessimistic');

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('DNS degradation mode changed', [
            'degraded' => true,
            'reason' => 'High error rate detected',
            'strategy' => 'pessimistic',
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_degradation'
        ]);
});

test('DNS logging service logs performance metrics', function () {
    $loggingService = new DnsLoggingService();

    $metrics = [
        'total_requests' => 100,
        'success_rate' => 95.5,
        'avg_response_time' => 2.3,
        'cache_hit_rate' => 80.0
    ];

    $loggingService->logPerformanceMetrics($metrics);

    Log::shouldHaveReceived('info')
        ->once()
        ->with('DNS performance metrics', [
            'metrics' => $metrics,
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_performance'
        ]);
});

test('DNS logging service logs cache operations', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logCacheOperation('hit', 'example.com', ['A'], 300);

    Log::shouldHaveReceived('debug')
        ->once()
        ->with('DNS cache operation', [
            'operation' => 'hit',
            'domain' => 'example.com',
            'record_types' => ['A'],
            'ttl_seconds' => 300,
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_cache'
        ]);
});

test('DNS logging service logs health alerts', function () {
    $loggingService = new DnsLoggingService();

    $alert = [
        'type' => 'high_error_rate',
        'threshold' => 20.0,
        'current_value' => 25.5,
        'severity' => 'warning'
    ];

    $loggingService->logHealthAlert($alert);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('DNS health alert', [
            'alert' => $alert,
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_health'
        ]);
});

test('DNS logging service logs batch operations', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logBatchOperation('start', 50, 'domain_check');

    Log::shouldHaveReceived('info')
        ->once()
        ->with('DNS batch operation', [
            'operation' => 'start',
            'domain_count' => 50,
            'batch_type' => 'domain_check',
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_batch'
        ]);
});

test('DNS logging service logs configuration changes', function () {
    $loggingService = new DnsLoggingService();

    $changes = [
        'timeout' => ['old' => 2, 'new' => 3],
        'fallback_enabled' => ['old' => false, 'new' => true]
    ];

    $loggingService->logConfigurationChange($changes, 'Manual override');

    Log::shouldHaveReceived('info')
        ->once()
        ->with('DNS configuration changed', [
            'changes' => $changes,
            'reason' => 'Manual override',
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_config'
        ]);
});

test('DNS logging service respects configuration settings', function () {
    config(['dns.logging.enabled' => false]);

    $loggingService = new DnsLoggingService();
    $loggingService->logLookupAttempt('example.com', 'primary', '8.8.8.8');

    Log::shouldNotHaveReceived('info');
});

test('DNS logging service filters by log level', function () {
    config(['dns.logging.level' => 'warning']);

    $loggingService = new DnsLoggingService();

    // Should log (warning level)
    $loggingService->logFallbackActivated('example.com', 'Error', ['8.8.8.8']);

    // Should not log (info level, below warning)
    $loggingService->logLookupSuccess('example.com', ['A'], 1.5, true);

    Log::shouldHaveReceived('warning')->once();
    Log::shouldNotHaveReceived('info');
});

test('DNS logging service logs error recovery events', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logErrorRecovery('circuit_breaker', 'primary_dns', 'Automatic timeout recovery');

    Log::shouldHaveReceived('info')
        ->once()
        ->with('DNS error recovery', [
            'recovery_type' => 'circuit_breaker',
            'component' => 'primary_dns',
            'description' => 'Automatic timeout recovery',
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_recovery'
        ]);
});

test('DNS logging service logs security events', function () {
    $loggingService = new DnsLoggingService();

    $loggingService->logSecurityEvent('potential_dns_poisoning', 'example.com', [
        'expected_ip' => '1.2.3.4',
        'received_ip' => '5.6.7.8',
        'server' => '8.8.8.8'
    ]);

    Log::shouldHaveReceived('error')
        ->once()
        ->with('DNS security event', [
            'event_type' => 'potential_dns_poisoning',
            'domain' => 'example.com',
            'details' => [
                'expected_ip' => '1.2.3.4',
                'received_ip' => '5.6.7.8',
                'server' => '8.8.8.8'
            ],
            'timestamp' => \Mockery::type('string'),
            'context' => 'dns_security'
        ]);
});