<?php

declare(strict_types=1);

use App\Contracts\DnsLookupServiceInterface;
use App\DTOs\DnsLookupResult;
use App\Services\DnsDegradationService;
use App\Services\DnsHealthAlertService;
use App\Services\DnsCircuitBreakerService;
use App\Models\DnsLookupMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\mock;

beforeEach(function () {
    Cache::flush();
    DnsLookupMetrics::truncate();
});

test('degradation service enables manual degradation mode', function () {
    // Create healthy metrics to ensure only manual mode triggers degradation
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    expect($degradationService->isDegradedMode())->toBeFalse();

    $degradationService->enableDegradationMode('Testing manual override');

    expect($degradationService->isDegradedMode())->toBeTrue();

    $status = $degradationService->getDegradationStatus();
    expect($status['degraded'])->toBeTrue()
        ->and($status['reason'])->toBe('Testing manual override');
});

test('degradation service disables manual degradation mode', function () {
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    $degradationService->enableDegradationMode('Testing');
    expect($degradationService->isDegradedMode())->toBeTrue();

    $degradationService->disableDegradationMode();
    expect($degradationService->isDegradedMode())->toBeFalse();
});

test('degradation service returns normal status when not degraded', function () {
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    $status = $degradationService->getDegradationStatus();

    expect($status['degraded'])->toBeFalse()
        ->and($status['mode'])->toBe('normal')
        ->and($status['reason'])->toBeNull()
        ->and($status['fallback_strategy'])->toBeNull();
});

test('degradation service handles optimistic strategy in manual mode', function () {
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    // Enable manual mode with optimistic strategy
    $degradationService->enableDegradationMode('Manual override');

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeFalse()
        ->and($result['dns_has_records'])->toBeFalse() // Optimistic assumption
        ->and($result['dns_source'])->toBe('degraded_optimistic')
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['fallback_strategy'])->toBe('optimistic')
        ->and($result['degraded_reason'])->toBe('Assuming domain is available due to DNS unavailability');
});

test('degradation service handles disabled strategy', function () {
    // Set config for disabled strategy during manual override
    config(['dns.degradation.manual_strategy' => 'disabled']);

    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    // Enable manual mode which should use disabled strategy
    $degradationService->enableDegradationMode('Manual override');

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeFalse()
        ->and($result['dns_has_records'])->toBeNull()
        ->and($result['dns_source'])->toBe('disabled')
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['fallback_strategy'])->toBe('disabled')
        ->and($result['degraded_reason'])->toBe('DNS checking disabled due to service unavailability');
});

test('degradation service throws exception when not in degraded mode', function () {
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    expect(fn() => $degradationService->checkDomainInDegradedMode('example.com'))
        ->toThrow(RuntimeException::class, 'DNS service is not in degraded mode');
});

test('degradation service provides no recovery time for manual mode', function () {
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 1,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);
    $degradationService->enableDegradationMode('Manual override');

    $status = $degradationService->getDegradationStatus();

    expect($status['estimated_recovery'])->toBeNull();
});

test('degradation service provides metrics for manual degradation', function () {
    // Use very low error metrics to ensure only manual override triggers degradation
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 0,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);
    $degradationService->enableDegradationMode('Manual testing metrics');

    $metrics = $degradationService->getDegradationMetrics();

    expect($metrics['degraded_mode_active'])->toBeTrue()
        ->and($metrics['degradation_reason'])->toBe('Manual testing metrics')
        ->and($metrics['fallback_strategy'])->toBe('optimistic') // Default manual strategy
        ->and($metrics['manual_override'])->toBeTrue()
        ->and($metrics['health_metrics'])->toBeArray();
});

test('degradation service handles cache-only strategy with DNS service and cache hit', function () {
    $mockResult = new DnsLookupResult(
        hasRecords: true,
        recordTypes: ['A'],
        error: null,
        checkedAt: now()
    );

    $dnsService = mock(DnsLookupServiceInterface::class);
    $dnsService->shouldReceive('getCachedResult')
        ->with('example.com')
        ->andReturn($mockResult);

    // Set up manual degradation mode with cache_only strategy
    config(['dns.degradation.manual_strategy' => 'cache_only']);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService, $dnsService);

    $degradationService->enableDegradationMode('Manual cache test');

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeTrue()
        ->and($result['dns_has_records'])->toBeTrue()
        ->and($result['dns_source'])->toBe('cache')
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['fallback_strategy'])->toBe('cache_only');
});

test('degradation service handles cache-only strategy with no cache', function () {
    $dnsService = mock(DnsLookupServiceInterface::class);
    $dnsService->shouldReceive('getCachedResult')
        ->with('example.com')
        ->andReturn(null);

    // Set up manual degradation mode with cache_only strategy
    config(['dns.degradation.manual_strategy' => 'cache_only']);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService, $dnsService);

    $degradationService->enableDegradationMode('Manual cache test');

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeFalse()
        ->and($result['dns_has_records'])->toBeNull()
        ->and($result['dns_source'])->toBe('degraded')
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['degraded_reason'])->toBe('no_cache');
});

test('degradation service handles DNS service cache exception gracefully', function () {
    $dnsService = mock(DnsLookupServiceInterface::class);
    $dnsService->shouldReceive('getCachedResult')
        ->with('example.com')
        ->andThrow(new Exception('Cache service unavailable'));

    // Set up manual degradation mode with cache_only strategy
    config(['dns.degradation.manual_strategy' => 'cache_only']);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService, $dnsService);

    $degradationService->enableDegradationMode('Manual cache test');

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeFalse()
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['degraded_reason'])->toBe('no_cache');
});

test('degradation service works without DNS service instance', function () {
    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService, null);

    // Set up manual degradation mode with cache_only strategy
    config(['dns.degradation.manual_strategy' => 'cache_only']);

    $degradationService->enableDegradationMode('Manual test without DNS service');

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeFalse()
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['degraded_reason'])->toBe('no_dns_service');
});

test('degradation service handles empty metrics gracefully', function () {
    // No metrics created - empty state

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    expect($degradationService->isDegradedMode())->toBeFalse();

    $status = $degradationService->getDegradationStatus();
    expect($status['degraded'])->toBeFalse()
        ->and($status['mode'])->toBe('normal');
});

test('degradation service manual mode overrides health status', function () {
    // Even with healthy metrics, manual mode should enable degradation
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 0,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    expect($degradationService->isDegradedMode())->toBeFalse();

    $degradationService->enableDegradationMode('Manual emergency maintenance');

    expect($degradationService->isDegradedMode())->toBeTrue();

    $status = $degradationService->getDegradationStatus();
    expect($status['reason'])->toBe('Manual emergency maintenance')
        ->and($status['fallback_strategy'])->toBe('optimistic'); // Default manual strategy
});

test('degradation service handles high error rate scenarios', function () {
    // Create metrics with very high error rate (75% failures)
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 75,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    // High error rate should trigger degraded mode
    expect($degradationService->isDegradedMode())->toBeTrue();

    $status = $degradationService->getDegradationStatus();
    expect($status['degraded'])->toBeTrue()
        ->and($status['reason'])->toBe('High DNS error rate detected')
        ->and($status['fallback_strategy'])->toBe('pessimistic');
});

test('degradation service pessimistic strategy assumes domain is taken', function () {
    // High error rate triggers pessimistic strategy
    DnsLookupMetrics::factory()->create([
        'failed_lookups' => 75,
        'domains_checked' => 100,
    ]);

    $circuitBreaker = app(DnsCircuitBreakerService::class);
    $healthService = new DnsHealthAlertService($circuitBreaker);
    $degradationService = new DnsDegradationService($healthService);

    $result = $degradationService->checkDomainInDegradedMode('example.com');

    expect($result['domain'])->toBe('example.com')
        ->and($result['dns_checked'])->toBeFalse()
        ->and($result['dns_has_records'])->toBeTrue() // Pessimistic assumption
        ->and($result['dns_source'])->toBe('degraded_pessimistic')
        ->and($result['degraded_mode'])->toBeTrue()
        ->and($result['fallback_strategy'])->toBe('pessimistic')
        ->and($result['degraded_reason'])->toBe('Assuming domain is taken due to DNS unavailability');
});