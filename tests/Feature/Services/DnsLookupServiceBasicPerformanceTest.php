<?php

declare(strict_types=1);

use App\Contracts\DnsPerformanceMonitorInterface;
use App\Contracts\DnsResolverInterface;
use App\Services\DnsLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DNS Lookup Service Basic Performance Integration', function (): void {
    it('works without performance monitor', function (): void {
        $resolver = \Mockery::mock(DnsResolverInterface::class);
        $resolver->shouldReceive('query')->andReturn((object) ['answer' => []]);

        $service = new DnsLookupService(resolver: $resolver);

        $result = $service->checkDomain('test.com');

        expect($result)->not()->toBeNull();
    });

    it('works with null performance monitor', function (): void {
        $resolver = \Mockery::mock(DnsResolverInterface::class);
        $resolver->shouldReceive('query')->andReturn((object) ['answer' => []]);

        $service = new DnsLookupService(
            resolver: $resolver,
            performanceMonitor: null
        );

        $result = $service->checkDomain('test.com');

        expect($result)->not()->toBeNull();
    });

    it('calls performance monitor when provided', function (): void {
        $resolver = \Mockery::mock(DnsResolverInterface::class);
        $resolver->shouldReceive('query')->andReturn((object) ['answer' => []]);

        $monitor = \Mockery::mock(DnsPerformanceMonitorInterface::class);
        $monitor->shouldReceive('recordLookup')
            ->once()
            ->withArgs(function ($domain, $time, $success, $cache, $error) {
                return $domain === 'test.com' && $success === true && $cache === false;
            });

        $service = new DnsLookupService(
            resolver: $resolver,
            performanceMonitor: $monitor
        );

        $result = $service->checkDomain('test.com');

        expect($result)->not()->toBeNull();
    });
});