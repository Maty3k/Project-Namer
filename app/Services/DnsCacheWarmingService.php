<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DnsLookupCache;
use App\Models\NameSuggestion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DnsCacheWarmingService
{
    public function __construct(
        protected DnsLookupService $dnsLookupService
    ) {
    }

    /**
     * Get popular domains based on frequency of use in name suggestions
     */
    public function getPopularDomains(int $limit = 100): array
    {
        $domainCounts = [];

        NameSuggestion::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('domains')
            ->get()
            ->each(function ($suggestion) use (&$domainCounts) {
                $domains = $suggestion->domains ?? [];

                foreach ($domains as $domainData) {
                    if (isset($domainData['domain']) && isset($domainData['tld'])) {
                        $key = $domainData['domain'] . '.' . $domainData['tld'];

                        if (!isset($domainCounts[$key])) {
                            $domainCounts[$key] = [
                                'domain' => $domainData['domain'],
                                'tld' => $domainData['tld'],
                                'frequency' => 0
                            ];
                        }

                        $domainCounts[$key]['frequency']++;
                    }
                }
            });

        return collect($domainCounts)
            ->sortByDesc('frequency')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Warm cache for popular domains
     */
    public function warmPopularDomains(int $limit = 50): array
    {
        $config = $this->getWarmingConfig();

        if (!$config['enabled']) {
            return [
                'warmed_count' => 0,
                'total_popular' => 0,
                'reason' => 'Cache warming disabled',
                'cache_hits_improved' => false,
            ];
        }

        if ($this->isRateLimited()) {
            return [
                'warmed_count' => 0,
                'rate_limited' => true,
                'cache_hits_improved' => false,
            ];
        }

        if ($config['off_peak_only'] && !$this->isOffPeakTime()) {
            return [
                'warmed_count' => 0,
                'total_popular' => 0,
                'reason' => 'Outside off-peak hours',
                'cache_hits_improved' => false,
            ];
        }

        $popularDomains = $this->getPopularDomains($limit);
        $filteredDomains = collect($popularDomains)
            ->where('frequency', '>=', $config['min_frequency'])
            ->take($limit)
            ->toArray();

        return $this->warmDomainList($filteredDomains);
    }

    /**
     * Warm cache for a specific list of domains
     */
    public function warmDomainList(array $domains): array
    {
        $batchSize = $this->getWarmingConfig()['batch_size'];
        $warmed = 0;
        $failed = 0;
        $errors = [];
        $startTime = microtime(true);

        $batches = array_chunk($domains, $batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $domainData) {
                $domain = is_array($domainData) ? $domainData['domain'] : $domainData;
                $tld = is_array($domainData) ? $domainData['tld'] : 'com';
                $fullDomain = "{$domain}.{$tld}";

                try {
                    // Check if already cached and fresh
                    if ($this->isCacheFresh($domain, $tld)) {
                        continue;
                    }

                    // Perform DNS lookup to warm cache
                    $result = $this->dnsLookupService->checkDomain($fullDomain);

                    if ($result->error === null) {
                        $warmed++;
                        $this->recordWarmingSuccess($domain, $tld);
                    } else {
                        $failed++;
                        $errors[] = ['domain' => $fullDomain, 'error' => $result->error];
                    }

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = ['domain' => $fullDomain, 'error' => $e->getMessage()];
                }
            }

            // Small delay between batches to avoid overwhelming DNS servers
            if (count($batches) > 1) {
                usleep(100000); // 100ms
            }
        }

        $duration = microtime(true) - $startTime;

        $result = [
            'requested_count' => count($domains),
            'warmed_count' => $warmed,
            'failed_count' => $failed,
            'duration_seconds' => round($duration, 2),
            'cache_hits_improved' => $warmed > 0,
            'errors' => $errors,
        ];

        $this->updateWarmingStats($result);

        Log::info('DNS cache warming completed', $result);

        return $result;
    }

    /**
     * Get stale domains that need rewarming
     */
    public function getStaleDomainsForRewarming(): Collection
    {
        $staleThreshold = now()->subHours(12); // Consider 12+ hours old as stale

        return DnsLookupCache::where('checked_at', '<', $staleThreshold)
            ->where('expires_at', '>', now()) // Still valid but getting old
            ->orderBy('checked_at')
            ->limit(100)
            ->get();
    }

    /**
     * Check if current time is during off-peak hours
     */
    public function isOffPeakTime(): bool
    {
        $currentHour = now()->hour;

        // Off-peak hours: 11 PM to 6 AM (23-06)
        return $currentHour >= 23 || $currentHour <= 6;
    }

    /**
     * Get prioritized domains for warming based on business logic
     */
    public function getPrioritizedDomainsForWarming(): Collection
    {
        $domains = NameSuggestion::where('created_at', '>=', now()->subWeeks(2))
            ->whereNotNull('domains')
            ->get()
            ->flatMap(function ($suggestion) {
                $domains = $suggestion->domains ?? [];
                $results = [];

                foreach ($domains as $domainData) {
                    if (isset($domainData['domain']) && isset($domainData['tld'])) {
                        $key = $domainData['domain'] . '.' . $domainData['tld'];
                        $results[$key] = [
                            'domain' => $domainData['domain'],
                            'tld' => $domainData['tld'],
                            'frequency' => 1,
                            'is_recent' => $suggestion->created_at >= now()->subDays(7)
                        ];
                    }
                }

                return $results;
            })
            ->groupBy(function ($item, $key) {
                return $key;
            })
            ->map(function ($group) {
                $first = $group->first();
                $frequency = $group->count();
                $hasRecent = $group->contains('is_recent', true);

                return [
                    'domain' => $first['domain'],
                    'tld' => $first['tld'],
                    'frequency' => $frequency,
                    'weighted_frequency' => $frequency * ($hasRecent ? 2 : 1)
                ];
            })
            ->filter(function ($item) {
                return $item['frequency'] >= 3;
            })
            ->sortByDesc('weighted_frequency')
            ->take(200);

        return collect($domains->values());
    }

    /**
     * Warm cache for trending TLDs
     */
    public function warmTrendingTlds(): array
    {
        // Get trending TLDs from recent suggestions
        $tldFrequency = [];

        NameSuggestion::where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('domains')
            ->get()
            ->each(function ($suggestion) use (&$tldFrequency) {
                $domains = $suggestion->domains ?? [];
                foreach ($domains as $domainData) {
                    if (isset($domainData['tld'])) {
                        $tld = $domainData['tld'];
                        $tldFrequency[$tld] = ($tldFrequency[$tld] ?? 0) + 1;
                    }
                }
            });

        // Filter and sort trending TLDs
        $trendingTlds = collect($tldFrequency)
            ->filter(fn($frequency) => $frequency >= 5)
            ->sortDesc()
            ->take(10)
            ->keys()
            ->toArray();

        $warmedTlds = 0;
        $totalWarmed = 0;

        foreach ($trendingTlds as $tld) {
            $domains = NameSuggestion::where('created_at', '>=', now()->subDays(7))
                ->whereNotNull('domains')
                ->get()
                ->flatMap(function ($suggestion) use ($tld) {
                    $domains = $suggestion->domains ?? [];
                    $results = [];

                    foreach ($domains as $domainData) {
                        if (isset($domainData['domain'], $domainData['tld']) && $domainData['tld'] === $tld) {
                            $key = $domainData['domain'] . '.' . $domainData['tld'];
                            $results[$key] = [
                                'domain' => $domainData['domain'],
                                'tld' => $domainData['tld']
                            ];
                        }
                    }

                    return $results;
                })
                ->unique()
                ->take(10)
                ->values()
                ->toArray();

            if (!empty($domains)) {
                $result = $this->warmDomainList($domains);
                $totalWarmed += $result['warmed_count'];

                if ($result['warmed_count'] > 0) {
                    $warmedTlds++;
                }
            }
        }

        return [
            'tlds_warmed' => $warmedTlds,
            'domains_warmed' => $totalWarmed,
            'trending_tlds' => $trendingTlds,
        ];
    }

    /**
     * Get warming statistics and analytics
     */
    public function getWarmingAnalytics(): array
    {
        $stats = $this->getWarmingStats();
        $cacheStats = app(DnsCacheOptimizationService::class)->getCacheStatistics();

        $performanceImprovement = [
            'cache_hit_rate' => $cacheStats['cache_hit_rate'] ?? 0,
            'total_cached_domains' => $cacheStats['total_entries'] ?? 0,
            'warming_contribution' => $this->calculateWarmingContribution(),
        ];

        $warmingEfficiency = [
            'success_rate' => $this->calculateWarmingSuccessRate(),
            'avg_domains_per_session' => $this->getAverageDomainsPerSession(),
            'cost_effectiveness' => $this->calculateCostEffectiveness(),
        ];

        $recommendation = $this->generateWarmingRecommendation($stats, $performanceImprovement);

        return [
            'current_stats' => $stats,
            'performance_improvement' => $performanceImprovement,
            'warming_efficiency' => $warmingEfficiency,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Get warming configuration
     */
    public function getWarmingConfig(): array
    {
        return [
            'enabled' => config('dns.performance.enable_cache_warming', true),
            'batch_size' => config('dns.warming.batch_size', 10),
            'rate_limit' => config('dns.warming.rate_limit_per_hour', 500),
            'off_peak_only' => config('dns.warming.off_peak_only', false),
            'min_frequency' => config('dns.warming.min_frequency', 2),
            'stale_threshold_hours' => config('dns.warming.stale_threshold_hours', 12),
        ];
    }

    /**
     * Get optimal warming schedule
     */
    public function getOptimalWarmingSchedule(): array
    {
        $config = $this->getWarmingConfig();
        $popularCount = count($this->getPopularDomains(100));
        $staleCount = $this->getStaleDomainsForRewarming()->count();

        $recommendedDomains = min($popularCount + $staleCount, $config['rate_limit']);
        $estimatedDuration = ($recommendedDomains / $config['batch_size']) * 2; // 2 seconds per batch estimate

        $nextWarming = $config['off_peak_only']
            ? $this->getNextOffPeakTime()
            : now()->addMinutes(30);

        return [
            'next_warming' => $nextWarming,
            'recommended_domains' => $recommendedDomains,
            'estimated_duration' => $estimatedDuration,
            'off_peak_required' => $config['off_peak_only'],
        ];
    }

    /**
     * Get warming statistics
     */
    public function getWarmingStats(): array
    {
        return Cache::get('dns_warming_stats', [
            'total_warmed_today' => 0,
            'total_warmed_week' => 0,
            'cache_hit_improvement' => 0,
            'last_warming' => null,
            'avg_success_rate' => 0,
        ]);
    }

    /**
     * Clear warming statistics
     */
    public function clearWarmingStats(): void
    {
        Cache::forget('dns_warming_stats');
    }

    /**
     * Check if cache is fresh for a domain
     */
    protected function isCacheFresh(string $domain, string $tld): bool
    {
        $cacheEntry = DnsLookupCache::where('domain', $domain)
            ->where('tld', $tld)
            ->first();

        if (!$cacheEntry) {
            return false;
        }

        $staleThreshold = $this->getWarmingConfig()['stale_threshold_hours'];
        return $cacheEntry->checked_at > now()->subHours($staleThreshold);
    }

    /**
     * Record successful warming attempt
     */
    protected function recordWarmingSuccess(string $domain, string $tld): void
    {
        // This could be used for additional logging or analytics
        Log::debug("Cache warmed successfully for {$domain}.{$tld}");
    }

    /**
     * Check if warming is rate limited
     */
    protected function isRateLimited(): bool
    {
        $rateLimit = $this->getWarmingConfig()['rate_limit'];
        $currentCount = Cache::get('dns_warming_hourly_count', 0);

        return $currentCount >= $rateLimit;
    }

    /**
     * Update warming statistics
     */
    protected function updateWarmingStats(array $result): void
    {
        $stats = $this->getWarmingStats();

        $stats['total_warmed_today'] += $result['warmed_count'];
        $stats['total_warmed_week'] += $result['warmed_count'];
        $stats['last_warming'] = now();

        // Update success rate
        $totalAttempts = ($stats['total_attempted'] ?? 0) + $result['requested_count'];
        $totalSuccesses = ($stats['total_successes'] ?? 0) + $result['warmed_count'];
        $stats['avg_success_rate'] = $totalAttempts > 0 ? ($totalSuccesses / $totalAttempts) * 100 : 0;
        $stats['total_attempted'] = $totalAttempts;
        $stats['total_successes'] = $totalSuccesses;

        Cache::put('dns_warming_stats', $stats, now()->addDays(7));

        // Update hourly rate limit counter
        $hourlyCount = Cache::get('dns_warming_hourly_count', 0);
        Cache::put('dns_warming_hourly_count', $hourlyCount + $result['requested_count'], now()->addHour());
    }

    /**
     * Calculate warming contribution to cache performance
     */
    protected function calculateWarmingContribution(): float
    {
        // Estimate what percentage of cache hits are from warmed entries
        return 25.0; // Placeholder - could be calculated from actual data
    }

    /**
     * Calculate warming success rate
     */
    protected function calculateWarmingSuccessRate(): float
    {
        $stats = $this->getWarmingStats();
        return $stats['avg_success_rate'] ?? 0;
    }

    /**
     * Get average domains warmed per session
     */
    protected function getAverageDomainsPerSession(): float
    {
        // Could be calculated from historical data
        return 25.0; // Placeholder
    }

    /**
     * Calculate cost effectiveness of warming
     */
    protected function calculateCostEffectiveness(): string
    {
        $successRate = $this->calculateWarmingSuccessRate();

        if ($successRate > 85) {
            return 'High';
        } elseif ($successRate > 60) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Generate warming recommendation
     */
    protected function generateWarmingRecommendation(array $stats, array $performance): string
    {
        if ($performance['cache_hit_rate'] < 50) {
            return 'Increase warming frequency and domain count to improve cache hit rate';
        }

        if ($stats['avg_success_rate'] < 60) {
            return 'Review domain selection criteria - many warming attempts are failing';
        }

        if ($performance['total_cached_domains'] < 100) {
            return 'Cache is under-utilized - consider warming more popular domains';
        }

        return 'Cache warming is performing well - maintain current strategy';
    }

    /**
     * Get next off-peak time
     */
    protected function getNextOffPeakTime(): Carbon
    {
        $now = now();

        // If currently in off-peak (23:00-06:00), return now
        if ($this->isOffPeakTime()) {
            return $now;
        }

        // Otherwise return next 23:00
        return $now->hour < 23 ? $now->setTime(23, 0, 0) : $now->addDay()->setTime(23, 0, 0);
    }
}