<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CleanupOldFilesJob;
use Illuminate\Console\Command;

final class CleanupOldFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logos:cleanup
                            {--days=30 : Number of days to keep files}
                            {--include-failed : Include failed generations in cleanup}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old logo generation files and temporary files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $includeFailed = (bool) $this->option('include-failed');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting file cleanup...');
        $this->info("Settings: Keep files for {$days} days");

        if ($includeFailed) {
            $this->info('Including failed generations in cleanup');
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            // TODO: Implement dry run logic
            $this->info('Dry run completed. No files were deleted.');

            return Command::SUCCESS;
        }

        try {
            // Dispatch the cleanup job immediately
            CleanupOldFilesJob::dispatchSync($days, $includeFailed);

            $this->info('File cleanup completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('File cleanup failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
