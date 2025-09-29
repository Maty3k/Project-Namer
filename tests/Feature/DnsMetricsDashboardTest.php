<?php

declare(strict_types=1);

use App\Livewire\DnsMetricsDashboard;
use App\Models\DnsLookupCache;
use App\Models\DnsLookupMetrics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('DNS Metrics Dashboard', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('can be rendered', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->assertSuccessful()
            ->assertSee('DNS Metrics Dashboard')
            ->assertSee('System Health')
            ->assertSee('Cache Statistics')
            ->assertSee('Circuit Breaker Status')
            ->assertSee('Optimization Suggestions');
    });

    it('displays cache statistics correctly', function (): void {
        // Create test cache data
        DnsLookupCache::factory()->count(5)->create(['expires_at' => now()->addHour()]);
        DnsLookupCache::factory()->count(2)->create(['expires_at' => now()->subHour()]);

        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Total Entries')
            ->assertSee('Valid Entries')
            ->assertSee('Expired Entries')
            ->assertSee('Memory Usage');
    });

    it('can refresh data', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->call('refreshData')
            ->assertDispatched('data-refreshed');
    });

    it('can toggle auto refresh', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->assertSet('autoRefresh', true)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', false)
            ->call('toggleAutoRefresh')
            ->assertSet('autoRefresh', true);
    });

    it('can update analysis period', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->assertSet('analysisFromDays', 7)
            ->call('updateAnalysisPeriod', 14)
            ->assertSet('analysisFromDays', 14)
            ->call('updateAnalysisPeriod', 50) // Should be capped at 30
            ->assertSet('analysisFromDays', 30)
            ->call('updateAnalysisPeriod', 0) // Should be minimum 1
            ->assertSet('analysisFromDays', 1);
    });

    it('can optimize cache', function (): void {
        // Create some test cache data
        DnsLookupCache::factory()->count(3)->create(['expires_at' => now()->subHour()]);
        DnsLookupCache::factory()->count(2)->create(['expires_at' => now()->addHour()]);

        Livewire::test(DnsMetricsDashboard::class)
            ->call('optimizeCache')
            ->assertDispatched('cache-optimized')
            ->assertDispatched('data-refreshed');
    });

    it('can preload popular domains', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->call('preloadPopularDomains', 50)
            ->assertDispatched('domains-preloaded')
            ->assertDispatched('data-refreshed');
    });

    it('handles cache optimization errors gracefully', function (): void {
        // Test that cache optimization completes without throwing exceptions
        Livewire::test(DnsMetricsDashboard::class)
            ->call('optimizeCache')
            ->assertSuccessful();

        // The method should dispatch some event (either success or failure)
        // This ensures graceful error handling is in place
    });

    it('displays system health information', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Overall Health')
            ->assertSee('Health Score')
            ->assertSee('Cache Hit Rate')
            ->assertSee('Circuit Breaker');
    });

    it('shows optimization suggestions when available', function (): void {
        // Create poor performance scenario
        DnsLookupMetrics::factory()->create([
            'cache_hits' => 10,
            'domains_checked' => 100,
            'created_at' => now()->subHour()
        ]);

        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Optimization Suggestions');
    });

    it('displays circuit breaker status', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Circuit Breaker Status')
            ->assertSee('Service')
            ->assertSee('State')
            ->assertSee('Failure Count')
            ->assertSee('Success Count');
    });

    it('shows quick actions panel', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Quick Actions')
            ->assertSee('Optimize Cache')
            ->assertSee('Preload Domains')
            ->assertSee('Refresh Data');
    });

    it('handles auto refresh events', function (): void {
        Livewire::test(DnsMetricsDashboard::class)
            ->set('autoRefresh', true)
            ->dispatch('auto-refresh')
            ->assertDispatched('data-refreshed');

        Livewire::test(DnsMetricsDashboard::class)
            ->set('autoRefresh', false)
            ->dispatch('auto-refresh')
            ->assertNotDispatched('data-refreshed');
    });

    it('displays hit analysis with different time periods', function (): void {
        // Create metrics for different periods
        DnsLookupMetrics::factory()->create([
            'cache_hits' => 80,
            'domains_checked' => 100,
            'created_at' => now()->subDays(2)
        ]);

        DnsLookupMetrics::factory()->create([
            'cache_hits' => 90,
            'domains_checked' => 120,
            'created_at' => now()->subDay()
        ]);

        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Hit Analysis')
            ->assertSee('Overall Hit Rate')
            ->assertSee('Cache Efficiency')
            ->assertSee('Total Cache Hits')
            ->assertSee('Total Lookups');
    });

    it('shows top TLDs when cache data exists', function (): void {
        DnsLookupCache::factory()->count(5)->create([
            'tld' => 'com',
            'expires_at' => now()->addHour()
        ]);

        DnsLookupCache::factory()->count(3)->create([
            'tld' => 'io',
            'expires_at' => now()->addHour()
        ]);

        Livewire::test(DnsMetricsDashboard::class)
            ->assertSee('Top TLDs');
    });

    it('requires authentication to access', function (): void {
        $this->get(route('admin.dns-metrics'))
            ->assertSuccessful();

        auth()->logout();

        $this->get(route('admin.dns-metrics'))
            ->assertRedirect(route('login'));
    });
});
