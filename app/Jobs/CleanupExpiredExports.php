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

final class CleanupExpiredExports implements ShouldQueue
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
        Log::info('Starting expired exports cleanup job');

        $cleanedUpCount = 0;
        $filesDeleted = 0;
        $totalSizeDeleted = 0;

        // Process exports in batches to avoid memory issues
        Export::where('expires_at', '<', now())
            ->chunkById($this->batchSize, function ($exports) use (&$cleanedUpCount, &$filesDeleted, &$totalSizeDeleted): void {
                foreach ($exports as $export) {
                    $result = $this->cleanupExport($export);
                    $cleanedUpCount++;

                    if ($result['file_deleted']) {
                        $filesDeleted++;
                        $totalSizeDeleted += $result['file_size'];
                    }
                }
            });

        // Also cleanup orphaned export files (files without database records)
        $orphanedFilesCount = $this->cleanupOrphanedExportFiles();

        Log::info('Expired exports cleanup completed', [
            'expired_exports_deleted' => $cleanedUpCount,
            'files_deleted' => $filesDeleted,
            'orphaned_files_cleaned' => $orphanedFilesCount,
            'total_size_deleted_bytes' => $totalSizeDeleted,
            'total_size_deleted_mb' => round($totalSizeDeleted / 1024 / 1024, 2),
        ]);
    }

    /**
     * Cleanup a single expired export.
     *
     * @return array{file_deleted: bool, file_size: int}
     */
    private function cleanupExport(Export $export): array
    {
        $result = [
            'file_deleted' => false,
            'file_size' => 0,
        ];

        try {
            // Get file size before deletion for reporting
            if ($export->file_path && Storage::exists($export->file_path)) {
                $result['file_size'] = Storage::size($export->file_path);

                // Delete the physical file
                Storage::delete($export->file_path);
                $result['file_deleted'] = true;

                Log::debug('Export file deleted', [
                    'export_uuid' => $export->uuid,
                    'file_path' => $export->file_path,
                    'file_size' => $result['file_size'],
                ]);
            }

            // Delete the database record
            $export->delete();

            Log::debug('Export record deleted', [
                'export_uuid' => $export->uuid,
                'expired_at' => $export->expires_at?->toISOString(),
                'export_type' => $export->export_type,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired export', [
                'export_uuid' => $export->uuid,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Cleanup orphaned export files that don't have database records.
     */
    private function cleanupOrphanedExportFiles(): int
    {
        $orphanedCount = 0;
        $exportDirs = ['exports/pdf', 'exports/csv', 'exports/json'];

        foreach ($exportDirs as $dir) {
            try {
                $files = Storage::files($dir);

                foreach ($files as $filePath) {
                    // Check if this file has a corresponding database record
                    $fileExists = Export::where('file_path', $filePath)->exists();

                    if (! $fileExists) {
                        // Check if file is older than 24 hours to avoid deleting recently created files
                        $fileModified = Storage::lastModified($filePath);
                        if ($fileModified < now()->subDay()->timestamp) {
                            Storage::delete($filePath);
                            $orphanedCount++;

                            Log::debug('Orphaned export file deleted', [
                                'file_path' => $filePath,
                                'last_modified' => date('Y-m-d H:i:s', $fileModified),
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to cleanup orphaned files in directory', [
                    'directory' => $dir,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $orphanedCount;
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
        Log::error('Expired exports cleanup job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
