<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DomainCheckService;
use Illuminate\Console\Command;

/**
 * Clear expired domain availability cache entries.
 */
class ClearExpiredDomainCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:clear-expired-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired domain availability cache entries';

    /**
     * Execute the console command.
     */
    public function handle(DomainCheckService $domainCheckService): int
    {
        $this->info('Clearing expired domain cache entries...');

        $deleted = $domainCheckService->clearExpiredCache();

        if ($deleted > 0) {
            $this->info("Successfully deleted {$deleted} expired cache entries.");
        } else {
            $this->info('No expired cache entries found.');
        }

        return self::SUCCESS;
    }
}
