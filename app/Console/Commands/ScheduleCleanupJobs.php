<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CleanupExpiredExports;
use App\Jobs\CleanupExpiredShares;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ScheduleCleanupJobs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cleanup:schedule
                           {--shares : Only run shares cleanup}
                           {--exports : Only run exports cleanup}
                           {--force : Force run even if not in production}';

    /**
     * The console command description.
     */
    protected $description = 'Schedule cleanup jobs for expired shares and exports';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cleanup job scheduling...');

        $sharesOnly = $this->option('shares');
        $exportsOnly = $this->option('exports');
        $force = $this->option('force');

        // Safety check for production
        if (! $force && ! app()->environment(['production', 'staging'])) {
            $this->warn('Cleanup jobs are typically run in production. Use --force to run in other environments.');

            if (! $this->confirm('Do you want to continue anyway?')) {
                $this->info('Cleanup cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            // Schedule shares cleanup if not exports-only
            if (! $exportsOnly) {
                CleanupExpiredShares::dispatch();
                $this->info('✓ Scheduled expired shares cleanup job');
                Log::info('Expired shares cleanup job scheduled via command');
            }

            // Schedule exports cleanup if not shares-only
            if (! $sharesOnly) {
                CleanupExpiredExports::dispatch();
                $this->info('✓ Scheduled expired exports cleanup job');
                Log::info('Expired exports cleanup job scheduled via command');
            }

            $this->newLine();
            $this->info('All cleanup jobs have been scheduled successfully!');
            $this->info('Check the queue worker logs for execution details.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to schedule cleanup jobs: '.$e->getMessage());
            Log::error('Failed to schedule cleanup jobs via command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
