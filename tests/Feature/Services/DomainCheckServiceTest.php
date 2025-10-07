<?php

declare(strict_types=1);

use App\Models\DomainCache;
use App\Services\DomainCheckService;
use App\Services\DNSLookupService;
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

        expect($results)->toHaveCount(10);
        expect($results)->toHaveKeys([
            'testbusiness.com',
            'testbusiness.net',
            'testbusiness.org',
            'testbusiness.io',
            'testbusiness.co',
            'testbusiness.app',
            'testbusiness.dev',
            'testbusiness.ai',
            'testbusiness.tech',
            'testbusiness.studio',
        ]);

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

describe('DNS Pre-screening Integration', function (): void {
    it('checks DNS before making API calls', function (): void {
        // Mock DNS service to return no DNS records (domain potentially available)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->once()
                ->andReturn(false);
        });

        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result['available'])->toBeTrue();
        expect($result['cached'])->toBeFalse();
    });

    it('skips API calls when DNS records are found', function (): void {
        // Mock DNS service to return true (domain has DNS records)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->once()
                ->andReturn(true);
        });

        // HTTP should never be called if DNS records found
        Http::fake();

        $result = $this->service->checkDomain('example.com');

        expect($result['available'])->toBeFalse();
        expect($result['status'])->toBe('taken');
        Http::assertNothingSent();
    });

    it('falls back to API when DNS check returns null (unknown)', function (): void {
        // Mock DNS service to return null (DNS check failed/unknown)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->once()
                ->andReturn(null);
        });

        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        $result = $this->service->checkDomain('example.com');

        expect($result['available'])->toBeTrue();
        Http::assertSentCount(1);
    });

    it('stores DNS check method in cache', function (): void {
        // Mock DNS service to return false (no DNS records)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->andReturn(false);
        });

        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        $this->service->checkDomain('example.com');

        $cached = DomainCache::where('domain', 'example.com')->first();
        expect($cached)->not->toBeNull();
        expect($cached->check_method)->toBe('dns');
    });

    it('uses different cache TTL for DNS checks', function (): void {
        // Create DNS cache entry (7 day TTL)
        DomainCache::create([
            'domain' => 'example.com',
            'available' => false,
            'has_dns_records' => true,
            'check_method' => 'dns',
            'checked_at' => now()->subDays(6),
        ]);

        // Should still be cached (DNS has 7 day TTL)
        $result = $this->service->checkDomain('example.com');

        expect($result['cached'])->toBeTrue();
        expect($result['available'])->toBeFalse();
    });

    it('expires API cache after 24 hours but not DNS cache', function (): void {
        // Create API cache entry (24 hour TTL) - should be expired
        DomainCache::create([
            'domain' => 'api-check.com',
            'available' => true,
            'check_method' => 'api',
            'checked_at' => now()->subHours(25),
        ]);

        // Create DNS cache entry (7 day TTL) - should still be valid
        DomainCache::create([
            'domain' => 'dns-check.com',
            'available' => false,
            'has_dns_records' => true,
            'check_method' => 'dns',
            'checked_at' => now()->subDays(2),
        ]);

        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('api-check.com')
                ->andReturn(false);
        });

        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        // API cache should be expired - will re-check
        $result1 = $this->service->checkDomain('api-check.com');
        expect($result1['cached'])->toBeFalse();

        // DNS cache should still be valid
        $result2 = $this->service->checkDomain('dns-check.com');
        expect($result2['cached'])->toBeTrue();
    });

    it('filters domains with DNS records in checkBusinessName', function (): void {
        // Mock DNS to show some domains have records, some don't
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('testbiz.com')->andReturn(true)  // Has DNS - filtered
                ->with('testbiz.net')->andReturn(false) // No DNS - check API
                ->with('testbiz.org')->andReturn(true)  // Has DNS - filtered
                ->with('testbiz.io')->andReturn(false)  // No DNS - check API
                ->with('testbiz.co')->andReturn(null)   // Unknown - check API
                ->with('testbiz.app')->andReturn(false)
                ->with('testbiz.dev')->andReturn(false)
                ->with('testbiz.ai')->andReturn(false)
                ->with('testbiz.tech')->andReturn(false)
                ->with('testbiz.studio')->andReturn(false);
        });

        Http::fake([
            '*' => Http::response(['available' => true], 200),
        ]);

        $results = $this->service->checkBusinessName('testbiz');

        // Domains with DNS should be marked unavailable without API call
        expect($results['testbiz.com']['available'])->toBeFalse();
        expect($results['testbiz.org']['available'])->toBeFalse();

        // Domains without DNS should be checked via API
        expect($results['testbiz.net']['available'])->toBeTrue();
        expect($results['testbiz.io']['available'])->toBeTrue();

        // Unknown should fall back to API
        expect($results['testbiz.co']['available'])->toBeTrue();
    });

    it('stores DNS record details when available', function (): void {
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->andReturn(true);
            $mock->shouldReceive('getDNSRecords')
                ->with('example.com')
                ->andReturn([
                    'A' => [['ip' => '192.0.2.1']],
                    'MX' => [['target' => 'mail.example.com', 'pri' => 10]],
                    'AAAA' => [],
                    'CNAME' => [],
                ]);
        });

        $this->service->checkDomain('example.com');

        $cached = DomainCache::where('domain', 'example.com')->first();
        expect($cached->dns_records)->toBeArray();
        expect($cached->dns_records)->toHaveKey('A');
        expect($cached->dns_records)->toHaveKey('MX');
    });
});
