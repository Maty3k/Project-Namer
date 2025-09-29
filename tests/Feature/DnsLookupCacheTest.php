<?php

declare(strict_types=1);

use App\Models\DnsLookupCache;
use Carbon\Carbon;

beforeEach(function () {
    // Clean up DNS cache table before each test
    DnsLookupCache::query()->delete();
});

test('dns cache model can be created with valid data', function () {
    $cache = DnsLookupCache::create([
        'domain' => 'example',
        'tld' => 'com',
        'has_records' => true,
        'record_types' => ['A', 'MX'],
        'checked_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($cache)
        ->toBeInstanceOf(DnsLookupCache::class)
        ->and($cache->domain)->toBe('example')
        ->and($cache->tld)->toBe('com')
        ->and($cache->has_records)->toBeTrue()
        ->and($cache->record_types)->toBe(['A', 'MX']);
});

test('dns cache model casts record_types to array', function () {
    $cache = DnsLookupCache::create([
        'domain' => 'test',
        'tld' => 'org',
        'has_records' => false,
        'record_types' => ['A', 'AAAA', 'CNAME'],
        'checked_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($cache->record_types)
        ->toBeArray()
        ->toContain('A', 'AAAA', 'CNAME');
});

test('dns cache model casts has_records to boolean', function () {
    $cache = DnsLookupCache::create([
        'domain' => 'boolean-test',
        'tld' => 'net',
        'has_records' => 1,
        'checked_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($cache->has_records)->toBeTrue();

    $cache->update(['has_records' => 0]);
    expect($cache->fresh()->has_records)->toBeFalse();
});

test('dns cache model casts timestamps correctly', function () {
    $checkedAt = now()->subHours(2);
    $expiresAt = now()->addHours(22);

    $cache = DnsLookupCache::create([
        'domain' => 'timestamp-test',
        'tld' => 'io',
        'has_records' => true,
        'checked_at' => $checkedAt,
        'expires_at' => $expiresAt,
    ]);

    expect($cache->checked_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($cache->expires_at)
        ->toBeInstanceOf(Carbon::class);
});

test('dns cache model has isExpired method that works correctly', function () {
    // Create expired cache entry
    $expiredCache = DnsLookupCache::create([
        'domain' => 'expired',
        'tld' => 'com',
        'has_records' => true,
        'checked_at' => now()->subHours(25),
        'expires_at' => now()->subHour(),
    ]);

    // Create valid cache entry
    $validCache = DnsLookupCache::create([
        'domain' => 'valid',
        'tld' => 'com',
        'has_records' => false,
        'checked_at' => now()->subHour(),
        'expires_at' => now()->addHours(23),
    ]);

    expect($expiredCache->isExpired())->toBeTrue();
    expect($validCache->isExpired())->toBeFalse();
});

test('dns cache model findValidCache method returns valid cache', function () {
    // Create expired cache first
    $expiredCache = DnsLookupCache::create([
        'domain' => 'findvalid',
        'tld' => 'com',
        'has_records' => true,
        'checked_at' => now()->subHours(25),
        'expires_at' => now()->subHour(),
    ]);

    // Create valid cache with different domain
    $validCache = DnsLookupCache::create([
        'domain' => 'findvalid-active',
        'tld' => 'com',
        'has_records' => false,
        'checked_at' => now()->subHour(),
        'expires_at' => now()->addHours(23),
    ]);

    $found = DnsLookupCache::findValidCache('findvalid-active', 'com');

    expect($found)
        ->not->toBeNull()
        ->and($found->id)->toBe($validCache->id)
        ->and($found->has_records)->toBeFalse();
});

test('dns cache model findValidCache method returns null for expired cache', function () {
    // Create only expired cache
    DnsLookupCache::create([
        'domain' => 'expired-only',
        'tld' => 'org',
        'has_records' => true,
        'checked_at' => now()->subHours(25),
        'expires_at' => now()->subHour(),
    ]);

    $found = DnsLookupCache::findValidCache('expired-only', 'org');

    expect($found)->toBeNull();
});

test('dns cache model findValidCache method returns null for non-existent domain', function () {
    $found = DnsLookupCache::findValidCache('non-existent', 'xyz');

    expect($found)->toBeNull();
});

test('dns cache model handles error messages correctly', function () {
    $cache = DnsLookupCache::create([
        'domain' => 'error-test',
        'tld' => 'com',
        'has_records' => false,
        'error_message' => 'DNS lookup timeout',
        'checked_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);

    expect($cache->error_message)->toBe('DNS lookup timeout');
});

test('dns cache model enforces unique constraint on domain and tld', function () {
    DnsLookupCache::create([
        'domain' => 'unique-test',
        'tld' => 'com',
        'has_records' => true,
        'checked_at' => now(),
        'expires_at' => now()->addHours(24),
    ]);

    // Attempt to create duplicate should fail
    expect(function () {
        DnsLookupCache::create([
            'domain' => 'unique-test',
            'tld' => 'com',
            'has_records' => false,
            'checked_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);
    })->toThrow(Exception::class);
});
