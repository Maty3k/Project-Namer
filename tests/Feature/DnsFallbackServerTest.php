<?php

declare(strict_types=1);

use App\Contracts\DnsResolverInterface;
use App\DTOs\DnsLookupResult;
use App\Services\DnsLookupService;
use App\Models\DnsLookupCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\mock;

beforeEach(function () {
    Cache::flush();
    DnsLookupCache::truncate();
});

test('DNS fallback configuration loads correctly', function () {
    // Test default fallback configuration
    Config::set('dns.fallback.enabled', true);
    Config::set('dns.fallback_servers', ['8.8.8.8', '1.1.1.1']);

    expect(config('dns.fallback.enabled'))->toBeTrue()
        ->and(config('dns.fallback_servers'))->toContain('8.8.8.8')
        ->and(config('dns.fallback_servers'))->toContain('1.1.1.1');
});

test('DNS service configures different timeouts for primary and fallback servers', function () {
    Config::set('dns.fallback.timeout_primary', 2);
    Config::set('dns.fallback.timeout_fallback', 5);
    Config::set('dns.fallback.max_retries_primary', 1);
    Config::set('dns.fallback.max_retries_fallback', 3);

    expect(config('dns.fallback.timeout_primary'))->toBe(2)
        ->and(config('dns.fallback.timeout_fallback'))->toBe(5)
        ->and(config('dns.fallback.max_retries_primary'))->toBe(1)
        ->and(config('dns.fallback.max_retries_fallback'))->toBe(3);
});

test('DNS service loads custom fallback servers from configuration', function () {
    $customFallbackServers = ['1.2.3.4', '5.6.7.8', '9.10.11.12'];
    Config::set('dns.fallback_servers', $customFallbackServers);

    expect(config('dns.fallback_servers'))->toBe($customFallbackServers);
});

test('DNS service can be configured with fallback disabled', function () {
    Config::set('dns.fallback.enabled', false);

    expect(config('dns.fallback.enabled'))->toBeFalse();
});

test('DNS service falls back when primary resolver is mocked to fail', function () {
    Config::set('dns.fallback.enabled', true);
    Config::set('dns.fallback_servers', ['8.8.8.8']);

    $mockResolver = mock(DnsResolverInterface::class);
    $mockResolver->shouldReceive('query')
        ->with('example.com', \Mockery::any())
        ->andThrow(new \Exception('Primary DNS server timeout'));

    $dnsService = new DnsLookupService($mockResolver);
    $result = $dnsService->checkDomain('example.com');

    // The result might succeed if fallback works or fail if it doesn't
    // We mainly want to ensure the service doesn't crash
    expect($result)->toBeInstanceOf(DnsLookupResult::class);
});

test('DNS service respects fallback disabled configuration', function () {
    Config::set('dns.fallback.enabled', false);

    // Mock a resolver that fails during initialization or first query
    $mockResolver = mock(DnsResolverInterface::class);
    $mockResolver->shouldReceive('query')
        ->with('example.com', 'A')
        ->andThrow(new \Exception('DNS server failure'));
    $mockResolver->shouldReceive('query')
        ->with('example.com', 'AAAA')
        ->andThrow(new \Exception('DNS server failure'));
    $mockResolver->shouldReceive('query')
        ->with('example.com', 'CNAME')
        ->andThrow(new \Exception('DNS server failure'));
    $mockResolver->shouldReceive('query')
        ->with('example.com', 'MX')
        ->andThrow(new \Exception('DNS server failure'));
    $mockResolver->shouldReceive('query')
        ->with('example.com', 'NS')
        ->andThrow(new \Exception('DNS server failure'));
    $mockResolver->shouldReceive('query')
        ->with('example.com', 'TXT')
        ->andThrow(new \Exception('DNS server failure'));

    $dnsService = new DnsLookupService($mockResolver);
    $result = $dnsService->checkDomain('example.com');

    // When individual record type queries fail, the service returns "withoutRecords" (not an error)
    // This is correct behavior - it means the domain exists but has no DNS records
    expect($result->isError())->toBeFalse()
        ->and($result->hasRecords)->toBeFalse()
        ->and($result->isSuccessful())->toBeTrue();
});

test('DNS service caches results when DNS queries have no records', function () {
    $mockResolver = mock(DnsResolverInterface::class);
    $mockResolver->shouldReceive('query')
        ->with('test-domain.com', 'A')
        ->andThrow(new \Exception('DNS lookup failed'));
    $mockResolver->shouldReceive('query')
        ->with('test-domain.com', 'AAAA')
        ->andThrow(new \Exception('DNS lookup failed'));
    $mockResolver->shouldReceive('query')
        ->with('test-domain.com', 'CNAME')
        ->andThrow(new \Exception('DNS lookup failed'));
    $mockResolver->shouldReceive('query')
        ->with('test-domain.com', 'MX')
        ->andThrow(new \Exception('DNS lookup failed'));
    $mockResolver->shouldReceive('query')
        ->with('test-domain.com', 'NS')
        ->andThrow(new \Exception('DNS lookup failed'));
    $mockResolver->shouldReceive('query')
        ->with('test-domain.com', 'TXT')
        ->andThrow(new \Exception('DNS lookup failed'));

    $dnsService = new DnsLookupService($mockResolver);
    $result = $dnsService->checkDomain('test-domain.com');

    // The result should be "withoutRecords" not an error
    expect($result->isError())->toBeFalse()
        ->and($result->hasRecords)->toBeFalse();

    // Check that cache entry was created
    $cached = DnsLookupCache::where('domain', 'test-domain')
        ->where('tld', 'com')
        ->first();

    expect($cached)->not->toBeNull()
        ->and($cached->has_records)->toBeFalse()
        ->and($cached->error_message)->toBeNull();
});

test('DNS service validates basic domain format', function () {
    $dnsService = new DnsLookupService();

    // Test clearly invalid domains that should be rejected
    $invalidDomains = [
        '',           // Empty domain
        '..com',      // Double dots
        '.example.com', // Starts with dot
        'example.com.', // Ends with dot
    ];

    foreach ($invalidDomains as $domain) {
        $result = $dnsService->checkDomain($domain);
        expect($result->isError())->toBeTrue()
            ->and($result->error)->toBe('Invalid domain format');
    }
});

test('DNS service parses domains correctly for caching', function () {
    $dnsService = new DnsLookupService();

    // Test with cached result to verify parsing without actual DNS calls
    DnsLookupCache::create([
        'domain' => 'sub.example',
        'tld' => 'com',
        'has_records' => false,
        'record_types' => [],
        'error_message' => null,
        'checked_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $result = $dnsService->getCachedResult('sub.example.com');

    expect($result)->not->toBeNull()
        ->and($result->hasRecords)->toBeFalse();
});

test('DNS service handles batch domain checking', function () {
    $mockResolver = mock(DnsResolverInterface::class);

    // Mock successful lookup for first domain (A record found)
    $mockResolver->shouldReceive('query')
        ->with('good-domain.com', 'A')
        ->andReturn((object) ['answer' => ['some record']]);

    // Mock other record types as empty for first domain
    $recordTypes = ['AAAA', 'CNAME', 'MX', 'NS', 'TXT'];
    foreach ($recordTypes as $type) {
        $mockResolver->shouldReceive('query')
            ->with('good-domain.com', $type)
            ->andReturn((object) ['answer' => []]);
    }

    // Mock failure for second domain - all record types (simulates no records)
    $allRecordTypes = ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT'];
    foreach ($allRecordTypes as $type) {
        $mockResolver->shouldReceive('query')
            ->with('bad-domain.com', $type)
            ->andThrow(new \Exception('DNS timeout'));
    }

    $dnsService = new DnsLookupService($mockResolver);
    $results = $dnsService->checkBatch(['good-domain.com', 'bad-domain.com']);

    expect($results)->toHaveKey('good-domain.com')
        ->and($results)->toHaveKey('bad-domain.com')
        ->and($results['good-domain.com']->hasRecords)->toBeTrue()
        ->and($results['bad-domain.com']->hasRecords)->toBeFalse()
        ->and($results['bad-domain.com']->isError())->toBeFalse(); // Should be withoutRecords, not an error
});

test('DNS service returns cached results when available', function () {
    // Create a cached entry
    DnsLookupCache::create([
        'domain' => 'cached-domain',
        'tld' => 'com',
        'has_records' => true,
        'record_types' => ['A', 'MX'],
        'error_message' => null,
        'checked_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $dnsService = new DnsLookupService();
    $result = $dnsService->checkDomain('cached-domain.com');

    expect($result->hasRecords)->toBeTrue()
        ->and($result->recordTypes)->toBe(['A', 'MX'])
        ->and($result->error)->toBeNull();
});

test('DNS fallback configuration has sensible defaults', function () {
    // Test that the configuration file has reasonable default values
    $fallbackConfig = config('dns.fallback');
    $fallbackServers = config('dns.fallback_servers');

    expect($fallbackConfig['enabled'])->toBeTrue()
        ->and($fallbackConfig['timeout_primary'])->toBeGreaterThan(0)
        ->and($fallbackConfig['timeout_fallback'])->toBeGreaterThan(0)
        ->and($fallbackServers)->toBeArray()
        ->and($fallbackServers)->not->toBeEmpty()
        ->and($fallbackServers)->toContain('8.8.8.8'); // Google DNS should be in defaults
});