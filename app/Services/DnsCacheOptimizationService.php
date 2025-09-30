<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DnsLookupServiceInterface;
use App\Models\DnsLookupCache;
use App\Models\DnsLookupMetrics;
use App\Models\NameSuggestion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class DnsCacheOptimizationService
{
    public function __construct(
        private DnsLookupServiceInterface $dnsService
    ) {}

    /**
     * @return array{expired_removed: int, duplicates_cleaned: int, orphaned_cleaned: int, memory_freed: int, cache_stats: array<string, mixed>}
     */
    public function optimizeCache(): array
    {
        $optimization = [
            'expired_removed' => $this->removeExpiredEntries(),
            'duplicates_cleaned' => $this->removeDuplicateEntries(),
            'orphaned_cleaned' => $this->removeOrphanedEntries(),
            'memory_freed' => $this->calculateMemoryFreed(),
            'cache_stats' => $this->getCacheStatistics(),
        ];

        Log::info('DNS cache optimization completed', $optimization);

        return $optimization;
    }

    /**
     * @return array{preloaded_count: int, preloaded_domains: array<string>, skipped_count: int, errors: array<array{domain: string, error: string}>}
     */
    public function preloadPopularDomains(int $limit = 100): array
    {
        $popularDomains = $this->getPopularDomains($limit);
        $preloaded = [];
        $errors = [];

        foreach ($popularDomains as $domain) {
            try {
                // Check if already cached and not expired
                $cachedResult = $this->dnsService->getCachedResult($domain);
                if ($cachedResult !== null) {
                    continue;
                }

                // Perform DNS lookup to populate cache
                $result = $this->dnsService->checkDomain($domain);
                $preloaded[] = $domain;

                Log::debug('DNS cache preloaded', [
                    'domain' => $domain,
                    'has_records' => $result->hasRecords,
                ]);
            } catch (\Exception $e) {
                $errors[] = [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ];

                Log::warning('Failed to preload DNS cache', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'preloaded_count' => count($preloaded),
            'preloaded_domains' => $preloaded,
            'skipped_count' => count($popularDomains) - count($preloaded),
            'errors' => $errors,
        ];
    }

    /**
     * @return array{extended_ttl?: array<string>, stable_extended?: array<string>}
     */
    public function optimizeCacheTtl(): array
    {
        $optimizations = [];

        // Analyze cache hit patterns
        $hitPatterns = $this->analyzeCacheHitPatterns();

        // Optimize TTL for frequently accessed domains
        foreach ($hitPatterns['frequent_domains'] as $domain => $frequency) {
            if ($frequency > 10) {
                // Extend TTL for frequently accessed domains
                $this->extendCacheTtl($domain, 86400 * 3); // 3 days
                $optimizations['extended_ttl'][] = $domain;
            }
        }

        // Reduce TTL for domains with DNS records (they're less likely to change)
        $stableDomains = $this->getStableDomainsWithRecords();
        foreach ($stableDomains as $domain) {
            $this->extendCacheTtl($domain, 86400 * 7); // 7 days
            $optimizations['stable_extended'][] = $domain;
        }

        return $optimizations;
    }

    /**
     * @return array{total_entries: int, active_entries: int, valid_entries: int, expired_entries: int, hit_rate: float, cache_hit_rate: float, size_mb: float, top_tlds: array<mixed>, daily_stats: array{total_cache_hits_24h: int, total_lookups_24h: int}, total_cache_hits_24h: int, total_lookups_24h: int, memory_usage_estimate: float}
     */
    public function getCacheStatistics(): array
    {
        $totalEntries = DnsLookupCache::count();
        $expiredEntries = DnsLookupCache::where('expires_at', '<', now())->count();
        $validEntries = $totalEntries - $expiredEntries;

        $cacheByTld = DnsLookupCache::where('expires_at', '>', now())
            ->select('tld', DB::raw('count(*) as count'))
            ->groupBy('tld')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        /** @var object{avg_hit_rate: float, total_cache_hits: int, total_lookups: int}|null $recentMetrics */
        $recentMetrics = DnsLookupMetrics::where('created_at', '>', now()->subDay())
            ->selectRaw('
                AVG(cache_hits * 100.0 / domains_checked) as avg_hit_rate,
                SUM(cache_hits) as total_cache_hits,
                SUM(domains_checked) as total_lookups
            ')
            ->first();

        $memoryUsage = $this->estimateCacheMemoryUsage();

        return [
            'total_entries' => $totalEntries,
            'active_entries' => $validEntries,
            'valid_entries' => $validEntries, // Backward compatibility alias
            'expired_entries' => $expiredEntries,
            'hit_rate' => round((float) ($recentMetrics->avg_hit_rate ?? 0), 2),
            'cache_hit_rate' => round((float) ($recentMetrics->avg_hit_rate ?? 0), 2), // Backward compatibility alias
            'size_mb' => round($memoryUsage / (1024 * 1024), 2),
            'top_tlds' => $cacheByTld->toArray(),
            'daily_stats' => [
                'total_cache_hits_24h' => (int) ($recentMetrics->total_cache_hits ?? 0),
                'total_lookups_24h' => (int) ($recentMetrics->total_lookups ?? 0),
            ],
            'total_cache_hits_24h' => (int) ($recentMetrics->total_cache_hits ?? 0), // Backward compatibility
            'total_lookups_24h' => (int) ($recentMetrics->total_lookups ?? 0), // Backward compatibility
            'memory_usage_estimate' => $memoryUsage,
        ];
    }

    /**
     * @return array{period_days: int, daily_breakdown: array<mixed>, overall_hit_rate: float, total_cache_hits: int, total_lookups: int, cache_efficiency: float}
     */
    public function getCacheHitAnalysis(int $days = 7): array
    {
        $metrics = DnsLookupMetrics::where('created_at', '>', now()->subDays($days))
            ->selectRaw('
                DATE(created_at) as date,
                SUM(cache_hits) as daily_cache_hits,
                SUM(domains_checked) as daily_lookups,
                AVG(cache_hits * 100.0 / NULLIF(domains_checked, 0)) as daily_hit_rate
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        /** @var object{total_cache_hits: int, total_lookups: int, avg_hit_rate: float}|null $overallStats */
        $overallStats = DnsLookupMetrics::where('created_at', '>', now()->subDays($days))
            ->selectRaw('
                SUM(cache_hits) as total_cache_hits,
                SUM(domains_checked) as total_lookups,
                AVG(cache_hits * 100.0 / NULLIF(domains_checked, 0)) as avg_hit_rate
            ')
            ->first();

        return [
            'period_days' => $days,
            'daily_breakdown' => $metrics->toArray(),
            'overall_hit_rate' => round((float) ($overallStats->avg_hit_rate ?? 0), 2),
            'total_cache_hits' => (int) ($overallStats->total_cache_hits ?? 0),
            'total_lookups' => (int) ($overallStats->total_lookups ?? 0),
            'cache_efficiency' => $this->calculateCacheEfficiency($overallStats),
        ];
    }

    /**
     * @return array{suggestions: array<array{type: string, priority: string, suggestion: string, current_rate?: float, expired_ratio?: float, memory_mb?: float, tld?: string, count?: int}>, optimization_score: int}
     */
    public function suggestCacheImprovements(): array
    {
        $stats = $this->getCacheStatistics();
        $hitAnalysis = $this->getCacheHitAnalysis();
        $suggestions = [];

        // Low cache hit rate suggestions
        if ($hitAnalysis['overall_hit_rate'] < 30) {
            $suggestions[] = [
                'type' => 'low_hit_rate',
                'priority' => 'high',
                'suggestion' => 'Cache hit rate is below 30%. Consider increasing cache TTL or implementing cache warming.',
                'current_rate' => $hitAnalysis['overall_hit_rate'],
            ];
        }

        // High expired entries ratio
        $expiredRatio = ($stats['expired_entries'] / max($stats['total_entries'], 1)) * 100;
        if ($expiredRatio > 20) {
            $suggestions[] = [
                'type' => 'high_expired_ratio',
                'priority' => 'medium',
                'suggestion' => 'High ratio of expired entries. Consider cleaning expired cache more frequently.',
                'expired_ratio' => round($expiredRatio, 2),
            ];
        }

        // Memory usage suggestions
        if ($stats['memory_usage_estimate'] > 100 * 1024 * 1024) { // 100MB
            $suggestions[] = [
                'type' => 'high_memory_usage',
                'priority' => 'medium',
                'suggestion' => 'Cache memory usage is high. Consider implementing cache size limits.',
                'memory_mb' => round($stats['memory_usage_estimate'] / (1024 * 1024), 2),
            ];
        }

        // Popular TLD optimization
        $topTld = $stats['top_tlds'][0] ?? null;
        if ($topTld && $topTld['count'] > 1000) {
            $suggestions[] = [
                'type' => 'tld_optimization',
                'priority' => 'low',
                'suggestion' => "Consider longer TTL for .{$topTld['tld']} domains as they are frequently accessed.",
                'tld' => $topTld['tld'],
                'count' => $topTld['count'],
            ];
        }

        return [
            'suggestions' => $suggestions,
            'optimization_score' => (int) $this->calculateOptimizationScore($stats, $hitAnalysis),
        ];
    }

    private function removeExpiredEntries(): int
    {
        return DnsLookupCache::where('expires_at', '<', now())->delete();
    }

    private function removeDuplicateEntries(): int
    {
        // Find and remove duplicate entries, keeping the most recent one
        $duplicates = DnsLookupCache::select('domain', 'tld', DB::raw('COUNT(*) as count'))
            ->groupBy('domain', 'tld')
            ->having('count', '>', 1)
            ->get();

        $removedCount = 0;
        foreach ($duplicates as $duplicate) {
            $entries = DnsLookupCache::where('domain', $duplicate->domain)
                ->where('tld', $duplicate->tld)
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the first (most recent) entry, delete the rest
            $entries->slice(1)->each(function ($entry) use (&$removedCount): void {
                $entry->delete();
                $removedCount++;
            });
        }

        return $removedCount;
    }

    private function removeOrphanedEntries(): int
    {
        // Remove cache entries for domains that are no longer in name suggestions
        $activeDomains = NameSuggestion::whereNotNull('name')
            ->pluck('name')
            ->map(function ($name) {
                $parts = explode('.', $name);
                $tld = array_pop($parts);
                $domain = implode('.', $parts);

                return ['domain' => $domain, 'tld' => $tld];
            })
            ->unique();

        $activeDomainsCollection = collect($activeDomains);

        return DnsLookupCache::whereNotIn(DB::raw("CONCAT(domain, '.', tld)"),
            $activeDomainsCollection->map(fn ($d) => $d['domain'].'.'.$d['tld'])
        )->delete();
    }

    private function calculateMemoryFreed(): int
    {
        // Estimate memory freed (rough calculation)
        return 0; // Would need before/after comparison
    }

    /**
     * @return array<string>
     */
    private function getPopularDomains(int $limit): array
    {
        return NameSuggestion::select('name')
            ->whereNotNull('name')
            ->groupBy('name')
            ->orderBy(DB::raw('COUNT(*)'), 'desc')
            ->limit($limit)
            ->pluck('name')
            ->toArray();
    }

    /**
     * @return array{frequent_domains: array<mixed>, cache_hit_trends: array<mixed>}
     */
    private function analyzeCacheHitPatterns(): array
    {
        // This would require more detailed tracking, for now return basic analysis
        return [
            'frequent_domains' => [],
            'cache_hit_trends' => [],
        ];
    }

    /**
     * @return array<string>
     */
    private function getStableDomainsWithRecords(): array
    {
        return DnsLookupCache::where('has_records', true)
            ->where('expires_at', '>', now())
            ->where('checked_at', '<', now()->subDays(7))
            ->pluck(DB::raw("CONCAT(domain, '.', tld)"))
            ->toArray();
    }

    private function extendCacheTtl(string $fullDomain, int $newTtlSeconds): void
    {
        $parts = explode('.', $fullDomain);
        $tld = array_pop($parts);
        $domain = implode('.', $parts);

        DnsLookupCache::where('domain', $domain)
            ->where('tld', $tld)
            ->update(['expires_at' => now()->addSeconds($newTtlSeconds)]);
    }

    private function estimateCacheMemoryUsage(): int
    {
        $avgRecordSize = 500; // Rough estimate in bytes per cache record
        $totalRecords = DnsLookupCache::count();

        return $totalRecords * $avgRecordSize;
    }

    /**
     * @param  object{total_cache_hits: int, total_lookups: int}|null  $stats
     */
    private function calculateCacheEfficiency($stats): float
    {
        if (! $stats || (int) ($stats->total_lookups ?? 0) == 0) {
            return 0.0;
        }

        return round(((int) ($stats->total_cache_hits ?? 0) / (int) ($stats->total_lookups ?? 0)) * 100, 2);
    }

    /**
     * @param  array<string, mixed>  $stats
     * @param  array<string, mixed>  $hitAnalysis
     */
    private function calculateOptimizationScore(array $stats, array $hitAnalysis): float
    {
        $score = 100.0;

        // Deduct points for low hit rate
        if ($hitAnalysis['overall_hit_rate'] < 50) {
            $score -= (50 - $hitAnalysis['overall_hit_rate']) * 0.5;
        }

        // Deduct points for high expired ratio
        $expiredRatio = ($stats['expired_entries'] / max($stats['total_entries'], 1)) * 100;
        if ($expiredRatio > 10) {
            $score -= ($expiredRatio - 10) * 0.3;
        }

        // Deduct points for high memory usage
        $memoryMb = $stats['memory_usage_estimate'] / (1024 * 1024);
        if ($memoryMb > 50) {
            $score -= min(($memoryMb - 50) * 0.1, 20);
        }

        return max(round($score, 1), 0.0);
    }
}
