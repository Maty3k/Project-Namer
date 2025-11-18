<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class CleanupOldExports implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $batchSize = 100
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting old exports cleanup job');

        $deletedCount = 0;
        $filesDeletedCount = 0;

        // Process exports in batches to avoid memory issues
        Export::where(function ($query): void {
            // Expired exports
            $query->where('expires_at', '<', now())
                // Or exports without expiration older than 90 days
                ->orWhere(function ($q): void {
                    $q->whereNull('expires_at')
                        ->where('created_at', '<', now()->subDays(90));
                });
        })->chunkById($this->batchSize, function ($exports) use (&$deletedCount, &$filesDeletedCount): void {
            foreach ($exports as $export) {
                if ($this->cleanupExport($export)) {
                    $deletedCount++;
                    $filesDeletedCount++;
                }
            }
        });

        Log::info('Cleanup old exports completed', [
            'deleted' => $deletedCount,
            'files_deleted' => $filesDeletedCount,
            'message' => "Successfully deleted {$deletedCount} expired exports and {$filesDeletedCount} files",
        ]);
    }

    /**
     * Cleanup a single export.
     */
    private function cleanupExport(Export $export): bool
    {
        try {
            // Delete the file if it exists
            if (Storage::exists($export->file_path)) {
                try {
                    Storage::delete($export->file_path);
                    Log::debug('Export file deleted', [
                        'export_uuid' => $export->uuid,
                        'file_path' => $export->file_path,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to delete export file', [
                        'export_uuid' => $export->uuid,
                        'file_path' => $export->file_path,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue to delete the database record even if file deletion fails
                }
            }

            // Delete the database record
            $export->delete();

            Log::debug('Export record deleted', [
                'export_uuid' => $export->uuid,
                'export_type' => $export->export_type,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup export', [
                'export_uuid' => $export->uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the tags associated with the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['cleanup', 'exports', 'maintenance'];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Old exports cleanup job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
