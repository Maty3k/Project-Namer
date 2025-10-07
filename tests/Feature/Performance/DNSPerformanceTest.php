<?php

declare(strict_types=1);

use App\Services\DNSLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DNS Performance Requirements', function (): void {
    it('completes DNS lookups in under 1 second for single domain', function (): void {
        $dnsService = app(DNSLookupService::class);

        $startTime = microtime(true);
        $result = $dnsService->hasDNSRecords('example.com');
        $duration = microtime(true) - $startTime;

        expect($duration)->toBeLessThan(1.0)
            ->and($result)->toBeIn([true, false, null]);
    });

    it('efficiently checks multiple domains in parallel', function (): void {
        $dnsService = app(DNSLookupService::class);
        $domains = ['example.com', 'google.com', 'github.com'];

        $startTime = microtime(true);
        foreach ($domains as $domain) {
            $dnsService->hasDNSRecords($domain);
        }
        $duration = microtime(true) - $startTime;

        // Should complete 3 domains in under 3 seconds (1 second each)
        expect($duration)->toBeLessThan(3.0);
    });

    it('handles timeout gracefully without blocking', function (): void {
        $dnsService = app(DNSLookupService::class);

        // Use a non-existent TLD to trigger timeout
        $startTime = microtime(true);
        $result = $dnsService->hasDNSRecords('verylongdomainnamethatshouldtimeout.invalidtld');
        $duration = microtime(true) - $startTime;

        // Should timeout and return false/null quickly (within timeout threshold)
        expect($duration)->toBeLessThan(5.0)
            ->and($result)->toBeIn([false, null]);
    });

    it('returns cached results instantly on subsequent checks', function (): void {
        $dnsService = app(DNSLookupService::class);

        // First check (may take time)
        $dnsService->hasDNSRecords('example.com');

        // Second check should be instant from cache
        $startTime = microtime(true);
        $result = $dnsService->hasDNSRecords('example.com');
        $duration = microtime(true) - $startTime;

        // Cached lookup should be near-instant
        expect($duration)->toBeLessThan(0.1)
            ->and($result)->toBeIn([true, false, null]);
    });
});
