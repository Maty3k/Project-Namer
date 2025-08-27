<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LogoGeneration;
use App\Services\FileManagementService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Old Files Job.
 *
 * Automatically removes old logo generation files and temporary files
 * to manage storage usage and maintain system performance.
 */
final class CleanupOldFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(/**
     * Age threshold for file cleanup (in days).
     */
        private int $maxAgeDays = 30, /**
     * Whether to include failed generations in cleanup.
     */
        private bool $includeFailedGenerations = true)
    {
        $this->onQueue('file-cleanup');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting automatic file cleanup job', [
            'max_age_days' => $this->maxAgeDays,
            'include_failed_generations' => $this->includeFailedGenerations,
        ]);

        $fileManagement = app(FileManagementService::class);
        $cutoffDate = Carbon::now()->subDays($this->maxAgeDays);

        $stats = [
            'logo_generations_processed' => 0,
            'logo_generations_cleaned' => 0,
            'temp_files_cleaned' => 0,
            'orphaned_files_cleaned' => 0,
            'failed_generations_cleaned' => 0,
            'total_storage_freed' => 0,
        ];

        try {
            // Clean up old logo generations
            $stats = array_merge($stats, $this->cleanupOldLogoGenerations($fileManagement, $cutoffDate));

            // Clean up temporary files for all active generations
            $stats['temp_files_cleaned'] = $this->cleanupTemporaryFiles($fileManagement);

            // Clean up orphaned files
            $stats['orphaned_files_cleaned'] = $fileManagement->cleanupOrphanedFiles();

            // Clean up failed generations if enabled
            if ($this->includeFailedGenerations) {
                $stats['failed_generations_cleaned'] = $this->cleanupFailedGenerations($fileManagement);
            }

            Log::info('File cleanup job completed successfully', $stats);

        } catch (\Exception $e) {
            Log::error('File cleanup job failed', [
                'error' => $e->getMessage(),
                'stats' => $stats,
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error('File cleanup job failed permanently', [
            'max_age_days' => $this->maxAgeDays,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'file-cleanup',
            'maintenance',
            "cleanup-{$this->maxAgeDays}days",
        ];
    }

    /**
     * The job's backoff strategy.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1min, 5min, 15min
    }

    /**
     * Clean up old logo generations.
     *
     * @return array<string, int>
     */
    private function cleanupOldLogoGenerations(FileManagementService $fileManagement, Carbon $cutoffDate): array
    {
        $query = LogoGeneration::where('created_at', '<', $cutoffDate);

        // Only include completed generations to avoid cleaning up active work
        $query->whereIn('status', ['completed', 'failed']);

        $oldGenerations = $query->get();
        $processed = 0;
        $cleaned = 0;
        $totalStorageFreed = 0;

        foreach ($oldGenerations as $logoGeneration) {
            $processed++;

            try {
                // Calculate storage usage before cleanup
                $usage = $fileManagement->calculateStorageUsage($logoGeneration->id);
                $storageToFree = $usage['total_bytes'];

                // Delete the logo generation files
                $result = $fileManagement->deleteLogoGeneration($logoGeneration->id);

                if ($result['success']) {
                    $cleaned++;
                    $totalStorageFreed += $storageToFree;

                    // Optionally delete the database record as well
                    // $logoGeneration->delete();

                    Log::info('Cleaned up old logo generation', [
                        'logo_generation_id' => $logoGeneration->id,
                        'business_name' => $logoGeneration->business_name,
                        'age_days' => $logoGeneration->created_at->diffInDays(now()),
                        'storage_freed' => $storageToFree,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup old logo generation', [
                    'logo_generation_id' => $logoGeneration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'logo_generations_processed' => $processed,
            'logo_generations_cleaned' => $cleaned,
            'total_storage_freed' => $totalStorageFreed,
        ];
    }

    /**
     * Clean up temporary files for all logo generations.
     */
    private function cleanupTemporaryFiles(FileManagementService $fileManagement): int
    {
        $totalCleaned = 0;
        $activeGenerations = LogoGeneration::whereIn('status', ['pending', 'processing'])->get();

        // Clean temp files older than 24 hours for active generations
        foreach ($activeGenerations as $logoGeneration) {
            try {
                $cleaned = $fileManagement->cleanupTempFiles($logoGeneration->id, 24);
                $totalCleaned += $cleaned;
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup temp files', [
                    'logo_generation_id' => $logoGeneration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // For completed generations, clean all temp files
        $completedGenerations = LogoGeneration::whereIn('status', ['completed', 'failed'])->get();
        foreach ($completedGenerations as $logoGeneration) {
            try {
                $cleaned = $fileManagement->cleanupTempFiles($logoGeneration->id, 0); // Clean all temp files
                $totalCleaned += $cleaned;
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup temp files for completed generation', [
                    'logo_generation_id' => $logoGeneration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totalCleaned;
    }

    /**
     * Clean up failed logo generations.
     */
    private function cleanupFailedGenerations(FileManagementService $fileManagement): int
    {
        $failedGenerations = LogoGeneration::where('status', 'failed')
            ->where('created_at', '<', Carbon::now()->subDays(7)) // Keep failed for 7 days for debugging
            ->get();

        $cleanedCount = 0;

        foreach ($failedGenerations as $logoGeneration) {
            try {
                $result = $fileManagement->cleanupFailedGeneration($logoGeneration);

                if ($result['success']) {
                    $cleanedCount++;

                    Log::info('Cleaned up failed logo generation', [
                        'logo_generation_id' => $logoGeneration->id,
                        'business_name' => $logoGeneration->business_name,
                        'failed_at' => $logoGeneration->updated_at,
                        'error_message' => $logoGeneration->error_message,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup failed generation', [
                    'logo_generation_id' => $logoGeneration->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cleanedCount;
    }

    /**
     * Get the display name for the queued job.
     */
    public function displayName(): string
    {
        return "Cleanup Old Files ({$this->maxAgeDays} days)";
    }
}
