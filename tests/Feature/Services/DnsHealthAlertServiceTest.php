<?php

declare(strict_types=1);

use App\Services\DnsHealthAlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    Log::spy();
    Http::fake();
    Cache::flush();

    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.suppression_window' => 60,
        'dns.alerts.notifications.log_enabled' => true,
        'dns.alerts.notifications.email_enabled' => false,
        'dns.alerts.notifications.webhook_enabled' => false,
        'dns.alerts.notifications.webhook_url' => 'https://example.com/webhook',
    ]);
});

test('DNS health alert service initializes with correct configuration', function (): void {
    $alertService = new DnsHealthAlertService;

    expect($alertService)->toBeInstanceOf(DnsHealthAlertService::class);
});

test('DNS health alert service sends log alerts when enabled', function (): void {
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.notifications.log_enabled' => true,
    ]);

    $alertService = new DnsHealthAlertService;

    $alertData = [
        'metric' => 'error_rate',
        'current_value' => 25.0,
        'threshold' => 20.0,
        'status' => 'critical',
    ];

    $alertService->sendAlert('error_rate', $alertData);

    Log::shouldHaveReceived('log')
        ->once()
        ->withArgs(fn ($level, $message, $context) => $level === 'error' &&
               $message === 'DNS health alert triggered' &&
               $context['metric'] === 'error_rate' &&
               $context['status'] === 'critical');
});

test('DNS health alert service sends webhook alerts when enabled', function (): void {
    config([
        'dns.alerts.notifications.webhook_enabled' => true,
        'dns.alerts.notifications.webhook_url' => 'https://example.com/webhook',
    ]);

    $alertService = new DnsHealthAlertService;

    $alertData = [
        'metric' => 'response_time',
        'current_value' => 7000.0,
        'threshold' => 5000.0,
        'status' => 'critical',
    ];

    $alertService->sendAlert('response_time', $alertData);

    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/webhook' &&
           $request['metric'] === 'response_time' &&
           $request['status'] === 'critical' &&
           $request['current_value'] === 7000.0);
});

test('DNS health alert service respects suppression window', function (): void {
    $alertService = new DnsHealthAlertService;

    $alertData = [
        'metric' => 'error_rate',
        'current_value' => 25.0,
        'threshold' => 20.0,
        'status' => 'critical',
    ];

    // Send first alert
    $alertService->sendAlert('error_rate', $alertData);

    // Send second alert immediately (should be suppressed)
    $alertService->sendAlert('error_rate', $alertData);

    // Should only log once due to suppression
    Log::shouldHaveReceived('log')
        ->once()
        ->withArgs(fn ($level, $message, $context) => $level === 'error' &&
               $message === 'DNS health alert triggered');
});

test('DNS health alert service handles different alert severities', function (): void {
    $alertService = new DnsHealthAlertService;

    // Critical alert
    $criticalData = [
        'metric' => 'error_rate',
        'current_value' => 25.0,
        'threshold' => 20.0,
        'status' => 'critical',
    ];

    $alertService->sendAlert('error_rate', $criticalData);

    // Warning alert
    $warningData = [
        'metric' => 'cache_hit_rate',
        'current_value' => 30.0,
        'threshold' => 50.0,
        'status' => 'warning',
    ];

    $alertService->sendAlert('cache_hit_rate', $warningData);

    // Should have called log twice - once for error, once for warning
    Log::shouldHaveReceived('log')
        ->times(2)
        ->withArgs(fn ($level, $message, $context) => ($level === 'error' || $level === 'warning') &&
               $message === 'DNS health alert triggered');
});

test('DNS health alert service handles webhook failures gracefully', function (): void {
    config([
        'dns.alerts.enabled' => true,
        'dns.alerts.notifications.log_enabled' => true,
        'dns.alerts.notifications.webhook_enabled' => true,
        'dns.alerts.notifications.webhook_url' => 'https://example.com/webhook',
    ]);

    Http::fake([
        'https://example.com/webhook' => Http::response(null, 500),
    ]);

    // Create service AFTER setting the config
    $alertService = new DnsHealthAlertService;

    $alertData = [
        'metric' => 'error_rate',
        'current_value' => 25.0,
        'threshold' => 20.0,
        'status' => 'critical',
    ];

    // Should not throw exception even if webhook fails
    expect(fn () => $alertService->sendAlert('error_rate', $alertData))->not->toThrow(Exception::class);

    // Verify the webhook was attempted
    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/webhook');

    // Verify the main alert was logged
    Log::shouldHaveReceived('log')
        ->atLeast()
        ->once()
        ->withArgs(fn ($level, $message, $context) => $level === 'error' &&
               $message === 'DNS health alert triggered');
});

test('DNS health alert service respects disabled configuration', function (): void {
    config(['dns.alerts.enabled' => false]);

    $alertService = new DnsHealthAlertService;

    $alertData = [
        'metric' => 'error_rate',
        'current_value' => 25.0,
        'threshold' => 20.0,
        'status' => 'critical',
    ];

    $alertService->sendAlert('error_rate', $alertData);

    // Should not send any alerts when disabled
    Log::shouldNotHaveReceived('log');
    Http::assertNothingSent();
});

test('DNS health alert service includes timestamp in alerts', function (): void {
    $alertService = new DnsHealthAlertService;

    $alertData = [
        'metric' => 'error_rate',
        'current_value' => 25.0,
        'threshold' => 20.0,
        'status' => 'critical',
    ];

    $alertService->sendAlert('error_rate', $alertData);

    Log::shouldHaveReceived('log')
        ->once()
        ->withArgs(fn ($level, $message, $context) => $level === 'error' &&
               $message === 'DNS health alert triggered' &&
               isset($context['timestamp']) &&
               isset($context['alert_id']));
});
