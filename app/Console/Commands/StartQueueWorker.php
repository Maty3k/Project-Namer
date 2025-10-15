<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Start the queue worker for background job processing.
 *
 * This command starts a queue worker that processes background jobs
 * such as domain DNS checking. Keep this running in a separate terminal
 * while using the application.
 */
class StartQueueWorker extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:dev';

    /**
     * The console command description.
     */
    protected $description = 'Start queue worker for development (processes domain checks and other background jobs)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting queue worker for domain DNS checking...');
        $this->newLine();
        $this->comment('💡 Keep this terminal open to process background jobs');
        $this->comment('   Press Ctrl+C to stop');
        $this->newLine();

        // Start the queue worker with appropriate settings for development
        $this->call('queue:work', [
            '--verbose' => true,
            '--tries' => 3,
            '--timeout' => 60,
        ]);

        return self::SUCCESS;
    }
}
