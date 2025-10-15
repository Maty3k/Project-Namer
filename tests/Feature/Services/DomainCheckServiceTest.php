<?php

declare(strict_types=1);

use App\Models\DomainCache;
use App\Services\DNSLookupService;
use App\Services\DomainCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class)->group('slow');

describe('Domain Checking Service', function (): void {
    beforeEach(function (): void {
        // Mock DNS service to return no DNS records by default (domain potentially available)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false)->byDefault();
            $mock->shouldReceive('getDNSRecords')->andReturn([
                'A' => [],
                'AAAA' => [],
                'CNAME' => [],
                'MX' => [],
            ])->byDefault();
        });

        $this->service = app(DomainCheckService::class);
    });

    it('can check domain availability for single domain', function (): void {
        // Mock DNS service to return no DNS records (domain potentially available)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $result = $this->service->checkDomain('example.com');

        expect($result)->toBeArray()
            ->and($result['domain'])->toBe('example.com')
            ->and($result['available'])->toBeTrue()
            ->and($result['status'])->toBe('available')
            ->and($result['check_method'])->toBe('dns');
    });

    it('can check multiple domains concurrently', function (): void {
        // Mock DNS service to return no DNS records
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

        $domains = ['example.com', 'example.io', 'example.co', 'example.net'];
        $results = $this->service->checkMultipleDomains($domains);

        expect($results)->toHaveCount(4);
        expect($results['example.com']['available'])->toBeTrue();
        expect($results['example.io']['available'])->toBeTrue();
        expect($results['example.co']['available'])->toBeTrue();
        expect($results['example.net']['available'])->toBeTrue();
    });

    it('can check domains for a business name across all TLDs', function (): void {
        // Mock DNS service to return no DNS records
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false);
        });

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

    it('validates domain names before checking', function (): void {
        expect(fn () => $this->service->checkDomain(''))
            ->toThrow(InvalidArgumentException::class, 'Domain name cannot be empty');

        expect(fn () => $this->service->checkDomain('invalid@domain.com'))
            ->toThrow(InvalidArgumentException::class, 'Invalid domain format');

        expect(fn () => $this->service->checkDomain('domain'))
            ->toThrow(InvalidArgumentException::class, 'Domain must include TLD');
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
    beforeEach(function (): void {
        // Mock DNS service to return no DNS records by default (domain potentially available)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturn(false)->byDefault();
            $mock->shouldReceive('getDNSRecords')->andReturn([
                'A' => [],
                'AAAA' => [],
                'CNAME' => [],
                'MX' => [],
            ])->byDefault();
        });

        $this->service = app(DomainCheckService::class);
    });

    it('marks domains as available when no DNS records found', function (): void {
        // Default mock already returns false for hasDNSRecords (domain potentially available)
        $result = $this->service->checkDomain('example.com');

        expect($result['available'])->toBeTrue();
        expect($result['status'])->toBe('available');
        expect($result['check_method'])->toBe('dns');
        expect($result['cached'])->toBeFalse();
    });

    it('marks domains as taken when DNS records are found', function (): void {
        // Mock DNS service to return true (domain has DNS records)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->andReturn(true);
            $mock->shouldReceive('getDNSRecords')
                ->andReturn([
                    'A' => [['ip' => '192.0.2.1']],
                    'AAAA' => [],
                    'CNAME' => [],
                    'MX' => [],
                ]);
        });

        // Re-instantiate service after mock is set up
        $service = app(DomainCheckService::class);

        $result = $service->checkDomain('example.com');

        expect($result['available'])->toBeFalse();
        expect($result['status'])->toBe('taken');
        expect($result['check_method'])->toBe('dns');
        expect($result['has_dns_records'])->toBeTrue();
    });

    it('returns unknown status when DNS check fails', function (): void {
        // Mock DNS service to return null (DNS check failed/unknown)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->with('example.com')
                ->andReturn(null);
        });

        // Re-instantiate service after mock is set up
        $service = app(DomainCheckService::class);

        $result = $service->checkDomain('example.com');

        expect($result['available'])->toBeNull();
        expect($result['status'])->toBe('unknown');
        expect($result['check_method'])->toBe('dns');
        expect($result['has_dns_records'])->toBeNull();
    });

    it('stores DNS check method in cache', function (): void {
        // Mock DNS service to return false (no DNS records)
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')
                ->andReturn(false);
        });

        $this->service->checkDomain('example.com');

        $cached = DomainCache::where('domain', 'example.com')->first();
        expect($cached)->not->toBeNull();
        expect($cached->check_method)->toBe('dns');
        expect($cached->available)->toBeTrue();
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

        // API cache should be expired - will re-check
        $result1 = $this->service->checkDomain('api-check.com');
        expect($result1['cached'])->toBeFalse();

        // DNS cache should still be valid
        $result2 = $this->service->checkDomain('dns-check.com');
        expect($result2['cached'])->toBeTrue();
    });

    it('filters domains with DNS records in checkBusinessName', function (): void {
        // Mock DNS to show some domains have records, some don't, some unknown
        $this->mock(DNSLookupService::class, function ($mock): void {
            $mock->shouldReceive('hasDNSRecords')->andReturnUsing(function ($domain) {
                if (in_array($domain, ['testbiz.com', 'testbiz.org'])) {
                    return true;  // Has DNS - marked as taken
                }
                if ($domain === 'testbiz.co') {
                    return null;  // Unknown - marked as unknown
                }

                return false;  // No DNS - marked as available
            });
            $mock->shouldReceive('getDNSRecords')->andReturn([
                'A' => [],
                'AAAA' => [],
                'CNAME' => [],
                'MX' => [],
            ]);
        });

        // Re-instantiate service after mock is set up
        $service = app(DomainCheckService::class);

        $results = $service->checkBusinessName('testbiz');

        // Domains with DNS should be marked unavailable
        expect($results['testbiz.com']['available'])->toBeFalse();
        expect($results['testbiz.com']['status'])->toBe('taken');
        expect($results['testbiz.org']['available'])->toBeFalse();
        expect($results['testbiz.org']['status'])->toBe('taken');

        // Domains without DNS should be marked as available
        expect($results['testbiz.net']['available'])->toBeTrue();
        expect($results['testbiz.net']['status'])->toBe('available');
        expect($results['testbiz.io']['available'])->toBeTrue();
        expect($results['testbiz.io']['status'])->toBe('available');

        // Unknown DNS should be marked as unknown
        expect($results['testbiz.co']['available'])->toBeNull();
        expect($results['testbiz.co']['status'])->toBe('unknown');
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

        // Re-instantiate service after mock is set up
        $service = app(DomainCheckService::class);

        $service->checkDomain('example.com');

        $cached = DomainCache::where('domain', 'example.com')->first();
        expect($cached->dns_records)->toBeArray();
        expect($cached->dns_records)->toHaveKey('A');
        expect($cached->dns_records)->toHaveKey('MX');
    });
});
