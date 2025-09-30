<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\DnsHealthAlertServiceInterface;
use App\Contracts\DnsLookupServiceInterface;
use App\Contracts\DnsPerformanceMonitorInterface;
use App\Services\DnsCircuitBreakerService;
use App\Services\DnsDegradationService;
use App\Services\DnsHealthAlertService;
use App\Services\DnsHealthCheckService;
use App\Services\DnsLoggingService;
use App\Services\DnsLookupService;
use App\Services\DnsPerformanceMonitorService;
use App\Services\DnsRecoveryService;
use App\Services\DnsRetryService;
use Illuminate\Support\ServiceProvider;

final class DnsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the core DNS lookup service
        $this->app->bind('dns.core', DnsLookupService::class);

        // Register the performance monitor
        $this->app->bind(DnsPerformanceMonitorInterface::class, DnsPerformanceMonitorService::class);

        // Register the health alert service interface
        $this->app->bind(DnsHealthAlertServiceInterface::class, DnsHealthAlertService::class);

        // Register the logging service
        $this->app->singleton(DnsLoggingService::class);

        // Register the retry service
        $this->app->singleton(DnsRetryService::class);

        // Register DNS health services
        $this->app->singleton(DnsHealthAlertService::class);
        $this->app->singleton(DnsHealthCheckService::class);
        $this->app->singleton(DnsRecoveryService::class);

        // Register the main DNS lookup service (with optional circuit breaker)
        $this->app->bind(function ($app): \App\Contracts\DnsLookupServiceInterface {
            $coreService = $app->make('dns.core');

            // Check if circuit breaker is enabled
            if (config('dns.circuit_breaker.enabled', true)) {
                return new DnsCircuitBreakerService($coreService);
            }

            return $coreService;
        });

        // Register the degradation service
        $this->app->bind(fn ($app): \App\Services\DnsDegradationService => new DnsDegradationService(
            $app->make(DnsHealthCheckService::class),
            $app->make(DnsLookupServiceInterface::class)
        ));
    }

    public function boot(): void
    {
        //
    }
}
