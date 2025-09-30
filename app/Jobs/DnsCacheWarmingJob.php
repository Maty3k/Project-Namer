<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DnsCacheWarmingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class DnsCacheWarmingJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $strategy = 'popular',
        public array $options = []
    ) {
        // Use low priority queue for cache warming
        $this->onQueue('warming');
    }

    /**
     * Execute the job.
     */
    public function handle(DnsCacheWarmingService $warmingService): void
    {
        try {
            Log::info('DNS cache warming job started', [
                'strategy' => $this->strategy,
                'options' => $this->options,
            ]);

            $result = match ($this->strategy) {
                'popular' => $this->warmPopularDomains($warmingService),
                'trending' => $this->warmTrendingDomains($warmingService),
                'stale' => $this->rewarmStaleDomains($warmingService),
                'custom' => $this->warmCustomDomains($warmingService),
                default => throw new \InvalidArgumentException("Unknown warming strategy: {$this->strategy}")
            };

            Log::info('DNS cache warming job completed', [
                'strategy' => $this->strategy,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('DNS cache warming job failed', [
                'strategy' => $this->strategy,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger job failure handling
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('DNS cache warming job failed permanently', [
            'strategy' => $this->strategy,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Warm popular domains based on frequency
     *
     * @return array<string, mixed>
     */
    protected function warmPopularDomains(DnsCacheWarmingService $warmingService): array
    {
        $limit = $this->options['limit'] ?? config('dns.warming.strategies.popular_domains_limit', 100);

        return $warmingService->warmPopularDomains($limit);
    }

    /**
     * Warm trending domains and TLDs
     *
     * @return array<string, mixed>
     */
    protected function warmTrendingDomains(DnsCacheWarmingService $warmingService): array
    {
        return $warmingService->warmTrendingTlds();
    }

    /**
     * Rewarm stale cache entries
     *
     * @return array<string, mixed>
     */
    protected function rewarmStaleDomains(DnsCacheWarmingService $warmingService): array
    {
        $staleDomains = $warmingService->getStaleDomainsForRewarming();
        $limit = $this->options['limit'] ?? config('dns.warming.strategies.stale_rewarming_limit', 50);

        $domainList = $staleDomains->take($limit)->map(fn ($cache) => ['domain' => $cache->domain, 'tld' => $cache->tld])->toArray();

        if (empty($domainList)) {
            return [
                'warmed_count' => 0,
                'reason' => 'No stale domains found for rewarming',
            ];
        }

        return $warmingService->warmDomainList($domainList);
    }

    /**
     * Warm custom domain list
     *
     * @return array<string, mixed>
     */
    protected function warmCustomDomains(DnsCacheWarmingService $warmingService): array
    {
        $domains = $this->options['domains'] ?? [];

        if (empty($domains)) {
            return [
                'warmed_count' => 0,
                'reason' => 'No custom domains provided',
            ];
        }

        return $warmingService->warmDomainList($domains);
    }

    /**
     * Static method to dispatch popular domains warming job
     */
    public static function warmPopular(?int $limit = null): void
    {
        $options = $limit ? ['limit' => $limit] : [];
        self::dispatch('popular', $options);
    }

    /**
     * Static method to dispatch trending domains warming job
     */
    public static function warmTrending(): void
    {
        self::dispatch('trending');
    }

    /**
     * Static method to dispatch stale domains rewarming job
     */
    public static function rewarmStale(?int $limit = null): void
    {
        $options = $limit ? ['limit' => $limit] : [];
        self::dispatch('stale', $options);
    }

    /**
     * Static method to dispatch custom domains warming job
     *
     * @param  array<array{domain: string, tld: string}>  $domains
     */
    public static function warmCustom(array $domains): void
    {
        self::dispatch('custom', ['domains' => $domains]);
    }
}
