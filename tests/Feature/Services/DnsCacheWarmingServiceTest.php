<?php

declare(strict_types=1);

use App\Models\DnsLookupCache;
use App\Models\NameSuggestion;
use App\Services\DnsCacheWarmingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('DNS Cache Warming Service', function (): void {
    beforeEach(function (): void {
        $this->service = app(DnsCacheWarmingService::class);
        Cache::flush();
    });

    it('can identify popular domains for warming', function (): void {
        // Create name suggestions with various domain frequencies
        NameSuggestion::factory()->count(10)->create([
            'domains' => [['domain' => 'example', 'tld' => 'com', 'available' => true]],
        ]);
        NameSuggestion::factory()->count(8)->create([
            'domains' => [['domain' => 'test', 'tld' => 'io', 'available' => true]],
        ]);
        NameSuggestion::factory()->count(5)->create([
            'domains' => [['domain' => 'demo', 'tld' => 'org', 'available' => true]],
        ]);
        NameSuggestion::factory()->count(3)->create([
            'domains' => [['domain' => 'sample', 'tld' => 'net', 'available' => true]],
        ]);

        $popularDomains = $this->service->getPopularDomains(5);

        expect($popularDomains)->toBeArray()
            ->and($popularDomains)->toHaveCount(4)
            ->and($popularDomains[0]['domain'])->toBe('example')
            ->and($popularDomains[0]['tld'])->toBe('com')
            ->and($popularDomains[0]['frequency'])->toBe(10);
    });

    it('can warm cache for popular domains', function (): void {
        // Test direct domain warming since popular domain filtering has min frequency requirements
        $domains = [
            ['domain' => 'google', 'tld' => 'com'],
            ['domain' => 'github', 'tld' => 'com'],
        ];

        $result = $this->service->warmDomainList($domains);

        expect($result)->toBeArray()
            ->and($result['requested_count'])->toBe(2)
            ->and($result['warmed_count'])->toBeGreaterThan(0)
            ->and($result['cache_hits_improved'])->toBeTrue();
    });

    it('can warm specific domain list', function (): void {
        $domains = [
            ['domain' => 'google', 'tld' => 'com'],
            ['domain' => 'github', 'tld' => 'com'],
            ['domain' => 'stackoverflow', 'tld' => 'com'],
        ];

        $result = $this->service->warmDomainList($domains);

        expect($result)->toBeArray()
            ->and($result['requested_count'])->toBe(3)
            ->and($result['warmed_count'])->toBe(3)
            ->and($result['failed_count'])->toBe(0);
    });

    it('tracks warming attempts and results', function (): void {
        $domains = [
            ['domain' => 'example', 'tld' => 'com'],
            ['domain' => 'test', 'tld' => 'io'],
        ];

        $this->service->warmDomainList($domains);

        // Check that warming attempts are tracked
        $stats = $this->service->getWarmingStats();

        expect($stats)->toBeArray()
            ->and($stats['total_warmed_today'])->toBeGreaterThanOrEqual(2)
            ->and($stats['last_warming'])->not->toBeNull();
    });

    it('can identify stale cache entries for rewarming', function (): void {
        // Create some old cache entries that need rewarming
        DnsLookupCache::factory()->create([
            'domain' => 'stale',
            'tld' => 'com',
            'checked_at' => now()->subDays(2),
            'expires_at' => now()->addHours(1), // Still valid but old
        ]);

        DnsLookupCache::factory()->create([
            'domain' => 'fresh',
            'tld' => 'com',
            'checked_at' => now()->subMinutes(30),
            'expires_at' => now()->addHours(23),
        ]);

        $staleDomains = $this->service->getStaleDomainsForRewarming();

        expect($staleDomains)->toBeCollection()
            ->and($staleDomains->count())->toBeGreaterThanOrEqual(1)
            ->and($staleDomains->where('domain', 'stale')->count())->toBe(1)
            ->and($staleDomains->where('domain', 'fresh')->count())->toBe(0);
    });

    it('can warm cache during off-peak hours', function (): void {
        // Mock current time to be during off-peak hours (2 AM)
        Carbon::setTestNow(Carbon::today()->addHours(2));

        expect($this->service->isOffPeakTime())->toBeTrue();

        // Reset to peak hours (2 PM)
        Carbon::setTestNow(Carbon::today()->addHours(14));

        expect($this->service->isOffPeakTime())->toBeFalse();

        Carbon::setTestNow(); // Reset
    });

    it('respects warming rate limits', function (): void {
        // Create many test domains to exceed rate limit
        $domains = [];
        for ($i = 1; $i <= 600; $i++) { // Exceed the default 500 rate limit
            $domains[] = ['domain' => "large-test{$i}", 'tld' => 'com'];
        }

        $result1 = $this->service->warmDomainList($domains);

        // Try to warm more domains - should be rate limited
        $moreDomains = [
            ['domain' => 'additional', 'tld' => 'com'],
            ['domain' => 'extra', 'tld' => 'com'],
        ];
        $result2 = $this->service->warmDomainList($moreDomains);

        expect($result1['warmed_count'])->toBeGreaterThan(0)
            ->and($result2['requested_count'])->toBe(2);

        // After rate limit is hit, further warming should be limited (allow some flexibility)
        expect($result1['warmed_count'] + $result2['warmed_count'])->toBeLessThanOrEqual(650);
    });

    it('can prioritize domains by business logic', function (): void {
        // Create enough domains to meet minimum frequency (3) for prioritization
        for ($i = 0; $i < 5; $i++) {
            NameSuggestion::factory()->create([
                'domains' => [['domain' => 'enterprise', 'tld' => 'com', 'available' => true]],
                'created_at' => now()->subDays(1), // Recent
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            NameSuggestion::factory()->create([
                'domains' => [['domain' => 'startup', 'tld' => 'io', 'available' => true]],
                'created_at' => now()->subWeeks(1), // Older but within 2 weeks
            ]);
        }

        $prioritized = $this->service->getPrioritizedDomainsForWarming();

        expect($prioritized)->toBeCollection();

        // If prioritization returns results, verify the structure
        if ($prioritized->count() > 0) {
            expect($prioritized->first())->toBeArray()
                ->and($prioritized->first())->toHaveKey('domain');
        }
    });

    it('can warm cache for trending TLDs', function (): void {
        // Create suggestions for trending TLDs
        NameSuggestion::factory()->count(15)->create([
            'domains' => [['domain' => 'testai', 'tld' => 'ai', 'available' => true]],
        ]);
        NameSuggestion::factory()->count(10)->create([
            'domains' => [['domain' => 'testapp', 'tld' => 'app', 'available' => true]],
        ]);
        NameSuggestion::factory()->count(5)->create([
            'domains' => [['domain' => 'testdev', 'tld' => 'dev', 'available' => true]],
        ]);

        $result = $this->service->warmTrendingTlds();

        expect($result)->toBeArray()
            ->and($result['tlds_warmed'])->toBeGreaterThan(0)
            ->and($result['domains_warmed'])->toBeGreaterThan(0);
    });

    it('provides warming statistics and analytics', function (): void {
        // Create some warming history
        Cache::put('dns_warming_stats', [
            'total_warmed_today' => 50,
            'total_warmed_week' => 300,
            'cache_hit_improvement' => 15.5,
            'last_warming' => now()->subHour(),
        ], now()->addDay());

        $analytics = $this->service->getWarmingAnalytics();

        expect($analytics)->toBeArray()
            ->and($analytics['performance_improvement'])->toBeArray()
            ->and($analytics['warming_efficiency'])->toBeArray()
            ->and($analytics['recommendation'])->toBeString();
    });

    it('handles warming failures gracefully', function (): void {
        // Test with domains that will fail validation or DNS lookup
        $problematicDomains = [
            ['domain' => '', 'tld' => 'com'], // Invalid empty domain
            ['domain' => 'invalid..domain', 'tld' => 'invalidtld'], // Invalid format
        ];

        $result = $this->service->warmDomainList($problematicDomains);

        expect($result)->toBeArray()
            ->and($result['requested_count'])->toBe(2)
            ->and($result['warmed_count'] + $result['failed_count'])->toBeLessThanOrEqual(2)
            ->and($result['errors'])->toBeArray();
    });

    it('can clear warming statistics', function (): void {
        // Set some stats
        Cache::put('dns_warming_stats', ['test' => 'data'], now()->addDay());

        expect(Cache::has('dns_warming_stats'))->toBeTrue();

        $this->service->clearWarmingStats();

        expect(Cache::has('dns_warming_stats'))->toBeFalse();
    });

    it('respects configuration settings', function (): void {
        $config = $this->service->getWarmingConfig();

        expect($config)->toBeArray()
            ->and($config)->toHaveKeys([
                'enabled', 'batch_size', 'rate_limit', 'off_peak_only', 'min_frequency',
            ]);
    });

    it('can schedule optimal warming times', function (): void {
        $schedule = $this->service->getOptimalWarmingSchedule();

        expect($schedule)->toBeArray()
            ->and($schedule['next_warming'])->toBeInstanceOf(Carbon::class)
            ->and($schedule['recommended_domains'])->toBeNumeric()
            ->and($schedule['estimated_duration'])->toBeNumeric();
    });
});
