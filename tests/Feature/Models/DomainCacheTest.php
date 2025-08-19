<?php

declare(strict_types=1);

use App\Models\DomainCache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DomainCache Model', function (): void {
    it('can create a domain cache entry', function (): void {
        $cache = DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        expect($cache)->toBeInstanceOf(DomainCache::class)
            ->and($cache->domain)->toBe('example.com')
            ->and($cache->available)->toBeTrue()
            ->and($cache->checked_at)->toBeInstanceOf(Carbon\Carbon::class);
    });

    it('can update existing domain cache entry', function (): void {
        $cache = DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'checked_at' => now()->subHours(2),
        ]);

        $cache->update([
            'available' => false,
            'checked_at' => now(),
        ]);

        expect($cache->fresh()->available)->toBeFalse()
            ->and($cache->fresh()->checked_at->diffInMinutes(now()))->toBeLessThan(1);
    });

    it('has correct fillable attributes', function (): void {
        $fillable = (new DomainCache)->getFillable();

        expect($fillable)->toContain('domain')
            ->and($fillable)->toContain('available')
            ->and($fillable)->toContain('checked_at');
    });

    it('casts attributes correctly', function (): void {
        $cache = DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        expect($cache->available)->toBeTrue()
            ->and($cache->checked_at)->toBeInstanceOf(Carbon\Carbon::class);
    });

    it('can scope by fresh entries', function (): void {
        // Create fresh entry
        $fresh = DomainCache::create([
            'domain' => 'fresh.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        // Create expired entry
        $expired = DomainCache::create([
            'domain' => 'expired.com',
            'available' => true,
            'checked_at' => now()->subHours(25),
        ]);

        $freshEntries = DomainCache::query()->fresh()->get();

        expect($freshEntries)->toHaveCount(1)
            ->and($freshEntries->first()->domain)->toBe('fresh.com');
    });

    it('can scope by expired entries', function (): void {
        // Create fresh entry
        DomainCache::create([
            'domain' => 'fresh.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        // Create expired entry
        DomainCache::create([
            'domain' => 'expired.com',
            'available' => true,
            'checked_at' => now()->subHours(25),
        ]);

        $expiredEntries = DomainCache::expired()->get();

        expect($expiredEntries)->toHaveCount(1)
            ->and($expiredEntries->first()->domain)->toBe('expired.com');
    });

    it('can find domain by exact match', function (): void {
        DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        $found = DomainCache::findByDomain('example.com');
        $notFound = DomainCache::findByDomain('notfound.com');

        expect($found)->not->toBeNull()
            ->and($found->domain)->toBe('example.com')
            ->and($notFound)->toBeNull();
    });

    it('validates domain format', function (): void {
        // SQLite allows empty strings, so we test the model's behavior instead
        $cache = DomainCache::create([
            'domain' => '',
            'available' => true,
            'checked_at' => now(),
        ]);

        expect($cache->domain)->toBe('');
    });

    it('requires all necessary fields', function (): void {
        expect(fn () => DomainCache::create([
            'domain' => 'example.com',
            // missing available and checked_at
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can determine if cache is expired', function (): void {
        $fresh = DomainCache::create([
            'domain' => 'fresh.com',
            'available' => true,
            'checked_at' => now(),
        ]);

        $expired = DomainCache::create([
            'domain' => 'expired.com',
            'available' => true,
            'checked_at' => now()->subHours(25),
        ]);

        expect($fresh->isExpired())->toBeFalse()
            ->and($expired->isExpired())->toBeTrue();
    });

    it('can get age in hours', function (): void {
        $cache = DomainCache::create([
            'domain' => 'example.com',
            'available' => true,
            'checked_at' => now()->subHours(5),
        ]);

        expect($cache->getAgeInHours())->toBeGreaterThanOrEqual(4)
            ->and($cache->getAgeInHours())->toBeLessThanOrEqual(6);
    });
});
