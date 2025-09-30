<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DnsCacheWarmingJob;
use App\Services\DnsCacheWarmingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class DnsCacheWarmCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dns:warm
                           {strategy=popular : Warming strategy (popular|trending|stale|custom|analytics|schedule)}
                           {--limit=100 : Number of domains to warm}
                           {--domains= : Comma-separated list of domains (domain:tld format)}
                           {--async : Run warming jobs asynchronously in background}
                           {--force : Force warming even during peak hours}
                           {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage DNS cache warming strategies and operations';

    /**
     * Execute the console command.
     */
    public function handle(DnsCacheWarmingService $warmingService): int
    {
        $strategy = $this->argument('strategy');
        $async = $this->option('async');
        $format = $this->option('format');

        return match ($strategy) {
            'popular' => $this->warmPopularDomains($warmingService, $async, $format),
            'trending' => $this->warmTrendingDomains($warmingService, $async, $format),
            'stale' => $this->rewarmStaleDomains($warmingService, $async, $format),
            'custom' => $this->warmCustomDomains($warmingService, $async, $format),
            'analytics' => $this->showAnalytics($warmingService, $format),
            'schedule' => $this->showSchedule($warmingService, $format),
            default => $this->showHelp(),
        };
    }

    /**
     * Warm popular domains
     */
    protected function warmPopularDomains(DnsCacheWarmingService $warmingService, bool $async, string $format): int
    {
        $limit = (int) $this->option('limit');

        $this->info('🔥 Warming popular domains...');

        if ($async) {
            DnsCacheWarmingJob::warmPopular($limit);
            $this->info("✅ Popular domains warming job dispatched (limit: {$limit})");

            return 0;
        }

        if (! $this->option('force') && ! $warmingService->isOffPeakTime()) {
            if (! $this->confirm('It\'s peak hours. Warming may impact performance. Continue?')) {
                $this->info('Warming cancelled');

                return 0;
            }
        }

        $result = $warmingService->warmPopularDomains($limit);

        return $this->displayResult($result, 'Popular Domains Warming', $format);
    }

    /**
     * Warm trending domains
     */
    protected function warmTrendingDomains(DnsCacheWarmingService $warmingService, bool $async, string $format): int
    {
        $this->info('📈 Warming trending domains and TLDs...');

        if ($async) {
            DnsCacheWarmingJob::warmTrending();
            $this->info('✅ Trending domains warming job dispatched');

            return 0;
        }

        $result = $warmingService->warmTrendingTlds();

        return $this->displayResult($result, 'Trending Domains Warming', $format);
    }

    /**
     * Rewarm stale domains
     */
    protected function rewarmStaleDomains(DnsCacheWarmingService $warmingService, bool $async, string $format): int
    {
        $limit = (int) $this->option('limit');

        $this->info('♻️ Rewarming stale cache entries...');

        if ($async) {
            DnsCacheWarmingJob::rewarmStale($limit);
            $this->info("✅ Stale domains rewarming job dispatched (limit: {$limit})");

            return 0;
        }

        $staleDomains = $warmingService->getStaleDomainsForRewarming();
        $domainList = $staleDomains->take($limit)->map(fn ($cache) => ['domain' => $cache->domain, 'tld' => $cache->tld])->toArray();

        if (empty($domainList)) {
            $this->info('✅ No stale domains found for rewarming');

            return 0;
        }

        $result = $warmingService->warmDomainList($domainList);

        return $this->displayResult($result, 'Stale Domains Rewarming', $format);
    }

    /**
     * Warm custom domain list
     */
    protected function warmCustomDomains(DnsCacheWarmingService $warmingService, bool $async, string $format): int
    {
        $domainsOption = $this->option('domains');

        if (! $domainsOption) {
            $this->error('Please provide domains using --domains option (format: domain1:tld1,domain2:tld2)');

            return 1;
        }

        $domains = [];
        foreach (explode(',', $domainsOption) as $domainString) {
            $parts = explode(':', trim($domainString));
            if (count($parts) === 2) {
                $domains[] = ['domain' => $parts[0], 'tld' => $parts[1]];
            }
        }

        if (empty($domains)) {
            $this->error('No valid domains parsed. Use format: domain1:tld1,domain2:tld2');

            return 1;
        }

        $this->info('🎯 Warming custom domain list...');

        if ($async) {
            DnsCacheWarmingJob::warmCustom($domains);
            $this->info('✅ Custom domains warming job dispatched ('.count($domains).' domains)');

            return 0;
        }

        $result = $warmingService->warmDomainList($domains);

        return $this->displayResult($result, 'Custom Domains Warming', $format);
    }

    /**
     * Show warming analytics
     */
    protected function showAnalytics(DnsCacheWarmingService $warmingService, string $format): int
    {
        $analytics = $warmingService->getWarmingAnalytics();

        if ($format === 'json') {
            $this->line(json_encode($analytics, JSON_PRETTY_PRINT));

            return 0;
        }

        $this->info('📊 DNS Cache Warming Analytics');
        $this->newLine();

        // Current Stats
        $stats = $analytics['current_stats'];
        $this->line('<info>Current Statistics:</info>');
        $this->table(['Metric', 'Value'], [
            ['Domains warmed today', number_format($stats['total_warmed_today'] ?? 0)],
            ['Domains warmed this week', number_format($stats['total_warmed_week'] ?? 0)],
            ['Average success rate', number_format($stats['avg_success_rate'] ?? 0, 1).'%'],
            ['Last warming', $stats['last_warming'] ? Carbon::parse($stats['last_warming'])->diffForHumans() : 'Never'],
        ]);

        $this->newLine();

        // Performance Impact
        $performance = $analytics['performance_improvement'];
        $this->line('<info>Performance Impact:</info>');
        $this->table(['Metric', 'Value'], [
            ['Current cache hit rate', number_format($performance['cache_hit_rate'], 1).'%'],
            ['Total cached domains', number_format($performance['total_cached_domains'])],
            ['Warming contribution', number_format($performance['warming_contribution'], 1).'%'],
        ]);

        $this->newLine();

        // Efficiency
        $efficiency = $analytics['warming_efficiency'];
        $this->line('<info>Warming Efficiency:</info>');
        $this->table(['Metric', 'Value'], [
            ['Success rate', number_format($efficiency['success_rate'], 1).'%'],
            ['Avg domains per session', number_format($efficiency['avg_domains_per_session'])],
            ['Cost effectiveness', $efficiency['cost_effectiveness']],
        ]);

        $this->newLine();

        // Recommendation
        $this->line('<comment>💡 Recommendation:</comment>');
        $this->line($analytics['recommendation']);

        return 0;
    }

    /**
     * Show warming schedule
     */
    protected function showSchedule(DnsCacheWarmingService $warmingService, string $format): int
    {
        $schedule = $warmingService->getOptimalWarmingSchedule();
        $config = $warmingService->getWarmingConfig();

        if ($format === 'json') {
            $this->line(json_encode([
                'schedule' => $schedule,
                'config' => $config,
            ], JSON_PRETTY_PRINT));

            return 0;
        }

        $this->info('📅 DNS Cache Warming Schedule');
        $this->newLine();

        $this->line('<info>Current Configuration:</info>');
        $this->table(['Setting', 'Value'], [
            ['Enabled', $config['enabled'] ? '✅ Yes' : '❌ No'],
            ['Batch size', $config['batch_size']],
            ['Rate limit (per hour)', $config['rate_limit']],
            ['Off-peak only', $config['off_peak_only'] ? '✅ Yes' : '❌ No'],
            ['Min frequency threshold', $config['min_frequency']],
            ['Stale threshold (hours)', $config['stale_threshold_hours']],
        ]);

        $this->newLine();

        $this->line('<info>Optimal Schedule:</info>');
        $this->table(['Aspect', 'Value'], [
            ['Next recommended warming', $schedule['next_warming']->format('Y-m-d H:i:s')],
            ['Time until next warming', $schedule['next_warming']->diffForHumans()],
            ['Recommended domains to warm', number_format($schedule['recommended_domains'])],
            ['Estimated duration (minutes)', number_format($schedule['estimated_duration'])],
            ['Off-peak required', $schedule['off_peak_required'] ? '✅ Yes' : '❌ No'],
        ]);

        $this->newLine();

        $popularCount = count($warmingService->getPopularDomains(100));
        $staleCount = $warmingService->getStaleDomainsForRewarming()->count();

        $this->line('<info>Available for Warming:</info>');
        $this->table(['Type', 'Count'], [
            ['Popular domains', number_format($popularCount)],
            ['Stale cache entries', number_format($staleCount)],
            ['Total available', number_format($popularCount + $staleCount)],
        ]);

        return 0;
    }

    /**
     * Display warming result
     */
    /**
     * @param  array{warmed_count: int, failed_count: int, skipped_count: int, requested_count?: int, duration_seconds?: float, cache_hits_improved?: bool, errors?: array<array{domain: string, error: string}>, rate_limited?: bool, reason?: string}  $result
     */
    protected function displayResult(array $result, string $title, string $format): int
    {
        if ($format === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return 0;
        }

        $this->newLine();
        $this->info($title.' Results:');
        $this->newLine();

        if (isset($result['rate_limited']) && $result['rate_limited']) {
            $this->warn('⚠️  Warming was rate limited. Try again later or increase rate limit.');

            return 1;
        }

        if (isset($result['reason'])) {
            $this->info("ℹ️  {$result['reason']}");

            return 0;
        }

        $successRate = $result['requested_count'] > 0
            ? ($result['warmed_count'] / $result['requested_count']) * 100
            : 0;

        $this->table(['Metric', 'Value'], [
            ['Domains requested', number_format($result['requested_count'] ?? 0)],
            ['Successfully warmed', number_format($result['warmed_count'] ?? 0)],
            ['Failed', number_format($result['failed_count'] ?? 0)],
            ['Success rate', number_format($successRate, 1).'%'],
            ['Duration (seconds)', number_format($result['duration_seconds'] ?? 0, 2)],
            ['Cache hits improved', ($result['cache_hits_improved'] ?? false) ? '✅ Yes' : '❌ No'],
        ]);

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Errors encountered:');
            foreach (array_slice($result['errors'], 0, 5) as $error) {
                $this->line("   • {$error['domain']}: {$error['error']}");
            }

            $totalErrors = count($result['errors']);
            if ($totalErrors > 5) {
                $this->line('   ... and '.($totalErrors - 5).' more errors');
            }
        }

        return $result['warmed_count'] > 0 ? 0 : 1;
    }

    /**
     * Show command help
     */
    protected function showHelp(): int
    {
        $this->info('DNS Cache Warming Commands:');
        $this->newLine();
        $this->line('  dns:warm popular       - Warm popular domains based on usage frequency');
        $this->line('  dns:warm trending      - Warm trending domains and TLDs');
        $this->line('  dns:warm stale         - Rewarm stale cache entries');
        $this->line('  dns:warm custom        - Warm custom domain list');
        $this->line('  dns:warm analytics     - Show warming analytics and performance');
        $this->line('  dns:warm schedule      - Show optimal warming schedule');
        $this->newLine();
        $this->line('Options:');
        $this->line('  --limit=100            - Number of domains to warm');
        $this->line('  --domains=domain:tld   - Custom domains (comma-separated)');
        $this->line('  --async                - Run warming jobs in background');
        $this->line('  --force                - Force warming during peak hours');
        $this->line('  --format=json          - Output in JSON format');
        $this->newLine();
        $this->line('Examples:');
        $this->line('  dns:warm popular --limit=50 --async');
        $this->line('  dns:warm custom --domains=google:com,github:com');
        $this->line('  dns:warm analytics --format=json');

        return 0;
    }
}
