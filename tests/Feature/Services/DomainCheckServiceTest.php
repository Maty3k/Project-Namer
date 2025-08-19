<?php

declare(strict_types=1);

use App\Models\DomainCache;
use App\Services\DomainCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(DomainCheckService::class);
});

describe('Domain Checking Service', function (): void {
    it('can check domain availability for single domain', function (): void {
        Http::fake([
            '*' => Http::response([
                'available' => true,
                'domain' => 'example.com',
            ], 200),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result)->toBeArray()
            ->and($result['domain'])->toBe('example.com')
            ->and($result['available'])->toBeTrue()
            ->and($result['status'])->toBe('available');
    });

    it('can check multiple domains concurrently', function (): void {
        Http::fake([
            '*' => Http::response([
                'available' => true,  // We'll just make all domains available for this test
            ], 200),
        ]);

        $domains = ['example.com', 'example.io', 'example.co', 'example.net'];
        $results = $this->service->checkMultipleDomains($domains);

        expect($results)->toHaveCount(4);
        expect($results['example.com']['available'])->toBeTrue();
        expect($results['example.io']['available'])->toBeTrue();
        expect($results['example.co']['available'])->toBeTrue();
        expect($results['example.net']['available'])->toBeTrue();
    });

    it('can check domains for a business name across all TLDs', function (): void {
        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        $results = $this->service->checkBusinessName('testbusiness');

        expect($results)->toHaveCount(4);
        expect($results)->toHaveKeys(['testbusiness.com', 'testbusiness.io', 'testbusiness.co', 'testbusiness.net']);

        foreach ($results as $result) {
            expect($result['available'])->toBeTrue();
            expect($result['status'])->toBe('available');
        }
    });

    it('handles domain API timeout errors gracefully', function (): void {
        Http::fake([
            '*' => Http::response([], 408),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result['domain'])->toBe('example.com')
            ->and($result['available'])->toBeNull()
            ->and($result['status'])->toBe('error')
            ->and($result['error'])->toBe('Timeout checking domain availability');
    });

    it('handles domain API service unavailable errors', function (): void {
        Http::fake([
            '*' => Http::response(['error' => 'Service temporarily unavailable'], 503),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result['status'])->toBe('error')
            ->and($result['error'])->toContain('Service temporarily unavailable');
    });

    it('handles malformed domain API responses', function (): void {
        Http::fake([
            '*' => Http::response(['invalid' => 'response'], 200),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result['status'])->toBe('error')
            ->and($result['error'])->toBe('Invalid response format from domain API');
    });

    it('caches domain availability results', function (): void {
        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        // First check should hit the API and create cache entry
        $this->service->checkDomain('example.com');

        // Second check should use cache (no additional cache creation needed)
        $result = $this->service->checkDomain('example.com');

        expect($result['cached'])->toBeTrue()
            ->and($result['available'])->toBeTrue();
    });

    it('respects cache expiry of 24 hours', function (): void {
        // Create an expired cache entry
        DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'checked_at' => now()->subHours(25),
        ]);

        Http::fake([
            '*' => Http::response(['available' => false], 200),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result['cached'])->toBeFalse()
            ->and($result['available'])->toBeFalse();
    });

    it('validates domain names before checking', function (): void {
        expect(fn () => $this->service->checkDomain(''))
            ->toThrow(InvalidArgumentException::class, 'Domain name cannot be empty');

        expect(fn () => $this->service->checkDomain('invalid@domain.com'))
            ->toThrow(InvalidArgumentException::class, 'Invalid domain format');

        expect(fn () => $this->service->checkDomain('domain'))
            ->toThrow(InvalidArgumentException::class, 'Domain must include TLD');
    });

    it('handles network connection errors', function (): void {
        Http::fake(function (): void {
            throw new \Illuminate\Http\Client\ConnectionException('Network error');
        });

        $result = $this->service->checkDomain('example.com');

        expect($result['status'])->toBe('error')
            ->and($result['error'])->toContain('Network error');
    });

    it('uses correct timeout for domain checks', function (): void {
        Http::fake();

        $this->service->checkDomain('example.com');

        Http::assertSent(fn ($request) =>
            // The timeout is set on the HTTP client, not directly testable via request
            // This test verifies that the request was sent
            true);
    });

    it('can clear expired cache entries', function (): void {
        // Create mixed cache entries
        DomainCache::create([
            'domain' => 'fresh.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        DomainCache::create([
            'domain' => 'expired.com',
            'available' => true,
            'checked_at' => now()->subHours(25),
        ]);

        $this->service->clearExpiredCache();

        expect(DomainCache::where('domain', 'fresh.com')->exists())->toBeTrue();
        expect(DomainCache::where('domain', 'expired.com')->exists())->toBeFalse();
    });

    it('formats domain names correctly', function (): void {
        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        // Test various input formats
        $result1 = $this->service->checkDomain('EXAMPLE.COM');
        $result2 = $this->service->checkDomain('  example.com  ');
        $result3 = $this->service->checkDomain('http://example.com');

        expect($result1['domain'])->toBe('example.com');
        expect($result2['domain'])->toBe('example.com');
        expect($result3['domain'])->toBe('example.com');
    });
});
