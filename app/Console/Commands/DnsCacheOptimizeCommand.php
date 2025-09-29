<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DnsCacheOptimizationService;
use Illuminate\Console\Command;

final class DnsCacheOptimizeCommand extends Command
{
    protected $signature = 'dns:cache-optimize
                           {action=all : Action to perform (all|clean|preload|analyze|suggestions)}
                           {--domains=100 : Number of domains to preload (default: 100)}
                           {--days=7 : Number of days for analysis (default: 7)}
                           {--format=table : Output format (table|json)}';

    protected $description = 'Optimize DNS lookup cache for better performance';

    public function handle(DnsCacheOptimizationService $optimizationService): int
    {
        $action = $this->argument('action');
        $format = $this->option('format');

        return match ($action) {
            'all' => $this->optimizeAll($optimizationService, $format),
            'clean' => $this->cleanCache($optimizationService, $format),
            'preload' => $this->preloadDomains($optimizationService, $format),
            'analyze' => $this->analyzeCache($optimizationService, $format),
            'suggestions' => $this->showSuggestions($optimizationService, $format),
            default => $this->showHelp(),
        };
    }

    private function optimizeAll(DnsCacheOptimizationService $service, string $format): int
    {
        $this->info('🔧 Starting comprehensive DNS cache optimization...');
        $this->newLine();

        // Step 1: Clean cache
        $this->line('📋 Step 1: Cleaning expired and duplicate entries...');
        $optimizationResult = $service->optimizeCache();

        $this->displayOptimizationResult($optimizationResult);
        $this->newLine();

        // Step 2: Preload popular domains
        $this->line('📋 Step 2: Preloading popular domains...');
        $domains = (int) $this->option('domains');
        $preloadResult = $service->preloadPopularDomains($domains);

        $this->displayPreloadResult($preloadResult);
        $this->newLine();

        // Step 3: Show cache statistics
        $this->line('📋 Step 3: Current cache statistics...');
        $stats = $service->getCacheStatistics();

        $this->displayCacheStats($stats, $format);
        $this->newLine();

        // Step 4: Show improvement suggestions
        $this->line('📋 Step 4: Cache improvement suggestions...');
        $suggestions = $service->suggestCacheImprovements();

        $this->displaySuggestions($suggestions, $format);

        $this->info('✅ DNS cache optimization completed successfully!');
        return Command::SUCCESS;
    }

    private function cleanCache(DnsCacheOptimizationService $service, string $format): int
    {
        $this->info('🧹 Cleaning DNS cache...');

        $result = $service->optimizeCache();
        $this->displayOptimizationResult($result);

        if ($format === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }

        $this->info('✅ Cache cleaning completed!');
        return Command::SUCCESS;
    }

    private function preloadDomains(DnsCacheOptimizationService $service, string $format): int
    {
        $domains = (int) $this->option('domains');
        $this->info("🔄 Preloading {$domains} popular domains...");

        $result = $service->preloadPopularDomains($domains);
        $this->displayPreloadResult($result);

        if ($format === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }

        $this->info('✅ Domain preloading completed!');
        return Command::SUCCESS;
    }

    private function analyzeCache(DnsCacheOptimizationService $service, string $format): int
    {
        $days = (int) $this->option('days');
        $this->info("📊 Analyzing DNS cache performance over {$days} days...");

        $stats = $service->getCacheStatistics();
        $analysis = $service->getCacheHitAnalysis($days);

        $this->displayCacheStats($stats, $format);
        $this->newLine();
        $this->displayHitAnalysis($analysis, $format);

        if ($format === 'json') {
            $this->line(json_encode([
                'cache_stats' => $stats,
                'hit_analysis' => $analysis,
            ], JSON_PRETTY_PRINT));
        }

        return Command::SUCCESS;
    }

    private function showSuggestions(DnsCacheOptimizationService $service, string $format): int
    {
        $this->info('💡 DNS cache improvement suggestions...');

        $suggestions = $service->suggestCacheImprovements();
        $this->displaySuggestions($suggestions, $format);

        if ($format === 'json') {
            $this->line(json_encode($suggestions, JSON_PRETTY_PRINT));
        }

        return Command::SUCCESS;
    }

    private function showHelp(): int
    {
        $this->error('Invalid action specified.');
        $this->newLine();
        $this->line('Available actions:');
        $this->line('  <info>all</info>         - Run complete optimization (clean + preload + analyze + suggestions)');
        $this->line('  <info>clean</info>       - Clean expired and duplicate cache entries');
        $this->line('  <info>preload</info>     - Preload popular domains into cache');
        $this->line('  <info>analyze</info>     - Show cache statistics and hit analysis');
        $this->line('  <info>suggestions</info> - Show improvement suggestions');
        $this->newLine();
        $this->line('Options:');
        $this->line('  <info>--domains=N</info>  - Number of domains to preload (default: 100)');
        $this->line('  <info>--days=N</info>     - Number of days for analysis (default: 7)');
        $this->line('  <info>--format=F</info>   - Output format: table or json (default: table)');

        return Command::FAILURE;
    }

    private function displayOptimizationResult(array $result): void
    {
        $this->table(
            ['Optimization', 'Count'],
            [
                ['Expired entries removed', $result['expired_removed']],
                ['Duplicate entries cleaned', $result['duplicates_cleaned']],
                ['Orphaned entries cleaned', $result['orphaned_cleaned']],
            ]
        );
    }

    private function displayPreloadResult(array $result): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Domains preloaded', $result['preloaded_count']],
                ['Errors encountered', count($result['errors'])],
            ]
        );

        if (!empty($result['errors'])) {
            $this->warn('⚠️  Errors encountered during preloading:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error['domain']}: {$error['error']}");
            }
        }
    }

    private function displayCacheStats(array $stats, string $format): void
    {
        if ($format === 'json') {
            return;
        }

        $this->table(
            ['Statistic', 'Value'],
            [
                ['Total entries', number_format($stats['total_entries'])],
                ['Valid entries', number_format($stats['valid_entries'])],
                ['Expired entries', number_format($stats['expired_entries'])],
                ['Cache hit rate (24h)', $stats['cache_hit_rate'] . '%'],
                ['Total cache hits (24h)', number_format($stats['total_cache_hits_24h'])],
                ['Total lookups (24h)', number_format($stats['total_lookups_24h'])],
                ['Memory usage estimate', $this->formatBytes($stats['memory_usage_estimate'])],
            ]
        );

        if (!empty($stats['top_tlds'])) {
            $this->newLine();
            $this->line('<info>Top TLDs by cache entries:</info>');
            $tldData = [];
            foreach (array_slice($stats['top_tlds'], 0, 5) as $tld) {
                $tldData[] = [".{$tld['tld']}", number_format($tld['count'])];
            }
            $this->table(['TLD', 'Count'], $tldData);
        }
    }

    private function displayHitAnalysis(array $analysis, string $format): void
    {
        if ($format === 'json') {
            return;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Analysis period', $analysis['period_days'] . ' days'],
                ['Overall hit rate', $analysis['overall_hit_rate'] . '%'],
                ['Total cache hits', number_format($analysis['total_cache_hits'])],
                ['Total lookups', number_format($analysis['total_lookups'])],
                ['Cache efficiency', $analysis['cache_efficiency'] . '%'],
            ]
        );
    }

    private function displaySuggestions(array $suggestions, string $format): void
    {
        if ($format === 'json') {
            return;
        }

        if (empty($suggestions['suggestions'])) {
            $this->info('✅ No optimization suggestions - your cache is performing well!');
            $this->line("Optimization score: <info>{$suggestions['optimization_score']}/100</info>");
            return;
        }

        $this->warn('⚠️  Found ' . count($suggestions['suggestions']) . ' optimization suggestions:');
        $this->line("Current optimization score: <comment>{$suggestions['optimization_score']}/100</comment>");
        $this->newLine();

        $suggestionData = [];
        foreach ($suggestions['suggestions'] as $suggestion) {
            $priority = match ($suggestion['priority']) {
                'high' => '🔴 High',
                'medium' => '🟡 Medium',
                'low' => '🟢 Low',
                default => $suggestion['priority'],
            };

            $suggestionData[] = [
                $priority,
                $suggestion['type'],
                $suggestion['suggestion'],
            ];
        }

        $this->table(['Priority', 'Type', 'Suggestion'], $suggestionData);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
