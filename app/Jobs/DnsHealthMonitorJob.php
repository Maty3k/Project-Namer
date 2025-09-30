<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\DnsHealthCheckService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class DnsHealthMonitorJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Set the queue to a low priority for monitoring
        $this->onQueue('monitoring');
    }

    /**
     * Execute the job.
     */
    public function handle(DnsHealthCheckService $healthCheckService): void
    {
        try {
            Log::info('DNS health monitoring job started');

            // Perform DNS health check - this will automatically trigger alerts
            $healthStatus = $healthCheckService->performHealthCheck();

            if ($healthStatus['overall_status'] !== 'healthy') {
                Log::warning('DNS health issues detected', [
                    'status' => $healthStatus['overall_status'],
                    'checked_at' => $healthStatus['checked_at'],
                ]);
            } else {
                Log::debug('DNS health check completed - all metrics healthy');
            }

        } catch (\Exception $e) {
            Log::error('DNS health monitoring job failed', [
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
        Log::critical('DNS health monitoring job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Could send a critical alert here that monitoring itself is failing
    }
}
