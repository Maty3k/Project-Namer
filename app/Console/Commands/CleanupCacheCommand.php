<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DomainCheckService;
use App\Services\OpenAINameService;
use Illuminate\Console\Command;

class CleanupCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:cleanup {--type=all : Type of cache to clean (domain, generation, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired cache entries for improved performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');

        $totalDeleted = 0;

        if ($type === 'domain' || $type === 'all') {
            $this->info('Cleaning up expired domain cache entries...');
            $domainService = app(DomainCheckService::class);
            $domainDeleted = $domainService->clearExpiredCache();
            $totalDeleted += $domainDeleted;
            $this->line("Deleted {$domainDeleted} expired domain cache entries");
        }

        if ($type === 'generation' || $type === 'all') {
            $this->info('Cleaning up expired generation cache entries...');
            $nameService = app(OpenAINameService::class);
            $generationDeleted = $nameService->clearExpiredCache();
            $totalDeleted += $generationDeleted;
            $this->line("Deleted {$generationDeleted} expired generation cache entries");
        }

        $this->info("Cache cleanup completed! Total entries deleted: {$totalDeleted}");

        return 0;
    }
}
